<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartType\Support;

use BackedEnum;
use Carbon\CarbonInterval;
use Closure;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Contracts\PriceResolver;
use RefactorCircus\Polycart\Domains\CartLine\Exceptions\LineRejectedException;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;
use RefactorCircus\Polycart\Domains\CartLine\Support\Stages\BuildLine;
use RefactorCircus\Polycart\Domains\CartLine\Support\Stages\CheckAccepted;
use RefactorCircus\Polycart\Domains\CartLine\Support\Stages\FindMatchingLine;
use RefactorCircus\Polycart\Domains\CartLine\Support\Stages\PrepareLine;
use RefactorCircus\Polycart\Domains\CartLine\Support\Stages\ResolvePrice;
use RefactorCircus\Polycart\Domains\CartLine\Support\Stages\ValidateLine;
use RefactorCircus\Polycart\Domains\CartLine\Support\Stages\WriteLine;
use RefactorCircus\Polycart\Domains\Sharing\Enums\Visibility;

/**
 * Everything that makes one kind of cart behave differently from another.
 *
 * Every cart lives in the same table and is told apart by a string key: a
 * shopping cart, a saved cart, a quote, an order, a project. The key points at
 * a subclass of this one, which is the single place that kind's rules live —
 * its lifecycle, what it may hold, whether its lines need a price, what it may
 * become, and where in a tree it may sit. Nothing outside a type should ever
 * branch on the key itself.
 *
 * Every method has a permissive default, so a type overrides only what makes
 * it different.
 */
abstract class CartType
{
    private string $key = '';

    /**
     * The key this type is registered under, stored in the carts table's `type` column.
     */
    final public function key(): string
    {
        return $this->key;
    }

    /**
     * @internal Set by the registry when it resolves the type.
     */
    final public function withKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    /**
     * The Eloquent model carts of this type are hydrated as.
     *
     * Point this at a subclass of CartModel to give a type its own relations,
     * accessors, and methods. Querying the base CartModel model still returns the
     * subclass for rows of this type.
     *
     * @return class-string<CartModel>
     */
    public function model(): string
    {
        return CartModel::class;
    }

    /**
     * The string-backed enum that holds this type's statuses, if it has any.
     *
     * Each type owns its own lifecycle, so an order's statuses never leak
     * into a quote's. A new cart starts on the enum's first case.
     *
     * @return class-string<BackedEnum>|null
     */
    public function statuses(): ?string
    {
        return null;
    }

    /**
     * The status a new cart of this type starts in.
     */
    public function initialStatus(): ?BackedEnum
    {
        $statuses = $this->statuses();

        return $statuses === null ? null : ($statuses::cases()[0] ?? null);
    }

    /**
     * The statuses each status may move to, keyed by the status's value.
     *
     * Null allows any move between this type's statuses. A status missing
     * from the map is terminal.
     *
     * @return array<string, array<int, BackedEnum>>|null
     */
    public function transitions(): ?array
    {
        return null;
    }

    public function canTransition(?BackedEnum $from, BackedEnum $to): bool
    {
        $statuses = $this->statuses();

        if ($statuses === null || ! $to instanceof $statuses) {
            return false;
        }

        $transitions = $this->transitions();

        if ($transitions === null || $from === null) {
            return true;
        }

        return in_array($to, $transitions[(string) $from->value] ?? [], true);
    }

    /**
     * The statuses a cart in the given status may move to next.
     *
     * @return array<int, BackedEnum>
     */
    public function nextStatuses(?BackedEnum $from): array
    {
        $statuses = $this->statuses();

        if ($statuses === null) {
            return [];
        }

        return array_values(array_filter(
            $statuses::cases(),
            fn (BackedEnum $to): bool => $to !== $from && $this->canTransition($from, $to),
        ));
    }

    /**
     * How long a cart of this type lives after it was last changed.
     *
     * Null means it never expires. Expired carts are not returned as a
     * customer's active cart and are removed by `model:prune`.
     */
    public function lifetime(): ?CarbonInterval
    {
        return null;
    }

    /**
     * The keys of the types a cart of this type may be nested under.
     *
     * Null allows any parent, or none at all. An empty array means a cart of
     * this type is always a root.
     *
     * @return array<int, string>|null
     */
    public function parents(): ?array
    {
        return null;
    }

    /**
     * Whether a cart of this type must be nested under a parent.
     */
    public function requiresParent(): bool
    {
        return false;
    }

    /**
     * The roles a member of a cart of this type can hold, strongest first,
     * with the abilities each grants. `*` grants every ability.
     *
     * The package checks `view`, `update`, `share`, `delete`, `transition`
     * and `convert`. Add your own — a `checkout` a quote has no use for — and
     * check them with `$cart->allows($user, 'checkout')` or
     * `$user->can('checkout', $cart)`.
     *
     * @return array<string, array<int, string>>
     */
    public function roles(): array
    {
        return [
            'owner' => ['*'],
            'editor' => ['view', 'update'],
            'viewer' => ['view'],
        ];
    }

    /**
     * Whether a role grants an ability.
     */
    public function grants(string $role, string $ability): bool
    {
        $abilities = $this->roles()[$role] ?? [];

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    /**
     * The role the creator of a cart of this type starts with.
     */
    public function creatorRole(): string
    {
        return (string) array_key_first($this->roles());
    }

    /**
     * The role visibility gives someone who is not a member.
     */
    public function visibilityRole(): string
    {
        return (string) array_key_last($this->roles());
    }

    /**
     * Who sees a new cart of this type without being shared in.
     */
    public function defaultVisibility(): Visibility
    {
        return Visibility::Private;
    }

    /**
     * Whether a cart of this type may be shared at all.
     */
    public function shareable(): bool
    {
        return true;
    }

    /**
     * The ability needed to move a cart between two statuses.
     *
     * Override it to guard one move harder than the rest, so submitting an
     * order needs `checkout` while cancelling it only needs `transition`.
     */
    public function transitionAbility(?BackedEnum $from, BackedEnum $to): string
    {
        return 'transition';
    }

    /**
     * The ability needed to convert a cart of this type into another type.
     */
    public function conversionAbility(string $to): string
    {
        return 'convert';
    }

    /**
     * The keys of the types a cart of this type may be converted into.
     *
     * @return array<int, string>
     */
    public function convertsTo(): array
    {
        return [];
    }

    /**
     * Called on the *target* type after a cart has been converted into it.
     *
     * The new cart already carries the source's label, meta, and lines. Use
     * this to trim the meta to what this type keeps, stamp a reference, or
     * drop lines this type cannot hold. Changes are saved for you.
     */
    public function convertedFrom(CartModel $cart, CartModel $source): void
    {
        //
    }

    /**
     * Whether a purchasable may be added to a cart of this type.
     *
     * Null is a custom line: one that points at no model, described entirely
     * by its meta.
     */
    public function accepts(?Model $purchasable): bool
    {
        return true;
    }

    /**
     * Whether every line must carry a unit price.
     */
    public function requiresPrice(): bool
    {
        return false;
    }

    /**
     * The unit price, in minor units, of a purchasable added without one.
     *
     * Defers to the bound PriceResolver. Override it when one type prices
     * differently from the rest, such as a list-price cart for a marketplace.
     *
     * @param  array<string, mixed>  $options
     */
    public function price(CartModel $cart, ?Model $purchasable, array $options): ?int
    {
        if (! $purchasable instanceof Model) {
            return null;
        }

        return app(PriceResolver::class)->price($cart, $purchasable, $options);
    }

    /**
     * The stages a line goes through on its way into a cart of this type.
     *
     * Each is a class with `handle(PendingLine $line, Closure $next)`, a
     * container-resolved `Class:arg` string, or a closure. They run in order
     * inside one transaction; any of them can refuse the line with
     * `$line->reject($message, $reason)`, and nothing is written.
     *
     * Override to add your own — stock, credit limits, customer rules — or
     * to reorder or drop the defaults. Start from `parent::addLineStages()`
     * with `self::insertStagesBefore()` to keep the defaults in place.
     *
     * @return array<int, class-string|string|Closure>
     */
    public function addLineStages(): array
    {
        return [
            PrepareLine::class,
            CheckAccepted::class,
            FindMatchingLine::class,
            ResolvePrice::class,
            BuildLine::class,
            ValidateLine::class,
            WriteLine::class,
        ];
    }

    /**
     * Insert stages before an existing one, or at the end when it is absent.
     *
     * @param  array<int, class-string|string|Closure>  $stages
     * @param  array<int, class-string|string|Closure>  $insert
     * @return array<int, class-string|string|Closure>
     */
    protected static function insertStagesBefore(array $stages, string $before, array $insert): array
    {
        $at = array_search($before, $stages, true);

        if ($at === false) {
            return [...$stages, ...$insert];
        }

        array_splice($stages, $at, 0, $insert);

        return $stages;
    }

    /**
     * Insert stages after an existing one, or at the end when it is absent.
     *
     * @param  array<int, class-string|string|Closure>  $stages
     * @param  array<int, class-string|string|Closure>  $insert
     * @return array<int, class-string|string|Closure>
     */
    protected static function insertStagesAfter(array $stages, string $after, array $insert): array
    {
        $at = array_search($after, $stages, true);

        if ($at === false) {
            return [...$stages, ...$insert];
        }

        array_splice($stages, $at + 1, 0, $insert);

        return $stages;
    }

    /**
     * Whether adding something already in the cart adds to that line.
     *
     * When false, every add makes a new line.
     */
    public function mergesLines(): bool
    {
        return true;
    }

    /**
     * Filter or normalise a line's options before they are stored.
     *
     * Called by the PrepareLine stage.
     *
     * Options describe *what* is being bought — a finish, a keying scheme —
     * and are part of the line's identity, so two lines with the same
     * purchasable and different options stay separate.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function prepareOptions(array $options): array
    {
        return $options;
    }

    /**
     * Filter or normalise a line's meta before it is stored.
     *
     * Meta is everything else a line carries — a note, a reference — and
     * does not decide whether two lines merge, except on a custom line.
     *
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public function prepareMeta(array $meta): array
    {
        return $meta;
    }

    /**
     * The identity that decides whether two lines are the same line.
     *
     * A line for a model is identified by the model and its options. A
     * custom line has no model, so its meta is part of its identity too.
     *
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $meta
     */
    public function fingerprint(?Model $purchasable, array $options, array $meta): string
    {
        $identity = $purchasable instanceof Model
            ? [$purchasable->getMorphClass(), (string) $purchasable->getKey(), self::sortKeys($options)]
            : [null, null, self::sortKeys($options), self::sortKeys($meta)];

        return hash('sha256', (string) json_encode($identity));
    }

    /**
     * Reject a line before it is saved.
     *
     * Called when a line is added, when its quantity changes, and for every
     * line carried into this type by a conversion. Throw a
     * LineRejectedException to refuse it.
     */
    public function validate(CartModel $cart, CartLineModel $line): void
    {
        if ($this->requiresPrice() && $line->unit_price === null) {
            throw LineRejectedException::missingPrice($this->key(), $line);
        }
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @return array<array-key, mixed>
     */
    private static function sortKeys(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = self::sortKeys($item);
            }
        }

        return $value;
    }
}
