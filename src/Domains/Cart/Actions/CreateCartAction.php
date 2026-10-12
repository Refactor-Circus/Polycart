<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Actions;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Polycart\Domains\Cart\Data\CreateCartData;
use RefactorCircus\Polycart\Domains\Cart\Events\CartCreatedActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Events\CartCreatingActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartType\Services\CartTypeRegistry;
use RefactorCircus\Polycart\Domains\Scope\Contracts\CartParticipant;
use RefactorCircus\Polycart\Domains\Scope\Contracts\CartScope;
use RefactorCircus\Polycart\Domains\Scope\Exceptions\InvalidScopeException;
use RefactorCircus\Polycart\Domains\Scope\Services\ScopeTree;

/**
 * Start a new cart of a type, hydrated as that type's model.
 */
final class CreateCartAction
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly CartTypeRegistry $types,
        private readonly ScopeTree $tree,
    ) {}

    /**
     * @param  Model|string|null  $owner  A model, or a guest's session key.
     * @param  Model|null  $scope  The level of the tree the cart lives in, such as a team.
     */
    public function execute(CreateCartData $data, Model|string|null $owner = null, ?Model $scope = null): CartModel
    {
        CartCreatingActionEvent::dispatch($data, $owner, $scope);

        $result = $this->perform($data, $owner, $scope);

        CartCreatedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @param  Model|string|null  $owner  A model, or a guest's session key.
     * @param  Model|null  $scope  The level of the tree the cart lives in, such as a team.
     */
    private function perform(CreateCartData $data, Model|string|null $owner = null, ?Model $scope = null): CartModel
    {
        $class = $this->types->get($data->type)->model();

        /** @var CartModel $cart */
        $cart = new $class;

        $cart->fill(['label' => $data->label, 'meta' => $data->meta, 'parent_id' => $data->parentId]);
        $cart->forceFill([...self::owner($owner), 'type' => $data->type]);

        if ($scope !== null) {
            if (! $scope instanceof CartScope) {
                throw InvalidScopeException::notAScope($scope::class);
            }

            // Someone can only start a cart in a part of the tree they are in.
            if ($owner instanceof CartParticipant && ! $this->tree->reaches($owner, $scope->getMorphClass(), (string) $scope->getKey())) {
                throw InvalidScopeException::outside($scope->getMorphClass().':'.$scope->getKey());
            }

            $cart->placeIn($scope);
        }

        // The cart, its place in the tree and its creator's membership land together.
        return $this->db->transaction(function () use ($cart): CartModel {
            $cart->save();

            return $cart;
        });
    }

    /**
     * @return array<string, string|null>
     */
    private static function owner(Model|string|null $owner): array
    {
        if ($owner instanceof Model) {
            return [
                'owner_type' => $owner->getMorphClass(),
                'owner_id' => (string) $owner->getKey(),
            ];
        }

        return $owner === null ? [] : ['session_key' => $owner];
    }
}
