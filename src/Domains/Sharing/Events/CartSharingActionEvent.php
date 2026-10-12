<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Sharing\Data\ShareCartData;

/**
 * A cart is about to be shared, or a member's role changed.
 */
final class CartSharingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CartModel $cart,
        public Model $member,
        public ShareCartData $data,
    ) {}
}
