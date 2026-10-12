<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Domains\Cart\Actions;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Support\Surface;
use RefactorCircus\Polycart\Domains\Cart\Data\ConvertCartData;
use RefactorCircus\Polycart\Domains\Cart\Events\CartConvertedActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Events\CartConvertingActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Exceptions\InvalidConversionException;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartType\Services\CartTypeRegistry;
use RefactorCircus\Polycart\Domains\CartType\Support\CartType;
use RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel;

/**
 * Turn a cart into another type: a cart into a quote, a quote into an order.
 *
 * The converted cart records the source of the conversion, not the original's,
 * whether it is a copy or the same cart retyped.
 *
 * The source type must list the target in convertsTo(). Every line is checked
 * against the target type, so a type that requires prices refuses a cart with
 * unpriced lines and nothing is written.
 */
final class ConvertCartAction
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly CartTypeRegistry $types,
    ) {}

    public function execute(CartModel $cart, ConvertCartData $data): CartModel
    {
        $from = $cart->type;

        CartConvertingActionEvent::dispatch($cart, $data);

        $result = $this->perform($cart, $data->to, $data->copy);

        CartConvertedActionEvent::dispatch($cart, $result, $from, $data->to, $data->copy);

        return $result;
    }

    /**
     * @param  bool  $copy  Copy the cart and its lines (true) or retype it in place (false).
     */
    private function perform(CartModel $cart, string $to, bool $copy = true): CartModel
    {
        $source = $cart->cartType();

        if (! in_array($to, $source->convertsTo(), true)) {
            throw InvalidConversionException::notAllowed($source->key(), $to);
        }

        $target = $this->types->get($to);

        return $this->db->transaction(fn (): CartModel => $copy ? $this->copy($cart, $target) : $this->retype($cart, $target));
    }

    private function copy(CartModel $cart, CartType $target): CartModel
    {
        $class = $target->model();

        /** @var CartModel $converted */
        $converted = new $class;

        $converted->forceFill([
            'type' => $target->key(),
            'owner_type' => $cart->owner_type,
            'owner_id' => $cart->owner_id,
            'session_key' => $cart->session_key,
            'label' => $cart->label,
            'meta' => $cart->meta,
        ]);

        // The copy stays in the same tree, and everyone keeps their access.
        $converted->inheritPlaceFrom($cart);
        $converted->save();

        // The copy already holds its owner's membership; everyone else joins.
        $held = $converted->members()->get(['member_type', 'member_id'])
            ->mapWithKeys(fn (CartMemberModel $member): array => [$member->member_type.'|'.$member->member_id => true])
            ->all();

        foreach ($cart->members()->get() as $member) {
            if (isset($held[$member->member_type.'|'.$member->member_id])) {
                continue;
            }

            $converted->members()->create([
                'member_type' => $member->member_type,
                'member_id' => $member->member_id,
                'role' => $this->roleIn($target, $member->role),
            ]);
        }

        foreach ($cart->lines()->get() as $line) {
            $copy = $line->replicate(['cart_id']);
            $copy->cart()->associate($converted);

            $target->validate($converted, $copy);
            $copy->save();
        }

        return $this->finish($converted, $cart, $target);
    }

    private function retype(CartModel $cart, CartType $target): CartModel
    {
        $lifetime = $target->lifetime();

        $cart->forceFill([
            'type' => $target->key(),
            // A cart that became an order through MCP is an MCP order.
            'source' => app(Surface::class)->current(),
            'status' => CartModel::statusValue($target->initialStatus()),
            'expires_at' => $lifetime === null ? null : Carbon::now()->add($lifetime),
        ])->save();

        // A role the target type does not have would silently grant nothing,
        // so it becomes the target's weakest role, as it does on a copy.
        foreach ($cart->members()->get() as $member) {
            $member->update(['role' => $this->roleIn($target, $member->role)]);
        }

        // Re-read so the cart hydrates as the target type's model.
        $converted = CartModel::query()->withoutGlobalScopes()->findOrFail($cart->id);

        foreach ($converted->lines as $line) {
            $target->validate($converted, $line);
        }

        return $this->finish($converted, $cart, $target);
    }

    /**
     * A member keeps their role when the target type has it, and otherwise
     * gets the target's weakest role rather than more access than before.
     */
    private function roleIn(CartType $target, string $role): string
    {
        return array_key_exists($role, $target->roles()) ? $role : $target->visibilityRole();
    }

    private function finish(CartModel $converted, CartModel $source, CartType $target): CartModel
    {
        $target->convertedFrom($converted, $source);

        $converted->save();
        $converted->unsetRelation('lines');

        return $converted;
    }
}
