<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\CartLine\Actions;

use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pipeline\Pipeline;
use RefactorCircus\Keystone\Support\Surface;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Data\AddLineData;
use RefactorCircus\Polycart\Domains\CartLine\Events\LineAddedActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Events\LineAddingActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Exceptions\LineRejectedException;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;
use RefactorCircus\Polycart\Domains\CartLine\Support\PendingLine;

/**
 * Put something in a cart by sending it through the cart type's stages.
 *
 * The whole pipeline runs in one transaction: a stage that rejects the line
 * leaves the cart exactly as it was.
 */
final class AddLineAction
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly Pipeline $pipeline,
        private readonly Auth $auth,
        private readonly Surface $surface,
    ) {}

    public function execute(CartModel $cart, AddLineData $data): CartLineModel
    {
        LineAddingActionEvent::dispatch($cart, $data);

        $result = $this->perform($cart, $data);

        LineAddedActionEvent::dispatch($cart, $result, ! $result->wasRecentlyCreated);

        return $result;
    }

    private function perform(CartModel $cart, AddLineData $data): CartLineModel
    {
        $type = $cart->cartType();
        $user = $this->auth->guard()->user();

        $pending = new PendingLine(
            cart: $cart,
            type: $type,
            purchasable: $data->purchasable,
            quantity: $data->quantity,
            options: $data->options,
            meta: $data->meta,
            unitPrice: $data->unitPrice,
            actor: $user instanceof Model ? $user : null,
            source: $this->surface->current(),
        );

        $line = $this->db->transaction(function () use ($pending, $type, $cart): CartLineModel {
            /** @var PendingLine $pending */
            $pending = $this->pipeline
                ->send($pending)
                ->through($type->addLineStages())
                ->thenReturn();

            // A stage that returns without passing the line on, and without
            // rejecting it, would otherwise look like a silent success.
            if (! $pending->line instanceof CartLineModel || ! $pending->line->exists) {
                throw LineRejectedException::stopped();
            }

            $cart->extendLifetime();

            return $pending->line;
        });

        $cart->unsetRelation('lines');

        return $line;
    }
}
