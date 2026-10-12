<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\Fluent\AssertableJson;
use RefactorCircus\Atrium\Domains\Widgets\Data\WidgetDefinition;
use RefactorCircus\Polycart\Atrium\PolycartPlugin;
use RefactorCircus\Polycart\Atrium\ScreenAccess;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\ListCartsTool;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Facades\Polycart;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Person;

/**
 * `polycart.atrium.show_all` makes some users dashboard operators: they see
 * every cart on the Atrium screens and may act on it, whatever their role.
 * The JSON API and MCP tools never treat anyone as one.
 */
beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    // Atrium denies access outside local until a gate is defined.
    app()->detectEnvironment(fn (): string => 'local');

    config()->set('polycart.authorization', true);

    $this->sales = team(organization(), 'Sales');
    $this->ann = person($this->sales);
    $this->bob = person($this->sales);
    $this->cart = Polycart::create('order', $this->ann, scope: $this->sales, attributes: ['label' => 'Annes order']);
    $this->line = $this->cart->add(product(), 2);
});

/**
 * What the dashboard's widgets and search hold for a user.
 *
 * @return array{recent: array<int, string>, counts: array<string, int>, search: array<int, string>}
 */
function operatorDashboard(Person $user): array
{
    test()->actingAs($user);

    $request = Request::create('/atrium');
    $request->setUserResolver(fn (): Person => $user);

    $plugin = app(PolycartPlugin::class);

    // Each widget and the search are offered only to users who may list carts.
    expect(collect($plugin->widgets())->every(fn (WidgetDefinition $widget): bool => $widget->isAuthorized($request)))->toBeTrue()
        ->and($plugin->search()?->isAuthorized($request))->toBeTrue();

    $widgets = collect($plugin->widgets())->keyBy(fn (WidgetDefinition $widget): string => $widget->key);

    /** @var array{carts: Collection<int, CartModel>} $recent */
    $recent = $widgets['polycart.recent-carts']->resolveData();
    /** @var array{counts: array<string, int>} $counts */
    $counts = $widgets['polycart.carts-by-type']->resolveData();

    return [
        'recent' => $recent['carts']->pluck('label')->all(),
        'counts' => $counts['counts'],
        'search' => array_map(fn ($result): string => $result->title, $plugin->search()?->results('Annes') ?? []),
    ];
}

it('keeps other users\' carts from a non-operator by default', function (): void {
    expect(config('polycart.atrium.show_all'))->toBeFalse()
        ->and(ScreenAccess::operator($this->bob))->toBeFalse();

    $this->actingAs($this->bob)->get(route('atrium.polycart.carts.index'))->assertOk()->assertDontSee('Annes order');
    $this->actingAs($this->bob)->get(route('atrium.polycart.carts.show', $this->cart))->assertForbidden();
    $this->actingAs($this->bob)->post(route('atrium.polycart.carts.clear', $this->cart))->assertForbidden();

    expect(operatorDashboard($this->bob))->toBe(['recent' => [], 'counts' => array_fill_keys(array_keys(config('polycart.types')), 0), 'search' => []]);
});

it('shows every cart and allows every action to everyone with show_all on', function (): void {
    config()->set('polycart.atrium.show_all', true);

    expect(ScreenAccess::operator($this->bob))->toBeTrue()
        ->and(ScreenAccess::operator(null))->toBeFalse();

    $this->actingAs($this->bob)->get(route('atrium.polycart.carts.index'))->assertOk()->assertSee('Annes order');

    $response = $this->actingAs($this->bob)->get(route('atrium.polycart.carts.show', $this->cart))->assertOk();

    foreach (['clear-cart', 'delete-cart', 'update-line', 'remove-line', 'set-visibility', 'remove-member', 'share-cart', 'details-card', 'transition-cart'] as $control) {
        $response->assertSee('data-testid="'.$control.'"', false);
    }

    // Cancelling needs `transition`, which only the owner holds.
    $response->assertSee('value="cancelled"', false);

    $dashboard = operatorDashboard($this->bob);

    expect($dashboard['recent'])->toBe(['Annes order'])
        ->and($dashboard['counts']['order'])->toBe(1)
        ->and($dashboard['search'])->toBe(['Annes order']);

    $this->actingAs($this->bob)->patch(route('atrium.polycart.carts.lines.update', [$this->cart, $this->line]), ['quantity' => 5])->assertSessionHas('status');
    $this->actingAs($this->bob)->patch(route('atrium.polycart.carts.update', $this->cart), ['label' => 'Fixed by support'])->assertSessionHas('status');
    $this->actingAs($this->bob)->post(route('atrium.polycart.carts.transition', $this->cart), ['status' => 'cancelled'])->assertSessionHas('status');

    $cart = $this->cart->fresh();

    expect($cart?->label)->toBe('Fixed by support')
        ->and($cart?->status)->toBe('cancelled')
        ->and($this->line->fresh()?->quantity)->toBe(5);

    $this->actingAs($this->bob)->delete(route('atrium.polycart.carts.destroy', $this->cart))->assertRedirect(route('atrium.polycart.carts.index'));
    expect(CartModel::query()->find($this->cart->id))->toBeNull();
});

it('makes only the users a gate ability allows operators', function (): void {
    config()->set('polycart.atrium.show_all', 'manage-carts');

    $support = person($this->sales);
    Gate::define('manage-carts', fn (Person $user): bool => $user->is($support));

    expect(ScreenAccess::operator($support))->toBeTrue()
        ->and(ScreenAccess::operator($this->bob))->toBeFalse();

    $this->actingAs($support)->get(route('atrium.polycart.carts.index'))->assertOk()->assertSee('Annes order');
    $this->actingAs($support)->get(route('atrium.polycart.carts.show', $this->cart))->assertOk()->assertSee('data-testid="delete-cart"', false);
    expect(operatorDashboard($support)['search'])->toBe(['Annes order']);

    $this->actingAs($this->bob)->get(route('atrium.polycart.carts.index'))->assertOk()->assertDontSee('Annes order');
    $this->actingAs($this->bob)->get(route('atrium.polycart.carts.show', $this->cart))->assertForbidden();
    expect(operatorDashboard($this->bob)['search'])->toBe([]);
});

it('treats an undefined gate ability as no operator', function (): void {
    config()->set('polycart.atrium.show_all', 'not-defined');

    expect(ScreenAccess::operator($this->bob))->toBeFalse();
    $this->actingAs($this->bob)->get(route('atrium.polycart.carts.show', $this->cart))->assertForbidden();
});

it('leaves the JSON API and MCP tools to the user\'s role with show_all on', function (): void {
    config()->set('polycart.atrium.show_all', true);

    $this->actingAs($this->bob)->getJson("/polycart/carts/{$this->cart->id}")->assertForbidden();
    $this->actingAs($this->bob)->patchJson("/polycart/carts/{$this->cart->id}", ['label' => 'x'])->assertForbidden();
    $this->actingAs($this->bob)->deleteJson("/polycart/carts/{$this->cart->id}")->assertForbidden();
    $this->actingAs($this->bob)->getJson('/polycart/carts')->assertOk()->assertJsonCount(0, 'data');

    mcpToolAs($this->bob, ListCartsTool::class)
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->has('data', 0)->etc());

    expect(CartModel::query()->find($this->cart->id)?->label)->toBe('Annes order');
});
