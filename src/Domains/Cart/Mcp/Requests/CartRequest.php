<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Requests;

use Illuminate\Database\Eloquent\Collection;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;
use RefactorCircus\Polycart\Mcp\Request;

abstract class CartRequest extends Request
{
    /**
     * The cart the call names; requests with input add their Data's rules.
     */
    protected function rules(): array
    {
        return $this->cartRules();
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function cartRules(): array
    {
        return ['cart' => ['required', 'string', 'max:26']];
    }

    protected function cart(): CartModel
    {
        /** @var string $id */
        $id = $this->get('cart');

        return CartModel::query()->findOrFail($id);
    }

    /**
     * The lines of this cart a call names, so each can be checked against
     * its policy. Ids that are not this cart's lines are left for the
     * action to reject.
     *
     * @return Collection<int, CartLineModel>
     */
    protected function linesNamed(mixed $ids): Collection
    {
        $ids = array_values(array_filter(is_array($ids) ? $ids : [], is_string(...)));

        return $this->cart()->lines()->whereIn('id', $ids)->get();
    }
}
