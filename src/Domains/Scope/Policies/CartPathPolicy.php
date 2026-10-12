<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Scope\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Scope\Models\CartPathModel;
use RefactorCircus\Polycart\Support\Policies\Policy;

/**
 * A cart's place in the tree is written by the cart itself when it is
 * created and never changed, so seeing it needs `view` on the cart and no
 * one may create, change or remove a path.
 */
class CartPathPolicy extends Policy
{
    public function viewAny(Model&Authenticatable $user, CartModel $cart): bool
    {
        return $this->allowsOn($user, 'view', $cart);
    }

    public function view(Model&Authenticatable $user, CartPathModel $path): bool
    {
        return $this->allowsOn($user, 'view', $path->cart);
    }

    public function create(Model&Authenticatable $user, CartModel $cart): bool
    {
        return false;
    }

    public function update(Model&Authenticatable $user, CartPathModel $path): bool
    {
        return false;
    }

    public function delete(Model&Authenticatable $user, CartPathModel $path): bool
    {
        return false;
    }
}
