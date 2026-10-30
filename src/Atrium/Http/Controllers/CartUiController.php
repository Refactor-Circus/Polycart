<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Atrium\Http\Controllers;

use BackedEnum;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Polycart\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Polycart\Atrium\ScreenAccess;
use RefactorCircus\Polycart\Atrium\Support\Screens;
use RefactorCircus\Polycart\Domains\Cart\Actions\ClearCartAction;
use RefactorCircus\Polycart\Domains\Cart\Actions\ConvertCartAction;
use RefactorCircus\Polycart\Domains\Cart\Actions\DeleteCartAction;
use RefactorCircus\Polycart\Domains\Cart\Actions\ListCartsAction;
use RefactorCircus\Polycart\Domains\Cart\Actions\ShowCartAction;
use RefactorCircus\Polycart\Domains\Cart\Actions\TransitionCartAction;
use RefactorCircus\Polycart\Domains\Cart\Actions\UpdateCartAction;
use RefactorCircus\Polycart\Domains\Cart\Data\ConvertCartData;
use RefactorCircus\Polycart\Domains\Cart\Data\ListCartsData;
use RefactorCircus\Polycart\Domains\Cart\Data\TransitionCartData;
use RefactorCircus\Polycart\Domains\Cart\Data\UpdateCartData;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Actions\RemoveLineAction;
use RefactorCircus\Polycart\Domains\CartLine\Actions\UpdateLineAction;
use RefactorCircus\Polycart\Domains\CartLine\Data\UpdateLineData;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;
use RefactorCircus\Polycart\Domains\CartType\Services\CartTypeRegistry;
use RefactorCircus\Polycart\Domains\Sharing\Actions\SetVisibilityAction;
use RefactorCircus\Polycart\Domains\Sharing\Actions\ShareCartAction;
use RefactorCircus\Polycart\Domains\Sharing\Actions\UnshareCartAction;
use RefactorCircus\Polycart\Domains\Sharing\Data\SetVisibilityData;
use RefactorCircus\Polycart\Domains\Sharing\Data\ShareCartData;
use RefactorCircus\Polycart\Domains\Sharing\Enums\Visibility;
use RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel;
use RefactorCircus\Polycart\Exceptions\PolycartException;
use RefactorCircus\Polycart\Support\Morphs;

/**
 * The dashboard pages. Every change goes through the same Action the JSON
 * API and MCP tools call, after the same policy check their requests make,
 * so a refusal here is the refusal they would give. The views hide each
 * control with that check (`@polycartCan`), so they cannot drift apart.
 *
 * The carts list (`polycart.carts`) and a cart (`polycart.cart`) declare
 * themselves on Atrium's screen extensions registry, resolved only once the
 * page's own check has passed.
 */
final class CartUiController
{
    use AuthorizesScreens;

    public function index(Request $request): View
    {
        // Filters are validated by the list's own Data, so the page and the
        // JSON API accept exactly the same query.
        $this->authorizeScreen('viewAny', CartModel::class);

        $filters = ListCartsData::from($request->all());
        $carts = app(ListCartsAction::class)->execute($filters, ScreenAccess::actor());

        // A row section's permission() asks about each cart's members: the
        // first row that asks loads them for every row, and nothing loads
        // while no row asks.
        (new Collection($carts->items()))->withRelationshipAutoloading();

        /** @var view-string $view */
        $view = 'polycart::ui.carts.index';

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::list('polycart.carts'), $request),
            'carts' => $carts,
            'filters' => $filters->toArray(),
            'types' => array_keys(app(CartTypeRegistry::class)->all()),
        ]);
    }

    public function show(Request $request, CartModel $cart): View
    {
        $this->authorizeScreen('view', $cart);

        $cart = app(ShowCartAction::class)->execute($cart);
        $type = app(CartTypeRegistry::class)->has($cart->type) ? $cart->cartType() : null;

        /** @var view-string $view */
        $view = 'polycart::ui.carts.show';

        return view($view, [
            'resolved' => app(ScreenExtensions::class)->resolve(Screens::detail('polycart.cart', $cart), $request),
            'cart' => $cart->load(['parent', 'children', 'paths']),
            'type' => $type,
            // Only the moves the type allows from here, and the user may make.
            'nextStatuses' => array_values(array_filter(
                $type?->nextStatuses($cart->currentStatus()) ?? [],
                fn (BackedEnum $status): bool => ScreenAccess::allows('transition', $cart, [(string) $status->value]),
            )),
            'conversions' => array_values(array_filter(
                $type?->convertsTo() ?? [],
                fn (string $to): bool => ScreenAccess::allows('convert', $cart, [$to]),
            )),
            'roles' => array_keys($type?->roles() ?? []),
            'memberTypes' => [...array_keys(config()->array('polycart.owners')), ...array_keys(config()->array('polycart.scopes'))],
            'visibilities' => Visibility::cases(),
        ]);
    }

    public function update(Request $request, CartModel $cart): RedirectResponse
    {
        $this->authorizeScreen('update', $cart);

        // Meta arrives from the form as a JSON string.
        $request->validate(['meta' => ['nullable', 'json']]);
        $meta = $request->input('meta');

        $data = UpdateCartData::from([
            'label' => $request->input('label'),
            'meta' => is_string($meta) ? json_decode($meta, true) : null,
        ]);

        return $this->attempt($cart, fn (): mixed => app(UpdateCartAction::class)->execute($cart, $data), 'cart_updated');
    }

    public function destroy(CartModel $cart): RedirectResponse
    {
        $this->authorizeScreen('delete', $cart);

        app(DeleteCartAction::class)->execute($cart);

        return redirect()
            ->route('atrium.polycart.carts.index')
            ->with('status', __('polycart::polycart.cart_deleted'));
    }

    public function clear(CartModel $cart): RedirectResponse
    {
        $this->authorizeScreen('update', $cart);

        return $this->attempt($cart, fn (): mixed => app(ClearCartAction::class)->execute($cart), 'cart_cleared');
    }

    public function transition(Request $request, CartModel $cart): RedirectResponse
    {
        $this->authorizeScreen('transition', $cart, [$request->string('status')->toString()]);

        $data = TransitionCartData::from($request->all());

        return $this->attempt($cart, fn (): mixed => app(TransitionCartAction::class)->execute($cart, $data), 'status_changed');
    }

    public function convert(Request $request, CartModel $cart): RedirectResponse
    {
        $this->authorizeScreen('convert', $cart, [$request->string('to')->toString()]);

        // An unticked "keep the original" box sends nothing: retype in place.
        $data = ConvertCartData::from(['copy' => $request->boolean('copy')] + $request->all());

        try {
            $converted = app(ConvertCartAction::class)->execute($cart, $data);
        } catch (PolycartException $e) {
            return back()->withErrors(['polycart' => $e->getMessage()]);
        }

        return redirect()
            ->route('atrium.polycart.carts.show', $converted)
            ->with('status', __('polycart::polycart.cart_converted'));
    }

    public function updateLine(Request $request, CartModel $cart, CartLineModel $line): RedirectResponse
    {
        $this->authorizeScreen('update', $line);

        $data = UpdateLineData::from($request->all());

        return $this->attempt($cart, fn (): mixed => app(UpdateLineAction::class)->execute($line, $data), 'line_updated');
    }

    public function removeLine(CartModel $cart, CartLineModel $line): RedirectResponse
    {
        $this->authorizeScreen('delete', $line);

        return $this->attempt($cart, function () use ($line): void {
            app(RemoveLineAction::class)->execute($line);
        }, 'line_removed');
    }

    public function visibility(Request $request, CartModel $cart): RedirectResponse
    {
        $this->authorizeScreen('share', $cart);

        $data = SetVisibilityData::from($request->all());

        return $this->attempt($cart, fn (): mixed => app(SetVisibilityAction::class)->execute($cart, $data), 'visibility_changed');
    }

    public function share(Request $request, CartModel $cart): RedirectResponse
    {
        $this->authorizeScreen('create', CartMemberModel::class, [$cart]);

        $member = $request->validate(Morphs::memberRules());
        $data = ShareCartData::from($request->all());

        return $this->attempt($cart, fn (): mixed => app(ShareCartAction::class)->execute(
            $cart,
            app(Morphs::class)->memberFrom($member),
            $data,
        ), 'cart_shared');
    }

    public function unshare(CartModel $cart, CartMemberModel $member): RedirectResponse
    {
        $this->authorizeScreen('delete', $member);

        return $this->attempt($cart, function () use ($cart, $member): void {
            app(UnshareCartAction::class)->execute($cart, $member);
        }, 'cart_unshared');
    }

    /**
     * Run a change and return to the cart, showing the type's refusal if any.
     *
     * @param  Closure(): mixed  $change
     */
    private function attempt(CartModel $cart, Closure $change, string $message): RedirectResponse
    {
        try {
            $change();
        } catch (ModelNotFoundException) {
            return back()->withErrors(['polycart' => __('polycart::polycart.member_not_found')]);
        } catch (PolycartException $e) {
            return back()->withErrors(['polycart' => $e->getMessage()]);
        }

        return redirect()
            ->route('atrium.polycart.carts.show', $cart)
            ->with('status', __('polycart::polycart.'.$message));
    }
}
