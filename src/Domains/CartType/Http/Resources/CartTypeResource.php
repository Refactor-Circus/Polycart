<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartType\Http\Resources;

use BackedEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Polycart\Domains\CartType\Support\CartType;

/**
 * What a cart type allows, so a client can offer only the moves that work.
 *
 * @property CartType $resource
 */
final class CartTypeResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'key' => $schema->string()->required(),
            'statuses' => $schema->array()->items($schema->string())->required(),
            'initial_status' => $schema->string()->nullable()->required(),
            'transitions' => $schema->object()->nullable()->description('Each status mapped to the statuses it may move to; null when any move is allowed.')->required(),
            'converts_to' => $schema->array()->items($schema->string())->required(),
            'parents' => $schema->array()->items($schema->string())->nullable()->description('The types it may nest under; null when any parent, or none, is allowed.')->required(),
            'requires_parent' => $schema->boolean()->required(),
            'requires_price' => $schema->boolean()->required(),
            'merges_lines' => $schema->boolean()->required(),
            'lifetime' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = $this->resource;
        $statuses = $type->statuses();

        return [
            'key' => $type->key(),
            'statuses' => $statuses === null
                ? []
                : array_map(fn (BackedEnum $status): string => (string) $status->value, $statuses::cases()),
            'initial_status' => $type->initialStatus()?->value,
            'transitions' => $type->transitions() === null ? null : array_map(
                fn (array $to): array => array_map(fn (BackedEnum $status): string => (string) $status->value, $to),
                $type->transitions(),
            ),
            'converts_to' => $type->convertsTo(),
            'parents' => $type->parents(),
            'requires_parent' => $type->requiresParent(),
            'requires_price' => $type->requiresPrice(),
            'merges_lines' => $type->mergesLines(),
            'lifetime' => $type->lifetime()?->spec(),
        ];
    }
}
