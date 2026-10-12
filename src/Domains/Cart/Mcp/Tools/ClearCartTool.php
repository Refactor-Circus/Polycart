<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Requests\ClearCartMcpRequest;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

#[Name('clear-cart')]
#[Description('Remove every line from a cart.')]
final class ClearCartTool extends Tool
{
    public function handle(ClearCartMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cart' => $schema->string()->description('The cart id.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return CartResource::structuredSchema($schema);
    }
}
