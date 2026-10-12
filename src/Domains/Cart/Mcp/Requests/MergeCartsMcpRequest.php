<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Actions\MergeCartsAction;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

final class MergeCartsMcpRequest extends CartRequest
{
    protected function authorize(): bool
    {
        $into = CartModel::query()->find((string) $this->get('into'));

        // Lines leave one cart and land in the other, so both must be editable.
        return $this->allows('update', $this->cart())
            && (! $into instanceof CartModel || $this->allows('update', $into));
    }

    protected function rules(): array
    {
        return $this->cartRules() + [
            'into' => ['required', 'string', 'max:26'],
        ];
    }

    protected function respond(array $validated): ResponseFactory
    {
        /** @var string $into */
        $into = $validated['into'];

        $cart = app(MergeCartsAction::class)->execute($this->cart(), CartModel::query()->findOrFail($into));

        return (new CartResource($cart->load('lines')))->toStructuredResponse();
    }
}
