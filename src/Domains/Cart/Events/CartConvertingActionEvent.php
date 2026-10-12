<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Polycart\Domains\Cart\Data\ConvertCartData;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;

/**
 * A cart is about to be converted into another type.
 */
final class CartConvertingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CartModel $cart,
        public ConvertCartData $data,
    ) {}
}
