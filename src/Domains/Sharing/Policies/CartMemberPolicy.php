<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel;
use RefactorCircus\Polycart\Support\Policies\Policy;

/**
 * Seeing who a cart is shared with needs `view` on the cart; sharing it,
 * changing a role or removing someone needs `share`.
 */
class CartMemberPolicy extends Policy
{
    public function viewAny(Model&Authenticatable $user, CartModel $cart): bool
    {
        return $this->allowsOn($user, 'view', $cart);
    }

    public function view(Model&Authenticatable $user, CartMemberModel $member): bool
    {
        return $this->allowsOn($user, 'view', $member->cart);
    }

    public function create(Model&Authenticatable $user, CartModel $cart): bool
    {
        return $this->allowsOn($user, 'share', $cart);
    }

    public function update(Model&Authenticatable $user, CartMemberModel $member): bool
    {
        return $this->allowsOn($user, 'share', $member->cart);
    }

    public function delete(Model&Authenticatable $user, CartMemberModel $member): bool
    {
        return $this->allowsOn($user, 'share', $member->cart);
    }
}
