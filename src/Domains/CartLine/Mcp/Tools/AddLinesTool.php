<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Polycart\Domains\CartLine\Actions\AddLinesAction;
use RefactorCircus\Polycart\Domains\CartLine\Mcp\Requests\AddLinesMcpRequest;
use RefactorCircus\Polycart\Domains\CartLine\Resources\CartLineResource;

#[Name('add-lines')]
#[Description('Add one or more lines to a cart, all or nothing. Each line is a purchasable (purchasable_type and purchasable_id) or a custom line described by its meta. Adding something already in the cart with the same options adds to that line. Every line goes through the cart type\'s checks — stock, customer rules, pricing — and if any is refused, none are added and the error names the line by its position and gives a reason code. Prices are integers in minor units (cents), resolved automatically when unit_price is omitted.')]
final class AddLinesTool extends Tool
{
    public function handle(AddLinesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cart' => $schema->string()->description('The cart id.')->required(),
            'lines' => $schema->array()
                ->description(sprintf('The lines to add, 1 to %d.', AddLinesAction::MAX_LINES))
                ->items($schema->object([
                    'purchasable_type' => $schema->string()->description('Purchasable alias from polycart.purchasables. Requires purchasable_id. Omit both for a custom line.'),
                    'purchasable_id' => $schema->string()->description('Key of the purchasable model.'),
                    'quantity' => $schema->integer()->description('How many, at least 1. Defaults to 1.')->min(1),
                    'options' => $schema->object()->description('What is being bought, such as a finish. Part of the line\'s identity.'),
                    'meta' => $schema->object()->description('Anything else the line carries, such as a note.'),
                    'unit_price' => $schema->integer()->description('Unit price in minor units, instead of the resolved price.')->min(0),
                ]))
                ->min(1)
                ->max(AddLinesAction::MAX_LINES)
                ->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return CartLineResource::collectionSchema($schema);
    }
}
