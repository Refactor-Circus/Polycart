<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Polycart\Domains\Cart\Http\Requests\CartRequest;
use RefactorCircus\Polycart\Domains\Sharing\Actions\ListMembersAction;
use RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel;
use RefactorCircus\Polycart\Domains\Sharing\Resources\CartMemberResource;

final class IndexMembersRequest extends CartRequest
{
    public function authorize(): bool
    {
        return $this->allows('viewAny', CartMemberModel::class, [$this->cart()]);
    }

    public function persist(): JsonResponse
    {
        return CartMemberResource::collection(app(ListMembersAction::class)->execute($this->cart()))->response();
    }
}
