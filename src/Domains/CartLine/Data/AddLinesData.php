<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Data;

use RefactorCircus\Keystone\Data\Attributes\ListOf;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Polycart\Domains\CartLine\Actions\AddLinesAction;
use RefactorCircus\Polycart\Support\Morphs;

/**
 * Input for AddLinesAction: the lines to add, in order, each as a single add
 * takes it.
 */
final readonly class AddLinesData extends Data
{
    /**
     * @param  list<AddLineData>  $lines
     */
    public function __construct(
        #[ListOf(AddLineData::class)]
        public array $lines,
    ) {}

    /**
     * Each line takes the same fields as a single add, under `lines.*`.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        $rules = ['lines' => ['required', 'array', 'list', 'min:1', 'max:'.AddLinesAction::MAX_LINES]];

        foreach (AddLineData::rules() as $field => $fieldRules) {
            $rules['lines.*.'.$field] = array_map(
                fn (mixed $rule): mixed => is_string($rule) && str_starts_with($rule, 'required_with:')
                    ? 'required_with:lines.*.'.substr($rule, strlen('required_with:'))
                    : $rule,
                $fieldRules,
            );
        }

        return $rules;
    }

    /**
     * Find every line's purchasable up front, one query per kind of
     * purchasable rather than one per line.
     */
    protected static function prepare(array $validated): array
    {
        $lines = $validated['lines'] ?? null;

        if (! is_array($lines)) {
            return $validated;
        }

        $purchasables = app(Morphs::class)->purchasablesFrom($lines);

        foreach ($lines as $index => $line) {
            if (is_array($line)) {
                $lines[$index] = [...$line, 'purchasable' => $purchasables[$index] ?? null];
            }
        }

        return [...$validated, 'lines' => $lines];
    }
}
