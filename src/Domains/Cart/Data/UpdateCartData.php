<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;

/**
 * Input for UpdateCartAction: the label and meta to change. Fields left out
 * are kept; meta, when given, replaces what is there.
 */
final readonly class UpdateCartData extends Data
{
    /**
     * @param  array<string, mixed>|Missing|null  $meta
     */
    public function __construct(
        public string|Missing|null $label = new Missing,
        public array|Missing|null $meta = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'label' => ['sometimes', 'nullable', 'string', 'max:191'],
            'meta' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
