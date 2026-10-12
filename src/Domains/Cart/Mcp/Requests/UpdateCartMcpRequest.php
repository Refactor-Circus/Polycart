<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Actions\UpdateCartAction;
use RefactorCircus\Polycart\Domains\Cart\Data\UpdateCartData;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

final class UpdateCartMcpRequest extends CartRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->cart());
    }

    protected function rules(): array
    {
        return $this->cartRules() + UpdateCartData::rules();
    }

    protected function respond(array $validated): ResponseFactory
    {
        $cart = app(UpdateCartAction::class)->execute($this->cart(), UpdateCartData::fromValidated($validated));

        return (new CartResource($cart))->toStructuredResponse();
    }
}
