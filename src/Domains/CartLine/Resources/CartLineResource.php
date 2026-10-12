<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;

/**
 * @mixin CartLineModel
 */
final class CartLineResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'cart_id' => $schema->string()->required(),
            'purchasable_type' => $schema->string()->nullable()->description('Null for a custom line.')->required(),
            'purchasable_id' => $schema->string()->nullable()->required(),
            'quantity' => $schema->integer()->required(),
            'unit_price' => $schema->integer()->nullable()->description('In minor units; null while unpriced.')->required(),
            'total' => $schema->integer()->nullable()->description('In minor units; null while unpriced.')->required(),
            'options' => $schema->object()->nullable()->description('What is being bought. Part of the line\'s identity.')->required(),
            'meta' => $schema->object()->nullable()->required(),
            'position' => $schema->integer()->nullable()->required(),
            'created_at' => $schema->string()->nullable()->required(),
            'updated_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cart_id' => $this->cart_id,
            'purchasable_type' => $this->purchasable_type,
            'purchasable_id' => $this->purchasable_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total' => $this->total(),
            'options' => $this->options,
            'meta' => $this->meta,
            'position' => $this->position,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
