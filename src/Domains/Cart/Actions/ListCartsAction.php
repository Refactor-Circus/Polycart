<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Polycart\Domains\Cart\Data\ListCartsData;
use RefactorCircus\Polycart\Domains\Cart\Events\CartsListedActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Events\CartsListingActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Scope\Models\CartPathModel;
use RefactorCircus\Polycart\Support\Morphs;

final class ListCartsAction
{
    public function __construct(private readonly Morphs $morphs) {}

    /**
     * @param  Model|null  $viewer  When given, only carts they can access.
     * @return CursorPaginator<int, CartModel>
     */
    public function execute(ListCartsData $filters = new ListCartsData, ?Model $viewer = null): CursorPaginator
    {
        CartsListingActionEvent::dispatch($filters, $viewer);

        $result = $this->perform($filters, $viewer);

        CartsListedActionEvent::dispatch($result, $viewer);

        return $result;
    }

    /**
     * @param  Model|null  $viewer  When given, only carts they can access.
     * @return CursorPaginator<int, CartModel>
     */
    private function perform(ListCartsData $filters, ?Model $viewer = null): CursorPaginator
    {
        $query = CartModel::query()->withCount('lines')->latest()->latest('id');

        if ($viewer !== null) {
            $query->accessibleBy($viewer);
        }

        if ($filters->scopeType !== null && $filters->scopeId !== null) {
            $scopeType = $this->morphs->scopeMorphClass($filters->scopeType);
            $scopeId = $filters->scopeId;

            $query->whereIn($query->qualifyColumn('id'), CartPathModel::query()
                ->select('cart_id')
                ->where('scope_type', $scopeType)
                ->where('scope_id', $scopeId));
        }

        $columns = [
            'type' => $filters->type,
            'status' => $filters->status,
            'source' => $filters->source,
            'session_key' => $filters->sessionKey,
            'parent_id' => $filters->parent,
            'root_id' => $filters->root,
        ];

        foreach ($columns as $column => $value) {
            if ($value !== null) {
                $query->where($column, $value);
            }
        }

        if ($filters->ownerType !== null && $filters->ownerId !== null) {
            $query->where('owner_type', $this->morphs->ownerMorphClass($filters->ownerType))
                ->where('owner_id', $filters->ownerId);
        }

        if ($filters->search !== null) {
            $search = $filters->search;

            $query->where(fn (Builder $query): Builder => $query
                ->where('label', 'like', '%'.$search.'%')
                ->orWhere('id', 'like', $search.'%'));
        }

        if ($filters->unexpired) {
            $query->unexpired();
        }

        return $query->cursorPaginate(perPage: $filters->perPage, cursor: $filters->cursor);
    }
}
