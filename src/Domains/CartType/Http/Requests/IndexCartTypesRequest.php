<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartType\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartType\Actions\ListCartTypesAction;
use RefactorCircus\Polycart\Domains\CartType\Http\Resources\CartTypeResource;

final class IndexCartTypesRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', CartModel::class);
    }

    public function persist(): JsonResponse
    {
        return CartTypeResource::collection(app(ListCartTypesAction::class)->execute())->response();
    }
}
