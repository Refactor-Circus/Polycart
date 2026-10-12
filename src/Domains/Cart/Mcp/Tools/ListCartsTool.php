<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Polycart\Domains\Cart\Data\ListCartsData;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Requests\ListCartsMcpRequest;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;

#[Name('list-carts')]
#[Description('List carts, newest first. Filter by type, status, owner, guest session key, parent, root, or a label search. Cursor paginated.')]
final class ListCartsTool extends Tool
{
    public function handle(ListCartsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('Only carts of this type key.'),
            'status' => $schema->string()->description('Only carts in this status.'),
            'source' => $schema->string()->description('Only carts created through this surface: http (the JSON API), mcp, cortex, atrium, cli, code, or one the application names.'),
            'owner_type' => $schema->string()->description('Owner alias from polycart.owners, or its morph class. Requires owner_id.'),
            'owner_id' => $schema->string()->description('Key of the owning model. Requires owner_type.'),
            'session_key' => $schema->string()->description('Only the guest carts for this session key.'),
            'parent' => $schema->string()->description('Only carts nested directly under this cart id.'),
            'root' => $schema->string()->description('Only carts anywhere in the tree under this root cart id.'),
            'search' => $schema->string()->description('Match against the label, or the start of the id.'),
            'unexpired' => $schema->boolean()->description('Leave out expired carts.'),
            'scope_type' => $schema->string()->description('Only carts anywhere under this scope alias. Requires scope_id.'),
            'scope_id' => $schema->string()->description('Key of the scope model.'),
        ] + ListCartsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return CartResource::collectionSchema($schema, ListCartsData::pagination());
    }
}
