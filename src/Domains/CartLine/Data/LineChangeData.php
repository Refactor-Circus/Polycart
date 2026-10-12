<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;

/**
 * One entry of UpdateLinesData: the line, or its id, and the fields of a
 * single update.
 */
final readonly class LineChangeData extends Data
{
    public function __construct(
        public CartLineModel|string $id,
        public int $quantity,
        public ?int $unitPrice = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'id' => ['required', 'string', 'max:26'],
        ] + UpdateLineData::rules();
    }

    /**
     * The change to the line, as UpdateLineAction takes it.
     */
    public function update(): UpdateLineData
    {
        return new UpdateLineData($this->quantity, $this->unitPrice);
    }
}
