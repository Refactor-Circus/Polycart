<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Support;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
use RefactorCircus\Polycart\Exceptions\UnknownMorphException;

/**
 * Turns an owner or purchasable named in an API or MCP call into a model.
 *
 * Only the models listed in `polycart.owners`, `polycart.scopes` and
 * `polycart.purchasables` can be reached, so a caller can never make the package load an arbitrary
 * class. Either the alias (the array key) or the class itself is accepted.
 */
final class Morphs
{
    public function __construct(private readonly Config $config) {}

    /**
     * Rules for naming an owner: `owner_type` and `owner_id`, or a guest's
     * `session_key`. One or the other is required when `$required`.
     *
     * @return array<string, array<int, string>>
     */
    public static function ownerRules(bool $required = false): array
    {
        return $required
            ? [
                'owner_type' => ['required_without:session_key', 'nullable', 'string', 'max:191', 'required_with:owner_id', 'prohibits:session_key'],
                'owner_id' => ['required_with:owner_type', 'nullable', 'string', 'max:191'],
                'session_key' => ['required_without:owner_type', 'nullable', 'string', 'max:191'],
            ]
            : [
                'owner_type' => ['nullable', 'string', 'max:191', 'required_with:owner_id', 'prohibits:session_key'],
                'owner_id' => ['nullable', 'string', 'max:191', 'required_with:owner_type'],
                'session_key' => ['sometimes', 'nullable', 'string', 'max:191'],
            ];
    }

    /**
     * Rules for naming a scope: `scope_type` and `scope_id`, both or neither.
     *
     * @return array<string, array<int, string>>
     */
    public static function scopeRules(): array
    {
        return [
            'scope_type' => ['nullable', 'string', 'max:191', 'required_with:scope_id'],
            'scope_id' => ['nullable', 'string', 'max:191', 'required_with:scope_type'],
        ];
    }

    /**
     * Rules for naming a cart member: `member_type` and `member_id`.
     *
     * @return array<string, array<int, string>>
     */
    public static function memberRules(): array
    {
        return [
            'member_type' => ['required', 'string', 'max:191'],
            'member_id' => ['required', 'string', 'max:191'],
        ];
    }

    /**
     * The owner described by validated input: a model, a guest session key,
     * or nobody.
     *
     * @param  array<string, mixed>  $data
     */
    public function ownerFrom(array $data): Model|string|null
    {
        if (is_string($data['owner_type'] ?? null) && is_scalar($data['owner_id'] ?? null)) {
            return $this->find('owners', $data['owner_type'], (string) $data['owner_id']);
        }

        return is_string($data['session_key'] ?? null) ? $data['session_key'] : null;
    }

    /**
     * The scope described by validated input, or null for an unscoped cart.
     *
     * @param  array<string, mixed>  $data
     */
    public function scopeFrom(array $data): ?Model
    {
        if (is_string($data['scope_type'] ?? null) && is_scalar($data['scope_id'] ?? null)) {
            return $this->find('scopes', $data['scope_type'], (string) $data['scope_id']);
        }

        return null;
    }

    /**
     * A cart member: a participant from `polycart.owners`, or a whole scope
     * from `polycart.scopes`.
     *
     * @param  array<string, mixed>  $data
     */
    public function memberFrom(array $data): Model
    {
        /** @var string $type */
        $type = $data['member_type'];

        /** @var int|string $id */
        $id = $data['member_id'];

        $group = $this->lookup('owners', $type) === null ? 'scopes' : 'owners';

        return $this->find($group, $type, (string) $id);
    }

    /**
     * The purchasable described by validated input, or null for a custom line.
     *
     * @param  array<string, mixed>  $data
     */
    public function purchasableFrom(array $data): ?Model
    {
        if (is_string($data['purchasable_type'] ?? null) && is_scalar($data['purchasable_id'] ?? null)) {
            return $this->find('purchasables', $data['purchasable_type'], (string) $data['purchasable_id']);
        }

        return null;
    }

    /**
     * The purchasables a batch of validated lines names, in the same order
     * and with null for custom lines. Each kind of purchasable is read in
     * one query; a missing one fails as a single lookup does.
     *
     * @param  array<int, mixed>  $lines
     * @return array<int, Model|null>
     */
    public function purchasablesFrom(array $lines): array
    {
        /** @var array<class-string<Model>, array<int, string>> $wanted */
        $wanted = [];
        $named = [];

        foreach ($lines as $index => $line) {
            if (is_array($line) && is_string($line['purchasable_type'] ?? null) && is_scalar($line['purchasable_id'] ?? null)) {
                $class = $this->lookup('purchasables', $line['purchasable_type'])
                    ?? throw UnknownMorphException::notAllowed('purchasables', $line['purchasable_type']);

                $named[$index] = [$class, (string) $line['purchasable_id']];
                $wanted[$class][] = (string) $line['purchasable_id'];
            }
        }

        $found = [];

        foreach ($wanted as $class => $ids) {
            foreach ($class::query()->whereKey(array_values(array_unique($ids)))->get() as $model) {
                $found[$class][(string) $model->getKey()] = $model;
            }
        }

        $purchasables = [];

        foreach (array_keys($lines) as $index) {
            if (! isset($named[$index])) {
                $purchasables[$index] = null;

                continue;
            }

            [$class, $id] = $named[$index];

            $purchasables[$index] = $found[$class][$id] ?? throw (new ModelNotFoundException)->setModel($class, [$id]);
        }

        return $purchasables;
    }

    /**
     * The morph class stored for an owner alias, for filtering.
     *
     * An unknown value is returned as given: comparing a string is harmless,
     * and it still matches rows written under that exact morph class.
     */
    public function ownerMorphClass(string $type): string
    {
        $class = $this->lookup('owners', $type);

        return $class === null ? $type : (new $class)->getMorphClass();
    }

    /**
     * The morph class stored for a scope alias, for filtering.
     */
    public function scopeMorphClass(string $type): string
    {
        $class = $this->lookup('scopes', $type);

        return $class === null ? $type : (new $class)->getMorphClass();
    }

    private function find(string $group, string $type, string $id): Model
    {
        $class = $this->lookup($group, $type) ?? throw UnknownMorphException::notAllowed($group, $type);

        return $class::query()->findOrFail($id);
    }

    /**
     * @return class-string<Model>|null
     */
    private function lookup(string $group, string $type): ?string
    {
        /** @var array<string, class-string<Model>> $allowed */
        $allowed = $this->config->get('polycart.'.$group, []);

        if (isset($allowed[$type])) {
            return $allowed[$type];
        }

        $class = Relation::getMorphedModel($type) ?? $type;

        return in_array($class, $allowed, true) ? $class : null;
    }
}
