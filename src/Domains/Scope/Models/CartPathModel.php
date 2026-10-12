<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Scope\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Support\Tables;

/**
 * One level of the tree a cart lives in. Depth 0 is the cart's own scope.
 *
 * @property string $id
 * @property string $cart_id
 * @property string $scope_type
 * @property string $scope_id
 * @property int $depth
 * @property-read CartModel $cart
 * @property-read Model|null $scope
 */
final class CartPathModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'scope_type',
        'scope_id',
        'depth',
    ];

    public function getTable(): string
    {
        return Tables::name('cart_paths');
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
    public function scope(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'scope_type', 'scope_id');
    }

    protected function casts(): array
    {
        return [
            'depth' => 'integer',
        ];
    }
}
