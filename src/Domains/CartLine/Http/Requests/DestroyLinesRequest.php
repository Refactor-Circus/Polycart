<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\CartRequest;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;
use RefactorCircus\Polycart\Domains\CartLine\Actions\RemoveLinesAction;
use RefactorCircus\Polycart\Domains\CartLine\Data\RemoveLinesData;

/**
 * The ids may come in the body or the query string, since some clients and
 * proxies drop the body of a DELETE.
 */
final class DestroyLinesRequest extends CartRequest
{
    public function authorize(): bool
    {
        return $this->allowsEach('delete', $this->linesNamed($this->input('lines')));
    }

    public function rules(): array
    {
        return RemoveLinesData::rules();
    }

    public function persist(): JsonResponse
    {
        $cart = app(RemoveLinesAction::class)->execute($this->cart(), RemoveLinesData::fromValidated($this->validated()));

        return (new CartResource($cart))->response();
    }
}
