<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Requests\DeleteCartMcpRequest;

#[Name('delete-cart')]
#[Description('Delete a cart. It is soft-deleted, and removed for good when it is pruned.')]
final class DeleteCartTool extends Tool
{
    public function handle(DeleteCartMcpRequest $request): Response|ResponseFactory
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
        return [
            'deleted' => $schema->string()->description('The id of the deleted cart.')->required(),
        ];
    }
}
