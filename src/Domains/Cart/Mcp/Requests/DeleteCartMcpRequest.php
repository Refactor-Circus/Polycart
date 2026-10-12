<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Actions\DeleteCartAction;

final class DeleteCartMcpRequest extends CartRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->cart());
    }

    protected function respond(array $validated): ResponseFactory
    {
        $cart = $this->cart();

        app(DeleteCartAction::class)->execute($cart);

        return Response::structured(['deleted' => $cart->id]);
    }
}
