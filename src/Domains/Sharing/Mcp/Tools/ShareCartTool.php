<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Polycart\Domains\Sharing\Mcp\Requests\ShareCartMcpRequest;
use RefactorCircus\Polycart\Domains\Sharing\Resources\CartMemberResource;

#[Name('share-cart')]
#[Description('Share a cart with a person or a whole scope, such as a team, inside the cart\'s boundary. Sharing with an existing member changes their role. Roles come from the cart\'s type: see list-cart-types.')]
final class ShareCartTool extends Tool
{
    public function handle(ShareCartMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cart' => $schema->string()->description('The cart id.')->required(),
            'member_type' => $schema->string()->description('Alias from polycart.owners (a person) or polycart.scopes (a team).')->required(),
            'member_id' => $schema->string()->description('Key of the member model.')->required(),
            'role' => $schema->string()->description('One of the cart type\'s roles.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return CartMemberResource::structuredSchema($schema);
    }
}
