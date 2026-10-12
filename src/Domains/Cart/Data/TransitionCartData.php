<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for TransitionCartAction: the status of the cart's own type to move
 * to, by its value.
 */
final readonly class TransitionCartData extends Data
{
    public function __construct(
        public string $status,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'status' => ['required', 'string', 'max:191'],
        ];
    }
}
