<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\CartRequest;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;
use RefactorCircus\Polycart\Domains\Sharing\Actions\SetVisibilityAction;
use RefactorCircus\Polycart\Domains\Sharing\Data\SetVisibilityData;

final class UpdateVisibilityRequest extends CartRequest
{
    public function authorize(): bool
    {
        return $this->allows('share', $this->cart());
    }

    public function rules(): array
    {
        return SetVisibilityData::rules();
    }

    public function persist(): JsonResponse
    {
        $cart = app(SetVisibilityAction::class)->execute($this->cart(), SetVisibilityData::fromValidated($this->validated()));

        return (new CartResource($cart))->response();
    }
}
