<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Actions\TransitionCartAction;
use RefactorCircus\Polycart\Domains\Cart\Data\TransitionCartData;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

final class TransitionCartMcpRequest extends CartRequest
{
    protected function authorize(): bool
    {
        return $this->allows('transition', $this->cart(), [(string) $this->get('status')]);
    }

    protected function rules(): array
    {
        return $this->cartRules() + TransitionCartData::rules();
    }

    protected function respond(array $validated): ResponseFactory
    {
        $cart = app(TransitionCartAction::class)->execute($this->cart(), TransitionCartData::fromValidated($validated));

        return (new CartResource($cart))->toStructuredResponse();
    }
}
