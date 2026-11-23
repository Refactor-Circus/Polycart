<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Polycart\Domains\Cart\Http\Resources\CartStatsResource;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Requests\ShowCartStatsMcpRequest;

#[Name('cart-stats')]
#[Description('Count the carts of each registered type, leaving out expired and deleted ones, and show the 5 most recently updated carts with their line counts. Only carts you can access are counted. To find carts by label or id, use list-carts with search.')]
final class CartStatsTool extends Tool
{
    public function handle(ShowCartStatsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return CartStatsResource::structuredSchema($schema);
    }
}
