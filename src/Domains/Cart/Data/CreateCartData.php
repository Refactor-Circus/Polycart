<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for CreateCartAction: the type, and optionally a label, header meta
 * and the cart to nest under. The owner and scope are models, passed to the
 * Action beside it.
 */
final readonly class CreateCartData extends Data
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function __construct(
        public string $type,
        public ?string $label = null,
        public ?array $meta = null,
        public ?string $parentId = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:191'],
            'label' => ['sometimes', 'nullable', 'string', 'max:191'],
            'meta' => ['sometimes', 'nullable', 'array'],
            'parent_id' => ['sometimes', 'nullable', 'string', 'max:26'],
        ];
    }
}
