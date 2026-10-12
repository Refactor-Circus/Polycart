<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Actions;

use Illuminate\Database\ConnectionInterface;
use RefactorCircus\Polycart\Domains\Cart\Data\TransitionCartData;
use RefactorCircus\Polycart\Domains\Cart\Events\CartTransitionedActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Events\CartTransitioningActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Exceptions\InvalidTransitionException;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;

/**
 * Move a cart to another status of its own type.
 */
final class TransitionCartAction
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function execute(CartModel $cart, TransitionCartData $data): CartModel
    {
        $from = $cart->status;

        CartTransitioningActionEvent::dispatch($cart, $data);

        $result = $this->perform($cart, $data->status);

        CartTransitionedActionEvent::dispatch($result, $from, (string) $result->status);

        return $result;
    }

    private function perform(CartModel $cart, string $value): CartModel
    {
        $type = $cart->cartType();
        $statuses = $type->statuses();

        $to = $statuses === null ? null : $statuses::tryFrom($value);

        if ($statuses === null || ! $to instanceof $statuses) {
            throw InvalidTransitionException::unknownStatus($type->key(), $value);
        }

        if (! $type->canTransition($cart->currentStatus(), $to)) {
            throw InvalidTransitionException::notAllowed($type->key(), $cart->status, $value);
        }

        $from = $cart->status;

        if ($from === $value) {
            return $cart;
        }

        $cart->status = $value;

        $this->db->transaction(fn (): bool => $cart->save());

        return $cart;
    }
}
