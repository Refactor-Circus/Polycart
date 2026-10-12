<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Data;

use RefactorCircus\Keystone\Data\Attributes\ListOf;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Polycart\Domains\CartLine\Actions\UpdateLinesAction;

/**
 * Input for UpdateLinesAction: the changes, in order, each naming a line by
 * its id.
 */
final readonly class UpdateLinesData extends Data
{
    /**
     * @param  list<LineChangeData>  $lines
     */
    public function __construct(
        #[ListOf(LineChangeData::class)]
        public array $lines,
    ) {}

    /**
     * Each entry names a line by `id` and takes the fields of a single update.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        $rules = ['lines' => ['required', 'array', 'list', 'min:1', 'max:'.UpdateLinesAction::MAX_LINES]];

        foreach (LineChangeData::rules() as $field => $fieldRules) {
            $rules['lines.*.'.$field] = $fieldRules;
        }

        return $rules;
    }
}
