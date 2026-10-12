<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartType\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Polycart\Domains\CartType\Http\Resources\CartTypeResource;
use RefactorCircus\Polycart\Domains\CartType\Mcp\Requests\ListCartTypesMcpRequest;

#[Name('list-cart-types')]
#[Description('List every cart type and what it allows: its statuses and allowed moves, the types it converts into, its allowed parents, and whether its lines need a price. Read this before transitioning or converting a cart.')]
final class ListCartTypesTool extends Tool
{
    public function handle(ListCartTypesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return CartTypeResource::collectionSchema($schema);
    }
}
