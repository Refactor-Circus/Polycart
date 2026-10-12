<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Polycart\Domains\Sharing\Mcp\Requests\UnshareCartMcpRequest;

#[Name('unshare-cart')]
#[Description('Remove a member from a cart. The last member with the top role cannot be removed.')]
final class UnshareCartTool extends Tool
{
    public function handle(UnshareCartMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cart' => $schema->string()->description('The cart id.')->required(),
            'member' => $schema->string()->description('The membership id, from list-members.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'removed' => $schema->string()->description('The id of the membership removed.')->required(),
        ];
    }
}
