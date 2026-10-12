<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Actions;

use Illuminate\Database\Eloquent\Collection;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Sharing\Events\MembersListedActionEvent;
use RefactorCircus\Polycart\Domains\Sharing\Events\MembersListingActionEvent;
use RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel;

final class ListMembersAction
{
    /**
     * @return Collection<int, CartMemberModel>
     */
    public function execute(CartModel $cart): Collection
    {
        MembersListingActionEvent::dispatch($cart);

        $result = $this->perform($cart);

        MembersListedActionEvent::dispatch($cart, $result);

        return $result;
    }

    /**
     * @return Collection<int, CartMemberModel>
     */
    private function perform(CartModel $cart): Collection
    {
        return $cart->members()->oldest()->get();
    }
}
