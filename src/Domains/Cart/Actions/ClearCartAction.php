<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Actions;

use Illuminate\Database\ConnectionInterface;
use RefactorCircus\Polycart\Domains\Cart\Events\CartClearedActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Events\CartClearingActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Actions\RemoveLineAction;

/**
 * Remove every line from a cart.
 */
final class ClearCartAction
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly RemoveLineAction $remove,
    ) {}

    public function execute(CartModel $cart): CartModel
    {
        CartClearingActionEvent::dispatch($cart);

        $result = $this->perform($cart);

        CartClearedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CartModel $cart): CartModel
    {
        // All or nothing: a removal that fails puts every line back.
        // Each line comes back holding this cart, whose lifetime is
        // extended once when the batch ends.
        $this->db->transaction(function () use ($cart): void {
            $cart->batchLines(function () use ($cart): void {
                foreach ($cart->lines()->get() as $line) {
                    $this->remove->execute($line);
                }
            });
        });

        return $cart->unsetRelation('lines');
    }
}
