<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Requests\CartRequest;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;
use RefactorCircus\Polycart\Domains\CartLine\Actions\UpdateLinesAction;
use RefactorCircus\Polycart\Domains\CartLine\Data\UpdateLinesData;

final class UpdateLinesMcpRequest extends CartRequest
{
    protected function authorize(): bool
    {
        return $this->allowsEach('update', $this->linesNamed(data_get($this->get('lines'), '*.id')));
    }

    protected function rules(): array
    {
        return $this->cartRules() + UpdateLinesData::rules();
    }

    protected function respond(array $validated): ResponseFactory
    {
        $cart = app(UpdateLinesAction::class)->execute($this->cart(), UpdateLinesData::fromValidated($validated));

        return (new CartResource($cart))->toStructuredResponse();
    }
}
