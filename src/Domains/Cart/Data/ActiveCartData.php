<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for ActiveCartAction: the type of cart wanted. The owner and scope
 * are models, passed to the Action beside it.
 */
final readonly class ActiveCartData extends Data
{
    public function __construct(
        public string $type,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:191'],
        ];
    }
}
