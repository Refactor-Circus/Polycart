<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Requests\MergeCartsMcpRequest;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

#[Name('merge-carts')]
#[Description('Move every line of one cart into another, then delete the first. Matching lines add together. Typically a guest\'s cart joining the customer\'s cart.')]
final class MergeCartsTool extends Tool
{
    public function handle(MergeCartsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cart' => $schema->string()->description('The cart id to move lines from. It is deleted.')->required(),
            'into' => $schema->string()->description('The cart id to move lines into.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return CartResource::structuredSchema($schema);
    }
}
