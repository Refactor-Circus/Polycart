<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\ShowCartTool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\UpdateCartTool;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Cart\Policies\CartPolicy;
use RefactorCircus\Polycart\Domains\CartLine\Mcp\Tools\AddLinesTool;
use RefactorCircus\Polycart\Domains\CartLine\Mcp\Tools\RemoveLinesTool;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;
use RefactorCircus\Polycart\Domains\CartLine\Policies\CartLinePolicy;
use RefactorCircus\Polycart\Domains\Scope\Models\CartPathModel;
use RefactorCircus\Polycart\Domains\Scope\Policies\CartPathPolicy;
use RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel;
use RefactorCircus\Polycart\Domains\Sharing\Policies\CartMemberPolicy;
use RefactorCircus\Polycart\Facades\Polycart;
use RefactorCircus\Polycart\PolycartServiceProvider;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Quote;
use RefactorCircus\Polycart\Tests\Fixtures\Policies\KeepLinesPolicy;
use RefactorCircus\Polycart\Tests\Fixtures\Policies\ReadOnlyCartPolicy;

beforeEach(function (): void {
    config()->set('polycart.authorization', true);

    $this->sales = team(organization(), 'Sales');
    $this->ann = person($this->sales);
    $this->bob = person($this->sales);
});

/**
 * Register the policies again after a test changes `polycart.policies`, as
 * the provider does on boot.
 *
 * @param  array<class-string, class-string>  $policies
 */
function usePolicies(array $policies): void
{
    foreach ($policies as $model => $policy) {
        config()->set('polycart.policies.'.$model, $policy);
    }

    $provider = app()->getProvider(PolycartServiceProvider::class);

    (fn () => $this->registerPolicies())->call($provider);
}

it('registers the policies from the config', function (): void {
    expect(Gate::getPolicyFor(CartModel::class))->toBeInstanceOf(CartPolicy::class)
        ->and(Gate::getPolicyFor(Quote::class))->toBeInstanceOf(CartPolicy::class)
        ->and(Gate::getPolicyFor(CartLineModel::class))->toBeInstanceOf(CartLinePolicy::class)
        ->and(Gate::getPolicyFor(CartMemberModel::class))->toBeInstanceOf(CartMemberPolicy::class)
        ->and(Gate::getPolicyFor(CartPathModel::class))->toBeInstanceOf(CartPathPolicy::class);
});

it('lets whoever sees a cart see its tree paths, and no one change them', function (): void {
    $cart = Polycart::create('cart', $this->ann, scope: $this->sales);
    $path = $cart->paths()->firstOrFail();

    expect($this->ann->can('viewAny', [CartPathModel::class, $cart]))->toBeTrue()
        ->and($this->ann->can('view', $path))->toBeTrue()
        ->and($this->ann->can('create', [CartPathModel::class, $cart]))->toBeFalse()
        ->and($this->ann->can('update', $path))->toBeFalse()
        ->and($this->ann->can('delete', $path))->toBeFalse()
        ->and($this->bob->can('view', $path))->toBeFalse();
});

it('lets the owner do anything with their cart', function (): void {
    $cart = Polycart::create('cart', $this->ann, scope: $this->sales);

    // Ownership is enough on its own, without the owner's membership.
    $cart->members()->delete();
    $cart->unsetRelation('members');

    expect($this->ann->can('view', $cart))->toBeTrue()
        ->and($this->ann->can('update', $cart))->toBeTrue()
        ->and($this->ann->can('delete', $cart))->toBeTrue()
        ->and($this->ann->can('approve', $cart))->toBeTrue()
        ->and($this->bob->can('view', $cart))->toBeFalse()
        ->and($this->bob->can('approve', $cart))->toBeFalse();
});

it('checks lines and members against their cart', function (): void {
    $cart = Polycart::create('cart', $this->ann, scope: $this->sales);
    $line = $cart->add(product());
    Polycart::share($cart, $this->bob, 'viewer');
    $member = $cart->members()->where('member_id', (string) $this->bob->id)->firstOrFail();

    expect($this->bob->can('viewAny', [CartLineModel::class, $cart]))->toBeTrue()
        ->and($this->bob->can('view', $line))->toBeTrue()
        ->and($this->bob->can('create', [CartLineModel::class, $cart]))->toBeFalse()
        ->and($this->bob->can('update', $line))->toBeFalse()
        ->and($this->bob->can('delete', $line))->toBeFalse()
        ->and($this->bob->can('viewAny', [CartMemberModel::class, $cart]))->toBeTrue()
        ->and($this->bob->can('delete', $member))->toBeFalse()
        ->and($this->ann->can('update', $line))->toBeTrue()
        ->and($this->ann->can('create', [CartMemberModel::class, $cart]))->toBeTrue()
        ->and($this->ann->can('delete', $member))->toBeTrue();
});

it('uses a cart policy swapped in the config, for carts and their lines', function (): void {
    usePolicies([CartModel::class => ReadOnlyCartPolicy::class]);

    $cart = Polycart::create('cart', $this->ann, scope: $this->sales);

    $this->actingAs($this->ann)->getJson("/polycart/carts/{$cart->id}")->assertOk();
    $this->actingAs($this->ann)->patchJson("/polycart/carts/{$cart->id}", ['name' => 'Renamed'])->assertForbidden();
    $this->actingAs($this->ann)->postJson("/polycart/carts/{$cart->id}/lines", ['lines' => [['unit_price' => 100]]])->assertForbidden();

    mcpToolAs($this->ann, ShowCartTool::class, ['cart' => $cart->id])->assertOk();
    mcpToolAs($this->ann, UpdateCartTool::class, ['cart' => $cart->id, 'name' => 'Renamed'])->assertHasErrors(['Unauthorized.']);
    mcpToolAs($this->ann, AddLinesTool::class, ['cart' => $cart->id, 'lines' => [['unit_price' => 100]]])->assertHasErrors(['Unauthorized.']);
});

it('checks each named line against a line policy swapped in the config', function (): void {
    usePolicies([CartLineModel::class => KeepLinesPolicy::class]);

    $cart = Polycart::create('cart', $this->ann, scope: $this->sales);
    $line = $cart->add(product());

    $this->actingAs($this->ann)
        ->patchJson("/polycart/carts/{$cart->id}/lines", ['lines' => [['id' => $line->id, 'quantity' => 3]]])
        ->assertOk();

    $this->actingAs($this->ann)
        ->deleteJson("/polycart/carts/{$cart->id}/lines", ['lines' => [$line->id]])
        ->assertForbidden();

    mcpToolAs($this->ann, RemoveLinesTool::class, ['cart' => $cart->id, 'lines' => [$line->id]])
        ->assertHasErrors(['Unauthorized.']);

    expect($cart->lines()->count())->toBe(1);
});
