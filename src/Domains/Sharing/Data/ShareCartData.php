<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for ShareCartAction: the role to give, one of the cart type's
 * roles. The member is a model, passed to the Action beside it.
 */
final readonly class ShareCartData extends Data
{
    public function __construct(
        public string $role,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'role' => ['required', 'string', 'max:191'],
        ];
    }
}
