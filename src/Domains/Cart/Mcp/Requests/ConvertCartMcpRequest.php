<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Actions\ConvertCartAction;
use RefactorCircus\Polycart\Domains\Cart\Data\ConvertCartData;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

final class ConvertCartMcpRequest extends CartRequest
{
    protected function authorize(): bool
    {
        return $this->allows('convert', $this->cart(), [(string) $this->get('to')]);
    }

    protected function rules(): array
    {
        return $this->cartRules() + ConvertCartData::rules();
    }

    protected function respond(array $validated): ResponseFactory
    {
        $cart = app(ConvertCartAction::class)->execute($this->cart(), ConvertCartData::fromValidated($validated));

        return (new CartResource($cart->load('lines')))->toStructuredResponse();
    }
}
