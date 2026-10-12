<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for ConvertCartAction: the type to convert into, and whether to copy
 * the cart (the default) or retype it in place.
 */
final readonly class ConvertCartData extends Data
{
    public function __construct(
        public string $to,
        public bool $copy = true,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'to' => ['required', 'string', 'max:191'],
            'copy' => ['sometimes', 'boolean'],
        ];
    }
}
