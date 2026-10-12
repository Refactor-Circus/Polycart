<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Polycart\Domains\Cart\Actions\TransitionCartAction;
use RefactorCircus\Polycart\Domains\Cart\Data\TransitionCartData;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

final class TransitionCartRequest extends CartRequest
{
    public function authorize(): bool
    {
        return $this->allows('transition', $this->cart(), [$this->string('status')->toString()]);
    }

    public function rules(): array
    {
        return TransitionCartData::rules();
    }

    public function persist(): JsonResponse
    {
        $cart = app(TransitionCartAction::class)->execute($this->cart(), TransitionCartData::fromValidated($this->validated()));

        return (new CartResource($cart))->response();
    }
}
