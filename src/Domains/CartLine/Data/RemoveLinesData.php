<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Polycart\Domains\CartLine\Actions\RemoveLinesAction;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;

/**
 * Input for RemoveLinesAction: the lines to remove, or their ids.
 */
final readonly class RemoveLinesData extends Data
{
    /**
     * @param  list<CartLineModel|string>  $lines
     */
    public function __construct(
        public array $lines,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'lines' => ['required', 'array', 'list', 'min:1', 'max:'.RemoveLinesAction::MAX_LINES],
            'lines.*' => ['required', 'string', 'max:26', 'distinct'],
        ];
    }
}
