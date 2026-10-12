<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Sharing\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Polycart\Domains\Sharing\Enums\Visibility;

/**
 * Input for SetVisibilityAction: who sees the cart without being shared in.
 */
final readonly class SetVisibilityData extends Data
{
    public function __construct(
        public Visibility $visibility,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'visibility' => ['required', Rule::enum(Visibility::class)],
        ];
    }
}
