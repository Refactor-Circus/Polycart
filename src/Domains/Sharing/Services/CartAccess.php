<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Scope\Services\ScopeTree;
use RefactorCircus\Polycart\Domains\Sharing\Enums\Visibility;
use RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel;

/**
 * Decides what someone may do with a cart.
 *
 * Someone's role on a cart is the strongest of:
 *
 * - their own membership,
 * - the membership of any scope they reach, such as a team shared in, and
 * - the cart type's visibility role, when the cart's visibility covers a
 *   scope they reach.
 *
 * Roles and the abilities they grant come from the cart's type. What each
 * actor reaches is read once per request (ScopeTree), so a team change shows
 * on the next request, or at once after `ScopeTree::forget($actor)`.
 */
final class CartAccess
{
    public function __construct(private readonly ScopeTree $tree) {}

    public function roleFor(CartModel $cart, Model $actor): ?string
    {
        $type = $cart->cartType();
        $reach = $this->tree->reach($actor);
        $self = ScopeTree::key($actor);

        $candidates = $cart->members
            ->filter(fn (CartMemberModel $member): bool => ($key = $member->member_type.'|'.$member->member_id) === $self || isset($reach[$key]))
            ->pluck('role')
            ->all();

        if ($this->visible($cart, $reach)) {
            $candidates[] = $type->visibilityRole();
        }

        // Roles are listed strongest first, so the earliest match wins.
        foreach (array_keys($type->roles()) as $role) {
            if (in_array($role, $candidates, true)) {
                return $role;
            }
        }

        return null;
    }

    public function allows(CartModel $cart, Model $actor, string $ability): bool
    {
        $role = $this->roleFor($cart, $actor);

        return $role !== null && $cart->cartType()->grants($role, $ability);
    }

    /**
     * Limit a query to the carts an actor can see.
     *
     * @param  Builder<CartModel>  $query
     */
    public function constrain(Builder $query, Model $actor): void
    {
        $reach = array_values($this->tree->reach($actor));
        [$type, $id] = ScopeTree::pair($actor);

        // Memberships are found through their (member_type, member_id) index
        // rather than checked cart by cart.
        $memberships = CartMemberModel::query()
            ->select('cart_id')
            ->where(function (Builder $members) use ($reach, $type, $id): void {
                $members->where(fn (Builder $member): Builder => $member->where('member_type', $type)->where('member_id', $id));

                foreach ($reach as [$scopeType, $scopeId]) {
                    $members->orWhere(fn (Builder $member): Builder => $member->where('member_type', $scopeType)->where('member_id', $scopeId));
                }
            });

        $query->where(function (Builder $query) use ($reach, $memberships): void {
            $query->whereIn($query->qualifyColumn('id'), $memberships);

            foreach ($reach as [$scopeType, $scopeId]) {
                $query->orWhere(fn (Builder $cart): Builder => $cart
                    ->where('visibility', Visibility::Scope->value)
                    ->where('scope_type', $scopeType)
                    ->where('scope_id', $scopeId));

                $query->orWhere(fn (Builder $cart): Builder => $cart
                    ->where('visibility', Visibility::Boundary->value)
                    ->where('boundary_type', $scopeType)
                    ->where('boundary_id', $scopeId));
            }
        });
    }

    /**
     * @param  array<string, array{0: string, 1: string}>  $reach
     */
    private function visible(CartModel $cart, array $reach): bool
    {
        return match ($cart->visibility) {
            Visibility::Scope => isset($reach[$cart->scope_type.'|'.$cart->scope_id]),
            Visibility::Boundary => isset($reach[$cart->boundary_type.'|'.$cart->boundary_id]),
            default => false,
        };
    }
}
