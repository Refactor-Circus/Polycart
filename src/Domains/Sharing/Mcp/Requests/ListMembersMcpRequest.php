<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Requests\CartRequest;
use RefactorCircus\Polycart\Domains\Sharing\Actions\ListMembersAction;
use RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel;
use RefactorCircus\Polycart\Domains\Sharing\Resources\CartMemberResource;

final class ListMembersMcpRequest extends CartRequest
{
    protected function authorize(): bool
    {
        return $this->allows('viewAny', CartMemberModel::class, [$this->cart()]);
    }

    protected function respond(array $validated): ResponseFactory
    {
        return CartMemberResource::collection(app(ListMembersAction::class)->execute($this->cart()))->toStructuredResponse();
    }
}
