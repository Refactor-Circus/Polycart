<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Actions;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Data\AddLinesData;
use RefactorCircus\Polycart\Domains\CartLine\Events\LinesAddedActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Events\LinesAddingActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Exceptions\LineRejectedException;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;

/**
 * Add several lines to a cart at once, all or nothing.
 *
 * Each line goes through the cart type's add pipeline in turn, inside one
 * transaction. If any is refused, none are written, and the refusal names the
 * line by its position in the list.
 */
final class AddLinesAction
{
    /**
     * The most lines one call may add.
     */
    public const int MAX_LINES = 100;

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly AddLineAction $add,
    ) {}

    /**
     * @return Collection<int, CartLineModel>
     */
    public function execute(CartModel $cart, AddLinesData $data): Collection
    {
        LinesAddingActionEvent::dispatch($cart, $data);

        $result = $this->perform($cart, $data);

        LinesAddedActionEvent::dispatch($cart, $result);

        return $result;
    }

    /**
     * @return Collection<int, CartLineModel>
     */
    private function perform(CartModel $cart, AddLinesData $data): Collection
    {
        // The cart's lifetime is extended once, when the batch ends.
        return $this->db->transaction(fn (): Collection => $cart->batchLines(function () use ($cart, $data): Collection {
            $added = new Collection;

            foreach ($data->lines as $index => $line) {
                try {
                    $added->push($this->add->execute($cart, $line));
                } catch (LineRejectedException $e) {
                    throw $e->atLine($index);
                }
            }

            return $added;
        }));
    }
}
