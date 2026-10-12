<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Scope\Services;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Polycart\Domains\Scope\Contracts\CartParticipant;
use RefactorCircus\Polycart\Domains\Scope\Contracts\CartScope;

/**
 * Walks the application's tree of scopes.
 *
 * Scopes are identified by [morph class, key] pairs so the same level
 * compares equal however it was loaded.
 *
 * Bound once per request (a scoped binding), it reads what each actor
 * reaches once and answers every later check from memory. Code that changes
 * someone's scopes and checks again in the same request calls `forget()`.
 */
final class ScopeTree
{
    /**
     * How far up a chain is followed, so a cycle cannot loop forever.
     */
    private const int MAX_DEPTH = 32;

    /**
     * What each actor reaches, keyed by "type|id", read once per request.
     *
     * @var array<string, array<string, array{0: string, 1: string}>>
     */
    private array $reach = [];

    /**
     * A scope and every level above it, nearest first.
     *
     * @return array<int, Model&CartScope>
     */
    public function chain(CartScope $scope): array
    {
        $chain = [];
        $seen = [];

        for ($level = $scope; $level instanceof CartScope && count($chain) < self::MAX_DEPTH; $level = $level->parentCartScope()) {
            if (! $level instanceof Model) {
                break;
            }

            $key = self::key($level);

            if (isset($seen[$key])) {
                break;
            }

            $seen[$key] = true;
            $chain[] = $level;
        }

        return $chain;
    }

    /**
     * The top of a scope's chain.
     */
    public function boundary(CartScope $scope): ?Model
    {
        $chain = $this->chain($scope);

        return $chain === [] ? null : $chain[array_key_last($chain)];
    }

    /**
     * Every scope a participant or scope reaches: the ones it belongs to
     * directly, and every level above them.
     *
     * @return array<string, array{0: string, 1: string}> Keyed by "type|id".
     */
    public function reach(Model $actor): array
    {
        if ($actor->getKey() === null) {
            return $this->walk($actor);
        }

        return $this->reach[self::key($actor)] ??= $this->walk($actor);
    }

    /**
     * Forget what an actor reaches, so the next check reads their scopes
     * again: call it after changing someone's teams within one request.
     */
    public function forget(Model $actor): void
    {
        unset($this->reach[self::key($actor)]);
    }

    /**
     * Forget what every actor reaches.
     */
    public function flush(): void
    {
        $this->reach = [];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    private function walk(Model $actor): array
    {
        $direct = match (true) {
            $actor instanceof CartParticipant => $actor->cartScopes(),
            $actor instanceof CartScope => [$actor],
            default => [],
        };

        $reach = [];

        foreach ($direct as $scope) {
            foreach ($this->chain($scope) as $level) {
                $reach[self::key($level)] = self::pair($level);
            }
        }

        return $reach;
    }

    /**
     * Whether an actor reaches the scope identified by a morph type and key.
     */
    public function reaches(Model $actor, ?string $type, ?string $id): bool
    {
        return $type !== null && $id !== null && isset($this->reach($actor)[$type.'|'.$id]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function pair(Model $model): array
    {
        return [$model->getMorphClass(), (string) $model->getKey()];
    }

    public static function key(Model $model): string
    {
        return implode('|', self::pair($model));
    }
}
