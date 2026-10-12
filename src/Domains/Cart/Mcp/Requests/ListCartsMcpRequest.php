<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Polycart\Domains\Cart\Actions\ListCartsAction;
use RefactorCircus\Polycart\Domains\Cart\Data\ListCartsData;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;
use RefactorCircus\Polycart\Mcp\Request;

final class ListCartsMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', CartModel::class);
    }

    protected function rules(): array
    {
        return ListCartsData::rules();
    }

    protected function respond(array $validated): ResponseFactory
    {
        $carts = app(ListCartsAction::class)->execute(ListCartsData::fromValidated($validated), $this->actor());

        return CartResource::collection($carts)->toStructuredResponse();
    }
}
