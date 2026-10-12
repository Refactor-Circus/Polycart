<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Polycart\Domains\Cart\Actions\MergeCartsAction;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

final class MergeCartsRequest extends CartRequest
{
    public function authorize(): bool
    {
        $into = CartModel::query()->find($this->string('into')->toString());

        // Lines leave one cart and land in the other, so both must be editable.
        return $this->allows('update', $this->cart())
            && (! $into instanceof CartModel || $this->allows('update', $into));
    }

    public function rules(): array
    {
        return [
            'into' => ['required', 'string', 'max:26'],
        ];
    }

    public function persist(): JsonResponse
    {
        /** @var string $into */
        $into = $this->validated('into');

        $cart = app(MergeCartsAction::class)->execute($this->cart(), CartModel::query()->findOrFail($into));

        return (new CartResource($cart->load('lines')))->response();
    }
}
