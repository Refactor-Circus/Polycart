<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Actions;

use RefactorCircus\Polycart\Domains\Cart\Events\CartShowingActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Events\CartShownActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;

final class ShowCartAction
{
    /**
     * The cart with its lines, members, and the size of the tree beneath it.
     */
    public function execute(CartModel $cart): CartModel
    {
        CartShowingActionEvent::dispatch($cart);

        $result = $this->perform($cart);

        CartShownActionEvent::dispatch($result);

        return $result;
    }

    /**
     * The cart with its lines, members, and the size of the tree beneath it.
     */
    private function perform(CartModel $cart): CartModel
    {
        return $cart->load(['lines', 'members'])->loadCount('children');
    }
}
