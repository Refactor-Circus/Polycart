<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Actions;

use Illuminate\Database\ConnectionInterface;
use RefactorCircus\Polycart\Domains\Cart\Events\CartsMergedActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Events\CartsMergingActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Actions\AddLineAction;
use RefactorCircus\Polycart\Domains\CartLine\Data\AddLineData;
use RefactorCircus\Polycart\Domains\Scope\Exceptions\InvalidScopeException;

/**
 * Move every line of one cart into another, then delete the first.
 *
 * The usual case is a guest's session cart joining the customer's cart at
 * login. Lines go through the target's rules like any other add, so matching
 * lines merge and the target type can refuse what it does not accept.
 */
final class MergeCartsAction
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly AddLineAction $add,
    ) {}

    public function execute(CartModel $from, CartModel $into): CartModel
    {
        CartsMergingActionEvent::dispatch($from, $into);

        $result = $this->perform($from, $into);

        CartsMergedActionEvent::dispatch($from, $result);

        return $result;
    }

    private function perform(CartModel $from, CartModel $into): CartModel
    {
        if ($from->is($into)) {
            return $into;
        }

        // An unscoped cart, such as a guest's, can join any cart. A scoped
        // cart's lines stay inside its own scope.
        if ($from->isScoped() && ! $from->sharesScopeWith($into)) {
            throw InvalidScopeException::differentTree($from->id, $into->id);
        }

        $this->db->transaction(function () use ($from, $into): void {
            // The target's lifetime is extended once, after every line.
            $into->batchLines(function () use ($from, $into): void {
                $this->addLines($from, $into);
            });

            $from->delete();
        });

        return $into;
    }

    private function addLines(CartModel $from, CartModel $into): void
    {
        foreach ($from->lines()->with('purchasable')->get() as $line) {
            // The purchasable has since been deleted; there is nothing left to add.
            if (! $line->isCustom() && $line->purchasable === null) {
                continue;
            }

            $this->add->execute($into, new AddLineData(
                purchasable: $line->purchasable,
                quantity: $line->quantity,
                options: $line->options,
                meta: $line->meta,
                unitPrice: $line->unit_price,
            ));
        }
    }
}
