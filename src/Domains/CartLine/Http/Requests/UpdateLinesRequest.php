<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\CartRequest;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;
use RefactorCircus\Polycart\Domains\CartLine\Actions\UpdateLinesAction;
use RefactorCircus\Polycart\Domains\CartLine\Data\UpdateLinesData;

final class UpdateLinesRequest extends CartRequest
{
    public function authorize(): bool
    {
        return $this->allowsEach('update', $this->linesNamed(data_get($this->input('lines'), '*.id')));
    }

    public function rules(): array
    {
        return UpdateLinesData::rules();
    }

    public function persist(): JsonResponse
    {
        $cart = app(UpdateLinesAction::class)->execute($this->cart(), UpdateLinesData::fromValidated($this->validated()));

        return (new CartResource($cart))->response();
    }
}
