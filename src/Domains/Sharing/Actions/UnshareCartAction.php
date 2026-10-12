<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Actions;

use Illuminate\Database\ConnectionInterface;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Sharing\Events\CartUnsharedActionEvent;
use RefactorCircus\Polycart\Domains\Sharing\Events\CartUnsharingActionEvent;
use RefactorCircus\Polycart\Domains\Sharing\Exceptions\SharingException;
use RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel;

/**
 * Take a member off a cart. The last member holding the type's top role
 * cannot be removed, so a cart is never left without an owner.
 */
final class UnshareCartAction
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function execute(CartModel $cart, CartMemberModel $member): void
    {
        $memberType = $member->member_type;
        $memberId = $member->member_id;

        CartUnsharingActionEvent::dispatch($cart, $member);

        $this->perform($cart, $member);

        CartUnsharedActionEvent::dispatch($cart, $memberType, $memberId);
    }

    private function perform(CartModel $cart, CartMemberModel $member): void
    {
        $this->db->transaction(function () use ($cart, $member): void {
            self::guardTopRole($cart, $member);

            $member->delete();
        });
        $cart->unsetRelation('members');
    }

    /**
     * Refuse to demote or remove the last member with the type's top role.
     *
     * @internal
     */
    public static function guardTopRole(CartModel $cart, CartMemberModel $member): void
    {
        $top = $cart->cartType()->creatorRole();

        if ($member->role === $top && $cart->members()->where('role', $top)->count() <= 1) {
            throw SharingException::lastOwner($cart->id);
        }
    }
}
