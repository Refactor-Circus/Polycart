<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for UpdateLineAction: the new quantity, where 0 removes the line, and
 * optionally a new unit price. No price keeps the line's own.
 */
final readonly class UpdateLineData extends Data
{
    public function __construct(
        public int $quantity,
        public ?int $unitPrice = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:0'],
            'unit_price' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
