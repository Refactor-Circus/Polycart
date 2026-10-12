<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Requests\CartRequest;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;
use RefactorCircus\Polycart\Domains\CartLine\Actions\RemoveLinesAction;
use RefactorCircus\Polycart\Domains\CartLine\Data\RemoveLinesData;

final class RemoveLinesMcpRequest extends CartRequest
{
    protected function authorize(): bool
    {
        return $this->allowsEach('delete', $this->linesNamed($this->get('lines')));
    }

    protected function rules(): array
    {
        return $this->cartRules() + RemoveLinesData::rules();
    }

    protected function respond(array $validated): ResponseFactory
    {
        $cart = app(RemoveLinesAction::class)->execute($this->cart(), RemoveLinesData::fromValidated($validated));

        return (new CartResource($cart))->toStructuredResponse();
    }
}
