<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Requests\CartRequest;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;
use RefactorCircus\Polycart\Domains\Sharing\Actions\SetVisibilityAction;
use RefactorCircus\Polycart\Domains\Sharing\Data\SetVisibilityData;

final class SetVisibilityMcpRequest extends CartRequest
{
    protected function authorize(): bool
    {
        return $this->allows('share', $this->cart());
    }

    protected function rules(): array
    {
        return $this->cartRules() + SetVisibilityData::rules();
    }

    protected function respond(array $validated): ResponseFactory
    {
        $cart = app(SetVisibilityAction::class)->execute($this->cart(), SetVisibilityData::fromValidated($validated));

        return (new CartResource($cart))->toStructuredResponse();
    }
}
