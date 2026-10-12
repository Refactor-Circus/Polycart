<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;
use RefactorCircus\Polycart\Domains\Sharing\Mcp\Requests\SetVisibilityMcpRequest;

#[Name('set-visibility')]
#[Description('Change who sees a cart without being shared in: private (members only), scope (everyone in the cart\'s scope, such as its team), or boundary (everyone in its organization).')]
final class SetVisibilityTool extends Tool
{
    public function handle(SetVisibilityMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cart' => $schema->string()->description('The cart id.')->required(),
            'visibility' => $schema->string()->enum(['private', 'scope', 'boundary'])->description('Who can see the cart.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return CartResource::structuredSchema($schema);
    }
}
