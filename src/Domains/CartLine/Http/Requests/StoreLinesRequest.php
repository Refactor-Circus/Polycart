<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\CartRequest;
use RefactorCircus\Polycart\Domains\CartLine\Actions\AddLinesAction;
use RefactorCircus\Polycart\Domains\CartLine\Data\AddLinesData;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;
use RefactorCircus\Polycart\Domains\CartLine\Resources\CartLineResource;

final class StoreLinesRequest extends CartRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', CartLineModel::class, [$this->cart()]);
    }

    public function rules(): array
    {
        return AddLinesData::rules();
    }

    public function persist(): JsonResponse
    {
        $lines = app(AddLinesAction::class)->execute($this->cart(), AddLinesData::fromValidated($this->validated()));

        return CartLineResource::collection($lines)->response()->setStatusCode(201);
    }
}
