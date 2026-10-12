<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;

/**
 * Input for ListCartsAction: the filters, each optional, and the page.
 * Owners and scopes are named by their alias from `polycart.owners` and
 * `polycart.scopes`, or their morph class.
 */
final readonly class ListCartsData extends Data
{
    public function __construct(
        public ?string $type = null,
        public ?string $status = null,
        public ?string $source = null,
        public ?string $ownerType = null,
        public ?string $ownerId = null,
        public ?string $sessionKey = null,
        public ?string $parent = null,
        public ?string $root = null,
        public ?string $scopeType = null,
        public ?string $scopeId = null,
        public ?string $search = null,
        public bool $unexpired = false,
        public int $perPage = 25,
        public ?string $cursor = null,
    ) {}

    /**
     * How the list pages; its tool states the same limits.
     */
    public static function pagination(): Pagination
    {
        return Pagination::cursor(default: 25, max: 200);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['sometimes', 'nullable', 'string', 'max:191'],
            'status' => ['sometimes', 'nullable', 'string', 'max:191'],
            'source' => ['sometimes', 'nullable', 'string', 'max:191'],
            'owner_type' => ['nullable', 'string', 'max:191', 'required_with:owner_id'],
            'owner_id' => ['nullable', 'string', 'max:191', 'required_with:owner_type'],
            'session_key' => ['sometimes', 'nullable', 'string', 'max:191'],
            'parent' => ['sometimes', 'nullable', 'string', 'max:26'],
            'root' => ['sometimes', 'nullable', 'string', 'max:26'],
            'scope_type' => ['nullable', 'string', 'max:191', 'required_with:scope_id'],
            'scope_id' => ['nullable', 'string', 'max:191', 'required_with:scope_type'],
            'search' => ['sometimes', 'nullable', 'string', 'max:191'],
            'unexpired' => ['sometimes', 'boolean'],
        ] + self::pagination()->rules();
    }
}
