<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Actions;

use Illuminate\Database\ConnectionInterface;
use RefactorCircus\Polycart\Domains\CartLine\Events\LineRemovedActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Events\LineRemovingActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;

final class RemoveLineAction
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function execute(CartLineModel $line): void
    {
        $cart = $line->cart;
        $lineId = $line->id;

        LineRemovingActionEvent::dispatch($cart, $line);

        $this->perform($line);

        LineRemovedActionEvent::dispatch($cart, $lineId);
    }

    private function perform(CartLineModel $line): void
    {
        $cart = $line->cart;

        $this->db->transaction(function () use ($line, $cart): void {
            $line->delete();

            $cart->extendLifetime();
        });

        $cart->unsetRelation('lines');
    }
}
