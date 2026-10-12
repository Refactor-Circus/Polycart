<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Polycart\Domains\Sharing\Mcp\Requests\ListMembersMcpRequest;
use RefactorCircus\Polycart\Domains\Sharing\Resources\CartMemberResource;

#[Name('list-members')]
#[Description('List who a cart is shared with and the role each holds. A member is a person or a whole scope, such as a team.')]
final class ListMembersTool extends Tool
{
    public function handle(ListMembersMcpRequest $request): Response|ResponseFactory
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
        return CartMemberResource::collectionSchema($schema);
    }
}
