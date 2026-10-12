<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Data;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Polycart\Support\Morphs;

/**
 * Input for AddLineAction: what to add, how many, its options and meta, and
 * a unit price in place of the resolved one. No purchasable adds a custom
 * line.
 *
 * Input names the purchasable by `purchasable_type` (an alias from
 * `polycart.purchasables`) and `purchasable_id`, and it is found
 * through that allowlist (`prepare()`). Code passes the model itself, any model.
 */
final readonly class AddLineData extends Data
{
    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public ?Model $purchasable = null,
        public int $quantity = 1,
        public array $options = [],
        public array $meta = [],
        public ?int $unitPrice = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'purchasable_type' => ['nullable', 'string', 'max:191', 'required_with:purchasable_id'],
            'purchasable_id' => ['nullable', 'string', 'max:191', 'required_with:purchasable_type'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'options' => ['sometimes', 'array'],
            'meta' => ['sometimes', 'array'],
            'unit_price' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Find the purchasable the input names, unless a batch found it already
     * (`AddLinesData::prepare()`).
     */
    protected static function prepare(array $validated): array
    {
        if (($validated['purchasable'] ?? null) instanceof Model) {
            return $validated;
        }

        return [...$validated, 'purchasable' => app(Morphs::class)->purchasableFrom($validated)];
    }
}
