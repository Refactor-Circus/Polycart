<?php

declare(strict_types=1);

use Illuminate\Testing\Fluent\AssertableJson;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\ActiveCartTool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\CreateCartTool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\ListCartsTool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\ShowCartTool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\TransitionCartTool;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Mcp\Tools\AddLinesTool;
use RefactorCircus\Polycart\Domains\Sharing\Mcp\Tools\ListMembersTool;
use RefactorCircus\Polycart\Domains\Sharing\Mcp\Tools\SetVisibilityTool;
use RefactorCircus\Polycart\Domains\Sharing\Mcp\Tools\ShareCartTool;
use RefactorCircus\Polycart\Domains\Sharing\Mcp\Tools\UnshareCartTool;
use RefactorCircus\Polycart\Facades\Polycart;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Person;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Team;

beforeEach(function (): void {
    config()->set('polycart.authorization', true);

    $this->acme = organization();
    $this->sales = team($this->acme, 'Sales');
    $this->ann = person($this->sales);
    $this->bob = person($this->sales);
});

it('requires a signed-in user', function (): void {
    $this->getJson('/polycart/carts')->assertUnauthorized();

    mcpTool(ListCartsTool::class)->assertHasErrors(['Unauthorized.']);
});

it('resolves the signed-in user\'s active cart in the scope asked for', function (): void {
    $ask = fn (): string => $this->actingAs($this->ann)
        ->postJson('/polycart/carts/active', ['type' => 'cart', 'scope_type' => 'team', 'scope_id' => (string) $this->sales->id])
        ->assertSuccessful()
        ->assertJsonPath('data.owner_id', (string) $this->ann->id)
        ->assertJsonPath('data.scope_id', (string) $this->sales->id)
        ->json('data.id');

    expect($ask())->toBe($ask());

    mcpToolAs($this->ann, ActiveCartTool::class, ['type' => 'cart'])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json
            ->where('data.owner_id', (string) $this->ann->id)
            ->where('data.scope_id', null)
            ->etc());
});

it('starts carts owned by the signed-in user, whatever the body says', function (): void {
    /** @var Person $ann */
    $ann = $this->ann;

    $id = $this->actingAs($ann)
        ->postJson('/polycart/carts', ['type' => 'cart', 'scope_type' => 'team', 'scope_id' => (string) $this->sales->id, 'owner_type' => 'person', 'owner_id' => (string) $this->bob->id])
        ->assertCreated()
        ->assertJsonPath('data.owner_id', (string) $ann->id)
        ->assertJsonPath('data.scope_type', Team::class)
        ->assertJsonPath('data.visibility', 'private')
        ->json('data.id');

    expect(CartModel::query()->findOrFail($id)->roleFor($ann))->toBe('owner');

    $this->actingAs($ann)
        ->postJson('/polycart/carts', ['type' => 'cart', 'scope_type' => 'team', 'scope_id' => (string) team($this->acme, 'Ops')->id])
        ->assertUnprocessable()
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'does not belong'));
});

it('lists only the carts the user can access', function (): void {
    $mine = Polycart::create('cart', $this->ann, scope: $this->sales);
    Polycart::create('cart', $this->bob, scope: $this->sales);

    $this->actingAs($this->ann)->getJson('/polycart/carts')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mine->id);

    mcpToolAs($this->ann, ListCartsTool::class)
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->has('data', 1)->where('data.0.id', $mine->id)->etc());
});

it('checks each change against the member\'s role', function (): void {
    $cart = Polycart::create('cart', $this->ann, scope: $this->sales);

    $this->actingAs($this->bob)->getJson("/polycart/carts/{$cart->id}")->assertForbidden();

    Polycart::share($cart, $this->bob, 'viewer');

    $this->actingAs($this->bob)->getJson("/polycart/carts/{$cart->id}")->assertOk()->assertJsonPath('data.members.1.role', 'viewer');
    $this->actingAs($this->bob)->postJson("/polycart/carts/{$cart->id}/lines", ['lines' => [['unit_price' => 100]]])->assertForbidden();
    $this->actingAs($this->bob)->deleteJson("/polycart/carts/{$cart->id}")->assertForbidden();

    Polycart::share($cart, $this->bob, 'editor');

    $this->actingAs($this->bob)->postJson("/polycart/carts/{$cart->id}/lines", ['lines' => [['unit_price' => 100]]])->assertCreated();
    $this->actingAs($this->bob)->postJson("/polycart/carts/{$cart->id}/members", ['member_type' => 'team', 'member_id' => (string) $this->sales->id, 'role' => 'viewer'])->assertForbidden();
});

it('shares, lists and removes members over the API', function (): void {
    $cart = Polycart::create('cart', $this->ann, scope: $this->sales);
    $ops = team($this->acme, 'Ops');

    $member = $this->actingAs($this->ann)
        ->postJson("/polycart/carts/{$cart->id}/members", ['member_type' => 'team', 'member_id' => (string) $ops->id, 'role' => 'editor'])
        ->assertCreated()
        ->assertJsonPath('data.member_type', Team::class)
        ->json('data.id');

    $this->actingAs($this->ann)->getJson("/polycart/carts/{$cart->id}/members")->assertOk()->assertJsonCount(2, 'data');

    $this->actingAs($this->ann)->putJson("/polycart/carts/{$cart->id}/visibility", ['visibility' => 'boundary'])
        ->assertOk()
        ->assertJsonPath('data.visibility', 'boundary');

    $this->actingAs($this->ann)->deleteJson("/polycart/carts/{$cart->id}/members/{$member}")->assertNoContent();

    $this->actingAs($this->ann)
        ->postJson("/polycart/carts/{$cart->id}/members", ['member_type' => 'team', 'member_id' => (string) team(organization('Globex'))->id, 'role' => 'viewer'])
        ->assertUnprocessable()
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'outside'));
});

it('guards a move with the ability the type names for it', function (): void {
    $order = Polycart::create('order', $this->ann, scope: $this->sales);
    Polycart::share($order, $this->bob, 'editor');

    $this->actingAs($this->bob)->postJson("/polycart/carts/{$order->id}/status", ['status' => 'processing'])->assertForbidden();
    $this->actingAs($this->bob)->postJson("/polycart/carts/{$order->id}/status", ['status' => 'cancelled'])->assertForbidden();

    Polycart::share($order, $this->bob, 'buyer');

    $this->actingAs($this->bob)->postJson("/polycart/carts/{$order->id}/status", ['status' => 'processing'])
        ->assertOk()
        ->assertJsonPath('data.status', 'processing');

    $this->actingAs($this->bob)->postJson("/polycart/carts/{$order->id}/status", ['status' => 'cancelled'])->assertForbidden();
});

it('enforces the same rules over MCP', function (): void {
    $cart = Polycart::create('cart', $this->ann, scope: $this->sales);

    mcpToolAs($this->bob, ShowCartTool::class, ['cart' => $cart->id])->assertHasErrors(['Unauthorized.']);
    mcpToolAs($this->bob, ListMembersTool::class, ['cart' => $cart->id])->assertHasErrors(['Unauthorized.']);

    mcpToolAs($this->ann, ShareCartTool::class, ['cart' => $cart->id, 'member_type' => 'person', 'member_id' => (string) $this->bob->id, 'role' => 'viewer'])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->where('data.role', 'viewer')->etc());

    mcpToolAs($this->bob, ShowCartTool::class, ['cart' => $cart->id])->assertOk();
    mcpToolAs($this->bob, AddLinesTool::class, ['cart' => $cart->id, 'lines' => [['unit_price' => 100]]])->assertHasErrors(['Unauthorized.']);

    mcpToolAs($this->ann, SetVisibilityTool::class, ['cart' => $cart->id, 'visibility' => 'scope'])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->where('data.visibility', 'scope')->etc());

    $membership = $cart->members()->where('member_id', (string) $this->bob->id)->firstOrFail();

    mcpToolAs($this->ann, UnshareCartTool::class, ['cart' => $cart->id, 'member' => $membership->id])->assertOk();

    mcpToolAs($this->ann, ListMembersTool::class, ['cart' => $cart->id])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json->has('data', 1)->etc());
});

it('starts carts over MCP as the signed-in user in their scope', function (): void {
    mcpToolAs($this->ann, CreateCartTool::class, ['type' => 'order', 'scope_type' => 'team', 'scope_id' => (string) $this->sales->id])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json): AssertableJson => $json
            ->where('data.owner_id', (string) $this->ann->id)
            ->where('data.scope_id', (string) $this->sales->id)
            ->etc());

    $order = CartModel::query()->ofType('order')->firstOrFail();

    mcpToolAs($this->ann, TransitionCartTool::class, ['cart' => $order->id, 'status' => 'processing'])->assertOk();
});
