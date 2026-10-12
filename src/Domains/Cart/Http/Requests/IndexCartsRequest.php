<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Polycart\Domains\Cart\Actions\ListCartsAction;
use RefactorCircus\Polycart\Domains\Cart\Data\ListCartsData;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

final class IndexCartsRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', CartModel::class);
    }

    public function rules(): array
    {
        return ListCartsData::rules();
    }

    public function persist(): JsonResponse
    {
        $carts = app(ListCartsAction::class)->execute(ListCartsData::fromValidated($this->validated()), $this->actor());

        return CartResource::collection($carts)->response();
    }
}
