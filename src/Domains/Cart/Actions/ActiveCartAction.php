<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Actions;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Polycart\Domains\Cart\Data\ActiveCartData;
use RefactorCircus\Polycart\Domains\Cart\Data\CreateCartData;
use RefactorCircus\Polycart\Domains\Cart\Events\ActiveCartResolvedActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Events\ActiveCartResolvingActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartType\Services\CartTypeRegistry;

/**
 * An owner's current cart of a type in a scope, started if they have none.
 *
 * A cart is current while it has not expired and is still in its type's
 * initial status. Once it moves on — checked out, submitted — the next call
 * starts a fresh one.
 */
final class ActiveCartAction
{
    public function __construct(
        private readonly CartTypeRegistry $types,
        private readonly CreateCartAction $create,
    ) {}

    /**
     * @param  Model|null  $scope  Each scope has its own active cart, so
     *                             switching teams switches carts.
     */
    public function execute(ActiveCartData $data, Model|string $owner, ?Model $scope = null): CartModel
    {
        ActiveCartResolvingActionEvent::dispatch($data, $owner, $scope);

        $result = $this->perform($data->type, $owner, $scope);

        ActiveCartResolvedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @param  Model|null  $scope  Each scope has its own active cart, so
     *                             switching teams switches carts.
     */
    private function perform(string $type, Model|string $owner, ?Model $scope = null): CartModel
    {
        $initial = CartModel::statusValue($this->types->get($type)->initialStatus());

        $query = CartModel::query()->ofType($type)->ownedBy($owner)->unexpired();

        $scope === null
            ? $query->whereNull('scope_type')
            : $query->where('scope_type', $scope->getMorphClass())->where('scope_id', (string) $scope->getKey());

        $initial === null
            ? $query->whereNull('status')
            : $query->where('status', $initial);

        return $query->latest()->first() ?? $this->create->execute(new CreateCartData($type), $owner, $scope);
    }
}
