<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Models;

use BackedEnum;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Keystone\Support\Surface;
use RefactorCircus\Polycart\Domains\Cart\Actions\ClearCartAction;
use RefactorCircus\Polycart\Domains\Cart\Actions\ConvertCartAction;
use RefactorCircus\Polycart\Domains\Cart\Actions\TransitionCartAction;
use RefactorCircus\Polycart\Domains\Cart\Data\ConvertCartData;
use RefactorCircus\Polycart\Domains\Cart\Data\TransitionCartData;
use RefactorCircus\Polycart\Domains\Cart\Exceptions\InvalidParentException;
use RefactorCircus\Polycart\Domains\CartLine\Actions\AddLineAction;
use RefactorCircus\Polycart\Domains\CartLine\Actions\AddLinesAction;
use RefactorCircus\Polycart\Domains\CartLine\Actions\RemoveLineAction;
use RefactorCircus\Polycart\Domains\CartLine\Actions\RemoveLinesAction;
use RefactorCircus\Polycart\Domains\CartLine\Actions\UpdateLineAction;
use RefactorCircus\Polycart\Domains\CartLine\Actions\UpdateLinesAction;
use RefactorCircus\Polycart\Domains\CartLine\Data\AddLineData;
use RefactorCircus\Polycart\Domains\CartLine\Data\AddLinesData;
use RefactorCircus\Polycart\Domains\CartLine\Data\LineChangeData;
use RefactorCircus\Polycart\Domains\CartLine\Data\RemoveLinesData;
use RefactorCircus\Polycart\Domains\CartLine\Data\UpdateLineData;
use RefactorCircus\Polycart\Domains\CartLine\Data\UpdateLinesData;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;
use RefactorCircus\Polycart\Domains\CartType\Services\CartTypeRegistry;
use RefactorCircus\Polycart\Domains\CartType\Support\CartType;
use RefactorCircus\Polycart\Domains\Scope\Contracts\CartScope;
use RefactorCircus\Polycart\Domains\Scope\Exceptions\InvalidScopeException;
use RefactorCircus\Polycart\Domains\Scope\Models\CartPathModel;
use RefactorCircus\Polycart\Domains\Scope\Services\ScopeTree;
use RefactorCircus\Polycart\Domains\Sharing\Actions\SetVisibilityAction;
use RefactorCircus\Polycart\Domains\Sharing\Data\SetVisibilityData;
use RefactorCircus\Polycart\Domains\Sharing\Enums\Visibility;
use RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel;
use RefactorCircus\Polycart\Domains\Sharing\Services\CartAccess;
use RefactorCircus\Polycart\Support\Tables;

/**
 * A cart of any type.
 *
 * Every type shares this table. Rows hydrate as the model their type names,
 * so `CartModel::find($id)` returns a `Quote` for a quote when the quote type
 * points at one. A subclass pins itself to a type by setting `$cartType`:
 * it is then scoped to that type and new instances take it by default.
 *
 * @property string $id
 * @property string $type
 * @property string|null $status
 * @property string|null $source
 * @property string|null $owner_type
 * @property string|null $owner_id
 * @property string|null $session_key
 * @property string|null $parent_id
 * @property string|null $root_id
 * @property string|null $label
 * @property array<string, mixed>|null $meta
 * @property string|null $scope_type
 * @property string|null $scope_id
 * @property string|null $boundary_type
 * @property string|null $boundary_id
 * @property Visibility|null $visibility
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, CartLineModel> $lines
 * @property-read CartModel|null $parent
 * @property-read CartModel|null $root
 * @property-read Collection<int, CartModel> $children
 * @property-read Model|null $owner
 * @property-read Model|null $anchor
 * @property-read Model|null $boundary
 * @property-read Collection<int, CartMemberModel> $members
 * @property-read Collection<int, CartPathModel> $paths
 */
class CartModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;
    use Prunable;
    use SoftDeletes;

    /**
     * The type key a subclass is pinned to, or null for every type.
     */
    protected static ?string $cartType = null;

    /**
     * The tree levels to record once the cart has an id.
     *
     * @var array<int, array{scope_type: string, scope_id: string, depth: int}>
     */
    private array $pendingPaths = [];

    /**
     * How many `batchLines()` calls are open on this instance.
     */
    private int $lineBatchDepth = 0;

    /**
     * Whether a line change inside the open batch asked for a longer lifetime.
     */
    private bool $lifetimeDeferred = false;

    /**
     * The position the next new line takes inside the open batch, once read.
     */
    private ?int $nextLinePosition = null;

    protected $fillable = [
        'type',
        'status',
        'owner_type',
        'owner_id',
        'session_key',
        'parent_id',
        'label',
        'meta',
        'expires_at',
    ];

    public function getTable(): string
    {
        return Tables::name('carts');
    }

    protected static function booted(): void
    {
        $pinned = static::$cartType;

        if ($pinned !== null) {
            static::addGlobalScope('polycart.type', function (Builder $query) use ($pinned): void {
                $query->where($query->qualifyColumn('type'), $pinned);
            });
        }

        static::creating(function (CartModel $cart) use ($pinned): void {
            if ($pinned !== null && ! isset($cart->type)) {
                $cart->type = $pinned;
            }

            $type = $cart->cartType();

            $cart->status ??= self::statusValue($type->initialStatus());
            $cart->source ??= app(Surface::class)->current();
            $cart->expires_at ??= self::expiry($type);
            $cart->visibility ??= $type->defaultVisibility();

            $cart->placeInTree($type);
        });

        static::created(function (CartModel $cart): void {
            $cart->paths()->createMany($cart->pendingPaths);
            $cart->pendingPaths = [];

            if ($cart->owner_type !== null && $cart->owner_id !== null) {
                $cart->members()->create([
                    'member_type' => $cart->owner_type,
                    'member_id' => $cart->owner_id,
                    'role' => $cart->cartType()->creatorRole(),
                ]);
            }
        });

        // A cart is locked to the tree it was created in.
        static::updating(function (CartModel $cart): void {
            if ($cart->isDirty(['scope_type', 'scope_id', 'boundary_type', 'boundary_id'])) {
                throw InvalidScopeException::locked($cart->id);
            }
        });
    }

    /**
     * Hydrate each row as the model its type names.
     *
     * @param  array<string, mixed>|object  $attributes
     * @param  string|null  $connection
     */
    public function newFromBuilder($attributes = [], $connection = null): static
    {
        $attributes = (array) $attributes;

        $key = $attributes['type'] ?? null;
        $class = is_string($key) ? app(CartTypeRegistry::class)->model($key) : null;

        if ($class === null || ! is_subclass_of($class, static::class)) {
            return parent::newFromBuilder($attributes, $connection);
        }

        /** @var static $model */
        $model = (new $class)->newInstance([], true);

        $model->setRawAttributes($attributes, true);
        $model->setConnection($connection ?: $this->getConnectionName());
        $model->fireModelEvent('retrieved', false);

        return $model;
    }

    /**
     * The behaviour of this cart's type.
     */
    public function cartType(): CartType
    {
        return app(CartTypeRegistry::class)->get($this->type);
    }

    /**
     * The current status as the type's enum, or null when it has none.
     */
    public function currentStatus(): ?BackedEnum
    {
        $statuses = $this->cartType()->statuses();

        if ($statuses === null || $this->status === null) {
            return null;
        }

        return $statuses::tryFrom($this->status);
    }

    /**
     * Put a new cart in a scope, recording every level above it.
     *
     * Only possible before the cart is saved: after that, it is locked.
     */
    public function placeIn(CartScope $scope): static
    {
        if ($this->exists) {
            throw InvalidScopeException::locked($this->id);
        }

        if (! $scope instanceof Model) {
            throw InvalidScopeException::notAScope($scope::class);
        }

        $chain = app(ScopeTree::class)->chain($scope);
        // The chain always starts with the scope itself, so it is never empty.
        $top = end($chain) ?: $scope;

        $this->forceFill([
            'scope_type' => $scope->getMorphClass(),
            'scope_id' => (string) $scope->getKey(),
            'boundary_type' => $top->getMorphClass(),
            'boundary_id' => (string) $top->getKey(),
        ]);

        $this->pendingPaths = array_map(fn (Model $level, int $depth): array => [
            'scope_type' => $level->getMorphClass(),
            'scope_id' => (string) $level->getKey(),
            'depth' => $depth,
        ], $chain, array_keys($chain));

        return $this;
    }

    /**
     * Whether the cart lives in a scope of the application's tree.
     */
    public function isScoped(): bool
    {
        return $this->scope_type !== null;
    }

    /**
     * Whether this cart and another live in the same scope.
     */
    public function sharesScopeWith(CartModel $other): bool
    {
        return $this->scope_type === $other->scope_type && $this->scope_id === $other->scope_id;
    }

    /**
     * The role someone holds on this cart, or null when they have no access.
     */
    public function roleFor(Model $actor): ?string
    {
        return app(CartAccess::class)->roleFor($this, $actor);
    }

    /**
     * Whether someone's role on this cart grants an ability.
     */
    public function allows(Model $actor, string $ability): bool
    {
        return app(CartAccess::class)->allows($this, $actor, $ability);
    }

    /**
     * Change who sees this cart without being shared in.
     */
    public function setVisibility(Visibility|string $visibility): static
    {
        app(SetVisibilityAction::class)->execute($this, new SetVisibilityData($visibility instanceof Visibility ? $visibility : Visibility::from($visibility)));

        return $this;
    }

    /**
     * Whether the cart is in the given status.
     */
    public function hasStatus(BackedEnum|string $status): bool
    {
        return $this->status === self::statusValue($status);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Add a purchasable to the cart, or a custom line when it is null.
     *
     * Adding something the cart already holds, with the same options, adds
     * to that line unless the type says otherwise.
     *
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $meta
     */
    public function add(?Model $purchasable, int $quantity = 1, array $options = [], array $meta = [], ?int $unitPrice = null): CartLineModel
    {
        return app(AddLineAction::class)->execute($this, new AddLineData($purchasable, $quantity, $options, $meta, $unitPrice));
    }

    /**
     * Add several lines at once, all or nothing. Each entry takes
     * `purchasable`, `quantity`, `options`, `meta` and `unit_price`.
     *
     * @param  array<int, array{purchasable?: Model|null, quantity?: int, options?: array<string, mixed>, meta?: array<string, mixed>, unit_price?: int|null}>  $lines
     * @return Collection<int, CartLineModel>
     */
    public function addLines(array $lines): Collection
    {
        return app(AddLinesAction::class)->execute($this, new AddLinesData(array_map(fn (array $line): AddLineData => new AddLineData(
            purchasable: $line['purchasable'] ?? null,
            quantity: $line['quantity'] ?? 1,
            options: $line['options'] ?? [],
            meta: $line['meta'] ?? [],
            unitPrice: $line['unit_price'] ?? null,
        ), array_values($lines))));
    }

    /**
     * Change several lines at once, all or nothing. Each entry takes `id`
     * (a line or its id), `quantity` and optionally `unit_price`.
     *
     * @param  array<int, array{id: CartLineModel|string, quantity: int, unit_price?: int|null}>  $changes
     */
    public function updateLines(array $changes): static
    {
        app(UpdateLinesAction::class)->execute($this, new UpdateLinesData(array_map(fn (array $change): LineChangeData => new LineChangeData(
            id: $change['id'],
            quantity: $change['quantity'],
            unitPrice: $change['unit_price'] ?? null,
        ), array_values($changes))));

        return $this;
    }

    /**
     * Remove several lines at once, all or nothing.
     *
     * @param  array<int, CartLineModel|string>  $lines  Lines, or their ids.
     */
    public function removeLines(array $lines): static
    {
        app(RemoveLinesAction::class)->execute($this, new RemoveLinesData(array_values($lines)));

        return $this;
    }

    /**
     * Change a line's quantity. A quantity of zero removes the line.
     */
    public function updateLine(CartLineModel $line, int $quantity, ?int $unitPrice = null): ?CartLineModel
    {
        return app(UpdateLineAction::class)->execute($line, new UpdateLineData($quantity, $unitPrice));
    }

    public function remove(CartLineModel $line): void
    {
        app(RemoveLineAction::class)->execute($line);
    }

    /**
     * Remove every line.
     */
    public function clear(): void
    {
        app(ClearCartAction::class)->execute($this);
    }

    public function transitionTo(BackedEnum|string $status): static
    {
        app(TransitionCartAction::class)->execute($this, new TransitionCartData((string) self::statusValue($status)));

        return $this;
    }

    /**
     * Turn this cart into another type.
     *
     * By default the cart is copied, leaving this one untouched. Pass
     * `copy: false` to retype it in place.
     */
    public function convertTo(string $type, bool $copy = true): CartModel
    {
        return app(ConvertCartAction::class)->execute($this, new ConvertCartData($type, $copy));
    }

    /**
     * The number of units across every line.
     */
    public function quantity(): int
    {
        return (int) $this->lines->sum('quantity');
    }

    /**
     * The sum of every priced line, in minor units.
     */
    public function subtotal(): int
    {
        return (int) $this->lines->sum(fn (CartLineModel $line): int => $line->total() ?? 0);
    }

    /**
     * Whether every line carries a unit price.
     */
    public function isFullyPriced(): bool
    {
        return $this->lines->every(fn (CartLineModel $line): bool => $line->unit_price !== null);
    }

    /**
     * @return HasMany<CartLineModel, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(CartLineModel::class, 'cart_id')->chaperone('cart')->orderBy('position');
    }

    /**
     * @return HasMany<CartMemberModel, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(CartMemberModel::class, 'cart_id')->chaperone('cart');
    }

    /**
     * Every level of the tree this cart lives in, nearest first.
     *
     * @return HasMany<CartPathModel, $this>
     */
    public function paths(): HasMany
    {
        return $this->hasMany(CartPathModel::class, 'cart_id')->orderBy('depth');
    }

    /**
     * The scope this cart lives in, such as a team.
     *
     * @return MorphTo<Model, $this>
     */
    public function anchor(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'scope_type', 'scope_id');
    }

    /**
     * The top of this cart's tree, such as an organization. It can only be
     * shared inside it.
     *
     * @return MorphTo<Model, $this>
     */
    public function boundary(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'boundary_type', 'boundary_id');
    }

    /**
     * The creator: usually a user.
     *
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'owner_type', 'owner_id');
    }

    /**
     * @return BelongsTo<CartModel, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(CartModel::class, 'parent_id');
    }

    /**
     * The top of this cart's tree, or null when this cart is the root.
     *
     * @return BelongsTo<CartModel, $this>
     */
    public function root(): BelongsTo
    {
        return $this->belongsTo(CartModel::class, 'root_id');
    }

    /**
     * @return HasMany<CartModel, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(CartModel::class, 'parent_id');
    }

    /**
     * Every cart beneath this one, at any depth, when this cart is a root.
     *
     * @return HasMany<CartModel, $this>
     */
    public function descendants(): HasMany
    {
        return $this->hasMany(CartModel::class, 'root_id');
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeOfType(Builder $query, string ...$types): void
    {
        $query->whereIn($query->qualifyColumn('type'), $types);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeOwnedBy(Builder $query, Model|string $owner): void
    {
        if (is_string($owner)) {
            $query->where($query->qualifyColumn('session_key'), $owner);

            return;
        }

        $query->where($query->qualifyColumn('owner_type'), $owner->getMorphClass())
            ->where($query->qualifyColumn('owner_id'), (string) $owner->getKey());
    }

    /**
     * Carts anywhere under a scope: `inScope($organization)` includes every
     * team's carts.
     *
     * @param  Builder<static>  $query
     */
    public function scopeInScope(Builder $query, Model $scope): void
    {
        $query->whereIn($query->qualifyColumn('id'), CartPathModel::query()
            ->select('cart_id')
            ->where('scope_type', $scope->getMorphClass())
            ->where('scope_id', (string) $scope->getKey()));
    }

    /**
     * Carts someone can see: as a member, through a scope that is a member,
     * or through the cart's visibility.
     *
     * @param  Builder<static>  $query
     */
    public function scopeAccessibleBy(Builder $query, Model $actor): void
    {
        /** @var Builder<CartModel> $query */
        app(CartAccess::class)->constrain($query, $actor);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeWhereStatus(Builder $query, BackedEnum|string ...$statuses): void
    {
        $query->whereIn(
            $query->qualifyColumn('status'),
            array_map(fn (BackedEnum|string $status): ?string => self::statusValue($status), $statuses),
        );
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeFromSource(Builder $query, BackedEnum|string ...$sources): void
    {
        $query->whereIn(
            $query->qualifyColumn('source'),
            array_map(fn (BackedEnum|string $source): string => $source instanceof BackedEnum ? (string) $source->value : $source, $sources),
        );
    }

    /**
     * Carts that have not expired.
     *
     * @param  Builder<static>  $query
     */
    public function scopeUnexpired(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->whereNull($query->qualifyColumn('expires_at'))
                ->orWhere($query->qualifyColumn('expires_at'), '>', Carbon::now());
        });
    }

    /**
     * Carts past their expiry by more than `polycart.prune_after_days`, and
     * deleted carts once they have been deleted for longer than
     * `polycart.prune_deleted_after_days`, whether their type expires or not.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        /** @var int $grace */
        $grace = config('polycart.prune_after_days', 30);

        /** @var int $deletedGrace */
        $deletedGrace = config('polycart.prune_deleted_after_days', 30);

        return static::query()
            ->withoutGlobalScope('polycart.type')
            ->where(fn (Builder $query): Builder => $query
                ->where(fn (Builder $expired): Builder => $expired
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', Carbon::now()->subDays($grace)))
                ->orWhere(fn (Builder $deleted): Builder => $deleted
                    ->whereNotNull('deleted_at')
                    ->where('deleted_at', '<=', Carbon::now()->subDays($deletedGrace))));
    }

    /**
     * Run several line changes as one batch: the cart's lifetime is
     * extended, and the cart touched, once at the end rather than once per
     * line, and new lines' positions are counted here rather than read back
     * for each line. Call it inside the batch's transaction.
     *
     * @internal
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function batchLines(Closure $callback): mixed
    {
        $outermost = $this->lineBatchDepth === 0;
        $this->lineBatchDepth++;

        try {
            $result = $callback();
        } finally {
            $this->lineBatchDepth--;

            if ($outermost) {
                $this->nextLinePosition = null;
            }
        }

        if ($outermost && $this->lifetimeDeferred) {
            $this->lifetimeDeferred = false;
            $this->extendLifetime();
        }

        return $result;
    }

    /**
     * The position a new line takes: after every line the cart holds.
     *
     * @internal
     */
    public function nextLinePosition(): int
    {
        if ($this->lineBatchDepth === 0) {
            return ((int) $this->lines()->max('position')) + 1;
        }

        $this->nextLinePosition ??= ((int) $this->lines()->max('position')) + 1;

        return $this->nextLinePosition++;
    }

    /**
     * Push the expiry back by the type's lifetime after a change. Inside
     * `batchLines()` it happens once, when the batch ends.
     *
     * @internal
     */
    public function extendLifetime(): void
    {
        if ($this->lineBatchDepth > 0) {
            $this->lifetimeDeferred = true;

            return;
        }

        $expiry = self::expiry($this->cartType());

        if ($expiry !== null) {
            $this->expires_at = $expiry;
        }

        $this->touch();
    }

    /**
     * @internal
     */
    public static function statusValue(BackedEnum|string|null $status): ?string
    {
        return $status instanceof BackedEnum ? (string) $status->value : $status;
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'visibility' => Visibility::class,
            'expires_at' => 'datetime',
        ];
    }

    private static function expiry(CartType $type): ?Carbon
    {
        $lifetime = $type->lifetime();

        return $lifetime === null ? null : Carbon::now()->add($lifetime);
    }

    /**
     * Check the parent against the type and inherit its root.
     */
    private function placeInTree(CartType $type): void
    {
        if ($this->parent_id === null) {
            if ($type->requiresParent()) {
                throw InvalidParentException::required($type->key());
            }

            return;
        }

        $parent = self::query()->withoutGlobalScopes()->findOrFail($this->parent_id);
        $allowed = $type->parents();

        if ($allowed !== null && ! in_array($parent->type, $allowed, true)) {
            throw InvalidParentException::notAllowed($type->key(), $parent->type);
        }

        $this->root_id = $parent->root_id ?? $parent->id;

        // A child lives where its parent lives.
        if (! $this->isScoped() && $parent->isScoped()) {
            $this->forceFill($parent->only(['scope_type', 'scope_id', 'boundary_type', 'boundary_id']));
            $this->pendingPaths = $parent->paths()->get(['scope_type', 'scope_id', 'depth'])
                ->map(fn (CartPathModel $path): array => ['scope_type' => $path->scope_type, 'scope_id' => $path->scope_id, 'depth' => $path->depth])
                ->all();
        }

        if ($this->boundary_type !== $parent->boundary_type || $this->boundary_id !== $parent->boundary_id) {
            throw InvalidScopeException::differentTree($this->id ?? 'new', $parent->id);
        }
    }

    /**
     * Copy another cart's place in the tree onto this unsaved cart.
     *
     * @internal Used when a cart is converted by copy.
     */
    public function inheritPlaceFrom(CartModel $source): void
    {
        $this->forceFill($source->only(['scope_type', 'scope_id', 'boundary_type', 'boundary_id', 'visibility']));
        $this->pendingPaths = $source->paths()->get(['scope_type', 'scope_id', 'depth'])
            ->map(fn (CartPathModel $path): array => ['scope_type' => $path->scope_type, 'scope_id' => $path->scope_id, 'depth' => $path->depth])
            ->all();
    }
}
