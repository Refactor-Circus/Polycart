<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Actions\ClearCartAction;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

final class ClearCartMcpRequest extends CartRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->cart());
    }

    protected function respond(array $validated): ResponseFactory
    {
        $cart = app(ClearCartAction::class)->execute($this->cart());

        return (new CartResource($cart->load('lines')))->toStructuredResponse();
    }
}
