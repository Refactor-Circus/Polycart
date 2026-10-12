<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Support\Tables;

/**
 * One line of a cart: a purchasable, or a custom line described by its meta.
 *
 * Prices are integers in minor units (cents), so totals never drift.
 *
 * @property string $id
 * @property string $cart_id
 * @property string|null $purchasable_type
 * @property string|null $purchasable_id
 * @property string $fingerprint
 * @property int $quantity
 * @property int|null $unit_price
 * @property array<string, mixed> $options
 * @property array<string, mixed> $meta
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CartModel $cart
 * @property-read Model|null $purchasable
 */
final class CartLineModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    protected $fillable = [
        'purchasable_type',
        'purchasable_id',
        'fingerprint',
        'quantity',
        'unit_price',
        'options',
        'meta',
        'position',
    ];

    protected $attributes = [
        'options' => '[]',
        'meta' => '[]',
    ];

    public function getTable(): string
    {
        return Tables::name('cart_lines');
    }

    /**
     * Whether this line points at no purchasable model.
     */
    public function isCustom(): bool
    {
        return $this->purchasable_type === null;
    }

    /**
     * The line total in minor units, or null when the line has no price.
     */
    public function total(): ?int
    {
        return $this->unit_price === null ? null : $this->unit_price * $this->quantity;
    }

    /**
     * @return BelongsTo<CartModel, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(CartModel::class, 'cart_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function purchasable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'purchasable_type', 'purchasable_id');
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'options' => 'array',
            'meta' => 'array',
            'position' => 'integer',
        ];
    }
}
