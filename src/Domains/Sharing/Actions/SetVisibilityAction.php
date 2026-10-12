<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Actions;

use Illuminate\Database\ConnectionInterface;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Sharing\Data\SetVisibilityData;
use RefactorCircus\Polycart\Domains\Sharing\Enums\Visibility;
use RefactorCircus\Polycart\Domains\Sharing\Events\VisibilityChangedActionEvent;
use RefactorCircus\Polycart\Domains\Sharing\Events\VisibilityChangingActionEvent;
use RefactorCircus\Polycart\Domains\Sharing\Exceptions\SharingException;

/**
 * Widen or narrow who sees a cart without being shared in.
 */
final class SetVisibilityAction
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function execute(CartModel $cart, SetVisibilityData $data): CartModel
    {
        $from = $cart->visibility ?? Visibility::Private;

        VisibilityChangingActionEvent::dispatch($cart, $data);

        $result = $this->perform($cart, $data->visibility);

        VisibilityChangedActionEvent::dispatch($result, $from, $result->visibility ?? Visibility::Private);

        return $result;
    }

    private function perform(CartModel $cart, Visibility $to): CartModel
    {
        $from = $cart->visibility ?? Visibility::Private;

        if ($to !== Visibility::Private) {
            if (! $cart->cartType()->shareable()) {
                throw SharingException::notShareable($cart->type);
            }

            if (! $cart->isScoped()) {
                throw SharingException::unscoped($cart->id);
            }
        }

        if ($from === $to) {
            return $cart;
        }

        $cart->visibility = $to;

        $this->db->transaction(fn (): bool => $cart->save());

        return $cart;
    }
}
