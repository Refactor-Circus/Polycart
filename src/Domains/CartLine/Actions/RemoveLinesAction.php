<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Actions;

use Illuminate\Database\ConnectionInterface;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Data\RemoveLinesData;
use RefactorCircus\Polycart\Domains\CartLine\Events\LinesRemovedActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Events\LinesRemovingActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Exceptions\LineRejectedException;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;
use RefactorCircus\Polycart\Domains\CartLine\Support\LineLookup;

/**
 * Remove several lines at once, all or nothing.
 *
 * Every line must belong to the cart. If one does not, none are removed and
 * the refusal names it by its position in the list.
 */
final class RemoveLinesAction
{
    public const int MAX_LINES = 100;

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly RemoveLineAction $remove,
    ) {}

    public function execute(CartModel $cart, RemoveLinesData $data): CartModel
    {
        LinesRemovingActionEvent::dispatch($cart, $data);

        $result = $this->perform($cart, $data->lines);

        LinesRemovedActionEvent::dispatch($result, array_map(fn (CartLineModel|string $line): string => $line instanceof CartLineModel ? $line->id : $line, $data->lines));

        return $result;
    }

    /**
     * @param  array<int, CartLineModel|string>  $lines  Lines, or their ids.
     */
    private function perform(CartModel $cart, array $lines): CartModel
    {
        $this->db->transaction(function () use ($cart, $lines): void {
            $found = LineLookup::lock($cart, $lines);

            // The cart's lifetime is extended once, when the batch ends.
            $cart->batchLines(function () use ($lines, $found): void {
                foreach (array_values($lines) as $index => $line) {
                    try {
                        $this->remove->execute($found->in($line));
                    } catch (LineRejectedException $e) {
                        throw $e->atLine($index);
                    }
                }
            });
        });

        return $cart->unsetRelation('lines')->load('lines');
    }
}
