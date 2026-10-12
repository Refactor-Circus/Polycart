<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartType\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartType\Actions\ListCartTypesAction;
use RefactorCircus\Polycart\Domains\CartType\Http\Resources\CartTypeResource;
use RefactorCircus\Polycart\Mcp\Request;

final class ListCartTypesMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', CartModel::class);
    }

    protected function respond(array $validated): ResponseFactory
    {
        return CartTypeResource::collection(app(ListCartTypesAction::class)->execute())->toStructuredResponse();
    }
}
