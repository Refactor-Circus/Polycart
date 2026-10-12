<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Requests\ConvertCartMcpRequest;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

#[Name('convert-cart')]
#[Description('Turn a cart into another type, such as a cart into a quote. The source type must list the target in converts_to. By default the cart is copied; set copy to false to retype it in place. Every line must meet the target type\'s rules, or nothing changes.')]
final class ConvertCartTool extends Tool
{
    public function handle(ConvertCartMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cart' => $schema->string()->description('The cart id.')->required(),
            'to' => $schema->string()->description('The type key to convert into.')->required(),
            'copy' => $schema->boolean()->description('Copy the cart (true, the default) or retype it in place (false).'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return CartResource::structuredSchema($schema);
    }
}
