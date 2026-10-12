<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Actions;

use Illuminate\Database\ConnectionInterface;
use RefactorCircus\Polycart\Domains\CartLine\Data\UpdateLineData;
use RefactorCircus\Polycart\Domains\CartLine\Events\LineUpdatedActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Events\LineUpdatingActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;

/**
 * Change a line's quantity, removing it when the quantity reaches zero.
 */
final class UpdateLineAction
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly RemoveLineAction $remove,
    ) {}

    public function execute(CartLineModel $line, UpdateLineData $data): ?CartLineModel
    {
        $cart = $line->cart;

        LineUpdatingActionEvent::dispatch($cart, $line, $data);

        $result = $this->perform($line, $data->quantity, $data->unitPrice);

        LineUpdatedActionEvent::dispatch($cart, $result);

        return $result;
    }

    private function perform(CartLineModel $line, int $quantity, ?int $unitPrice = null): ?CartLineModel
    {
        if ($quantity < 1) {
            $this->remove->execute($line);

            return null;
        }

        $cart = $line->cart;

        $line->quantity = $quantity;
        $line->unit_price = $unitPrice ?? $line->unit_price;

        $cart->cartType()->validate($cart, $line);

        $this->db->transaction(function () use ($line, $cart): void {
            $line->save();

            $cart->extendLifetime();
        });

        $cart->unsetRelation('lines');

        return $line;
    }
}
