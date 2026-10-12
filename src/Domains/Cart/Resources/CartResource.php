<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Resources\CartLineResource;
use RefactorCircus\Polycart\Domains\Sharing\Resources\CartMemberResource;

/**
 * @mixin CartModel
 */
final class CartResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'type' => $schema->string()->required(),
            'status' => $schema->string()->nullable()->description('A status of the cart\'s type, or null for a type without statuses.')->required(),
            'source' => $schema->string()->nullable()->description('The surface the cart was created or converted through.')->required(),
            'label' => $schema->string()->nullable()->required(),
            'owner_type' => $schema->string()->nullable()->required(),
            'owner_id' => $schema->string()->nullable()->required(),
            'session_key' => $schema->string()->nullable()->required(),
            'scope_type' => $schema->string()->nullable()->required(),
            'scope_id' => $schema->string()->nullable()->required(),
            'boundary_type' => $schema->string()->nullable()->required(),
            'boundary_id' => $schema->string()->nullable()->required(),
            'visibility' => $schema->string()->enum(['private', 'scope', 'boundary', null])->nullable()->required(),
            'parent_id' => $schema->string()->nullable()->required(),
            'root_id' => $schema->string()->nullable()->required(),
            'meta' => $schema->object()->description('Free-form header data.')->required(),
            'lines_count' => $schema->integer()->description('When counted.'),
            'children_count' => $schema->integer()->description('When counted.'),
            'quantity' => $schema->integer()->description('Units across every line, when the lines are loaded.'),
            'subtotal' => $schema->integer()->description('In minor units, when the lines are loaded.'),
            'fully_priced' => $schema->boolean()->description('Whether every line has a price, when the lines are loaded.'),
            'lines' => $schema->array()->items($schema->object(CartLineResource::schema($schema)))->description('When loaded.'),
            'members' => $schema->array()->items($schema->object(CartMemberResource::schema($schema)))->description('When loaded.'),
            'expires_at' => $schema->string()->nullable()->required(),
            'created_at' => $schema->string()->nullable()->required(),
            'updated_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $linesLoaded = $this->resource->relationLoaded('lines');

        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'source' => $this->source,
            'label' => $this->label,
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'session_key' => $this->session_key,
            'scope_type' => $this->scope_type,
            'scope_id' => $this->scope_id,
            'boundary_type' => $this->boundary_type,
            'boundary_id' => $this->boundary_id,
            'visibility' => $this->visibility?->value,
            'parent_id' => $this->parent_id,
            'root_id' => $this->root_id,
            'meta' => $this->meta ?? [],
            'lines_count' => $this->whenCounted('lines'),
            'children_count' => $this->whenCounted('children'),
            'quantity' => $this->when($linesLoaded, fn (): int => $this->resource->quantity()),
            'subtotal' => $this->when($linesLoaded, fn (): int => $this->resource->subtotal()),
            'fully_priced' => $this->when($linesLoaded, fn (): bool => $this->resource->isFullyPriced()),
            'lines' => $this->whenLoaded('lines', fn (): array => CartLineResource::collection($this->resource->lines)->resolve($request)),
            'members' => $this->whenLoaded('members', fn (): array => CartMemberResource::collection($this->resource->members)->resolve($request)),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
