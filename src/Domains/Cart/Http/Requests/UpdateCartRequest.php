<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Polycart\Domains\Cart\Actions\UpdateCartAction;
use RefactorCircus\Polycart\Domains\Cart\Data\UpdateCartData;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

final class UpdateCartRequest extends CartRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->cart());
    }

    public function rules(): array
    {
        return UpdateCartData::rules();
    }

    public function persist(): JsonResponse
    {
        $cart = app(UpdateCartAction::class)->execute($this->cart(), UpdateCartData::fromValidated($this->validated()));

        return (new CartResource($cart))->response();
    }
}
