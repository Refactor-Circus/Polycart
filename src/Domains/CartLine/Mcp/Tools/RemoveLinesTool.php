<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;
use RefactorCircus\Polycart\Domains\CartLine\Actions\RemoveLinesAction;
use RefactorCircus\Polycart\Domains\CartLine\Mcp\Requests\RemoveLinesMcpRequest;

#[Name('remove-lines')]
#[Description('Remove one or more lines from a cart, all or nothing. Every line must belong to the cart; if one does not, none are removed. Returns the cart with its remaining lines and totals.')]
final class RemoveLinesTool extends Tool
{
    public function handle(RemoveLinesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cart' => $schema->string()->description('The cart id.')->required(),
            'lines' => $schema->array()
                ->description(sprintf('The line ids to remove, 1 to %d.', RemoveLinesAction::MAX_LINES))
                ->items($schema->string())
                ->min(1)
                ->max(RemoveLinesAction::MAX_LINES)
                ->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return CartResource::structuredSchema($schema);
    }
}
