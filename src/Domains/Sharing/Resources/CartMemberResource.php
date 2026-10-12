<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel;

/**
 * @mixin CartMemberModel
 */
final class CartMemberResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'cart_id' => $schema->string()->required(),
            'member_type' => $schema->string()->required(),
            'member_id' => $schema->string()->required(),
            'role' => $schema->string()->required(),
            'created_at' => $schema->string()->nullable()->required(),
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
            'member_type' => $this->member_type,
            'member_id' => $this->member_id,
            'role' => $this->role,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
