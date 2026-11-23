<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Support;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;

/**
 * What ShowCartStatsAction read: the unexpired carts counted per registered
 * type, and the most recently updated carts.
 */
final readonly class CartStats
{
    /**
     * @param  array<string, int>  $byType  Each registered type's count, in registry order.
     * @param  EloquentCollection<int, CartModel>  $recent  Newest first, with `lines_count`.
     */
    public function __construct(
        public array $byType,
        public EloquentCollection $recent,
    ) {}
}
