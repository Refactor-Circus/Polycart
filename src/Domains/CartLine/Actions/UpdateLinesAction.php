<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Actions;

use Illuminate\Database\ConnectionInterface;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Data\LineChangeData;
use RefactorCircus\Polycart\Domains\CartLine\Data\UpdateLinesData;
use RefactorCircus\Polycart\Domains\CartLine\Events\LinesUpdatedActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Events\LinesUpdatingActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Exceptions\LineRejectedException;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;
use RefactorCircus\Polycart\Domains\CartLine\Support\LineLookup;

/**
 * Change several lines' quantities or prices at once, all or nothing.
 *
 * A quantity of zero removes the line. If any change is refused — a line not
 * in this cart, a price the type requires — none are kept, and the refusal
 * names the entry by its position in the list.
 */
final class UpdateLinesAction
{
    public const int MAX_LINES = 100;

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly UpdateLineAction $update,
    ) {}

    public function execute(CartModel $cart, UpdateLinesData $data): CartModel
    {
        LinesUpdatingActionEvent::dispatch($cart, $data);

        $result = $this->perform($cart, $data);

        LinesUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CartModel $cart, UpdateLinesData $data): CartModel
    {
        $this->db->transaction(function () use ($cart, $data): void {
            $lines = LineLookup::lock($cart, array_map(fn (LineChangeData $change): CartLineModel|string => $change->id, $data->lines));

            // The cart's lifetime is extended once, when the batch ends.
            $cart->batchLines(function () use ($data, $lines): void {
                foreach ($data->lines as $index => $change) {
                    try {
                        $this->update->execute($lines->in($change->id), $change->update());
                    } catch (LineRejectedException $e) {
                        throw $e->atLine($index);
                    }
                }
            });
        });

        return $cart->unsetRelation('lines')->load('lines');
    }
}
