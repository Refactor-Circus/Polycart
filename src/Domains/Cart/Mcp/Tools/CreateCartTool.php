<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Requests\CreateCartMcpRequest;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

#[Name('create-cart')]
#[Description('Start a new cart of a type. Give an owner (owner_type and owner_id) or a guest session_key, or neither. Nest it with parent_id where the type allows.')]
final class CreateCartTool extends Tool
{
    public function handle(CreateCartMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('The type key. See list-cart-types.')->required(),
            'owner_type' => $schema->string()->description('Owner alias from polycart.owners. Requires owner_id.'),
            'owner_id' => $schema->string()->description('Key of the owning model.'),
            'session_key' => $schema->string()->description('A guest session key, instead of an owner.'),
            'label' => $schema->string()->description('A name for the cart.'),
            'meta' => $schema->object()->description('Free-form header data.'),
            'parent_id' => $schema->string()->description('The cart id to nest this cart under.'),
            'scope_type' => $schema->string()->description('Scope alias from polycart.scopes, such as a team. Requires scope_id. The cart is locked to it.'),
            'scope_id' => $schema->string()->description('Key of the scope model.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return CartResource::structuredSchema($schema);
    }
}
