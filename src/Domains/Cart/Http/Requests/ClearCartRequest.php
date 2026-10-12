<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Polycart\Domains\Cart\Actions\ClearCartAction;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

final class ClearCartRequest extends CartRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->cart());
    }

    public function persist(): JsonResponse
    {
        $cart = app(ClearCartAction::class)->execute($this->cart());

        return (new CartResource($cart->load('lines')))->response();
    }
}
