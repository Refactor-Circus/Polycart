<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Http\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Polycart\Domains\Cart\Resources\CartResource;
use RefactorCircus\Polycart\Domains\Cart\Support\CartStats;

/**
 * The carts at a glance: a count per registered type, and the carts updated
 * most recently.
 *
 * @property CartStats $resource
 */
final class CartStatsResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'by_type' => $schema->array()->items($schema->object([
                'type' => $schema->string()->required(),
                'count' => $schema->integer()->description('Carts of the type that are neither expired nor deleted.')->required(),
            ]))->description('One entry per registered type, in registry order.')->required(),
            'recent' => $schema->array()->items($schema->object(CartResource::schema($schema)))
                ->description('Up to 5 carts, most recently updated first, with lines_count and without lines.')
                ->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $stats = $this->resource;

        return [
            'by_type' => array_map(
                fn (int|string $type, int $count): array => ['type' => (string) $type, 'count' => $count],
                array_keys($stats->byType),
                $stats->byType,
            ),
            'recent' => CartResource::collection($stats->recent)->resolve($request),
        ];
    }
}
