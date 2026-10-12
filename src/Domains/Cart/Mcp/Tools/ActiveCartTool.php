<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Requests\ActiveCartMcpRequest;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

#[Name('active-cart')]
#[Description('Get an owner\'s current cart of a type, starting one if they have none. A cart is current while it has not expired and is still in its type\'s first status.')]
final class ActiveCartTool extends Tool
{
    public function handle(ActiveCartMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('The type key.')->required(),
            'owner_type' => $schema->string()->description('Owner alias from polycart.owners. Requires owner_id.'),
            'owner_id' => $schema->string()->description('Key of the owning model.'),
            'session_key' => $schema->string()->description('A guest session key, instead of an owner.'),
            'scope_type' => $schema->string()->description('Scope alias from polycart.scopes. Each scope has its own active cart.'),
            'scope_id' => $schema->string()->description('Key of the scope model.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return CartResource::structuredSchema($schema);
    }
}
