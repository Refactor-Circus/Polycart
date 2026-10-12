<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;
use RefactorCircus\Polycart\Support\Policies\Policy;

/**
 * Lines are cart content, so each check defers to the cart: reading a line
 * needs `view` on its cart, changing one needs `update`.
 */
class CartLinePolicy extends Policy
{
    public function viewAny(Model&Authenticatable $user, CartModel $cart): bool
    {
        return $this->allowsOn($user, 'view', $cart);
    }

    public function view(Model&Authenticatable $user, CartLineModel $line): bool
    {
        return $this->allowsOn($user, 'view', $line->cart);
    }

    public function create(Model&Authenticatable $user, CartModel $cart): bool
    {
        return $this->allowsOn($user, 'update', $cart);
    }

    public function update(Model&Authenticatable $user, CartLineModel $line): bool
    {
        return $this->allowsOn($user, 'update', $line->cart);
    }

    public function delete(Model&Authenticatable $user, CartLineModel $line): bool
    {
        return $this->allowsOn($user, 'update', $line->cart);
    }
}
