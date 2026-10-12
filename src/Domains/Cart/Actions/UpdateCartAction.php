<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Actions;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use RefactorCircus\Polycart\Domains\Cart\Data\UpdateCartData;
use RefactorCircus\Polycart\Domains\Cart\Events\CartUpdatedActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Events\CartUpdatingActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;

/**
 * Change a cart's label or meta.
 *
 * Type, status, and owner are not editable here: type and status change
 * through conversion and transition, which enforce the type's rules.
 */
final class UpdateCartAction
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function execute(CartModel $cart, UpdateCartData $data): CartModel
    {
        CartUpdatingActionEvent::dispatch($cart, $data);

        $result = $this->perform($cart, $data);

        CartUpdatedActionEvent::dispatch($result, array_keys(Arr::only($result->getChanges(), ['label', 'meta'])));

        return $result;
    }

    private function perform(CartModel $cart, UpdateCartData $data): CartModel
    {
        $cart->fill($data->toArray());

        $this->db->transaction(fn (): bool => $cart->save());

        return $cart;
    }
}
