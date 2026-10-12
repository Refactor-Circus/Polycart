<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Polycart\Domains\Cart\Data\UpdateCartData;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;

/**
 * A cart's label or meta is about to change.
 */
final class CartUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CartModel $cart,
        public UpdateCartData $data,
    ) {}
}
