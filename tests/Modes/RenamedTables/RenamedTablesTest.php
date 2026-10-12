<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Mcp\Tools\AddLinesTool;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;
use RefactorCircus\Polycart\Domains\Scope\Models\CartPathModel;
use RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel;
use RefactorCircus\Polycart\Facades\Polycart;
use RefactorCircus\Polycart\Support\Tables;

// The whole run here migrates with every table renamed through `polycart.tables`.

beforeEach(function (): void {
    config()->set('polycart.authorization', true);

    $this->acme = organization();
    $this->sales = team($this->acme, 'Sales');
    $this->ann = person($this->sales);
    $this->bob = person($this->sales);
});

it('migrates every table under its configured name', function (): void {
    foreach (array_keys(Tables::DEFAULTS) as $key) {
        expect(Schema::hasTable('crm_'.$key))->toBeTrue()
            ->and(Schema::hasTable(Tables::DEFAULTS[$key]))->toBeFalse();
    }

    expect((new CartModel)->getTable())->toBe('crm_carts')
        ->and((new CartLineModel)->getTable())->toBe('crm_cart_lines')
        ->and((new CartMemberModel)->getTable())->toBe('crm_cart_members')
        ->and((new CartPathModel)->getTable())->toBe('crm_cart_paths');
});

it('writes a cart\'s tree paths and owner membership from the model hooks', function (): void {
    $cart = $this->actingAs($this->ann)
        ->postJson('/polycart/carts', ['type' => 'cart', 'scope_type' => 'team', 'scope_id' => (string) $this->sales->id])
        ->assertCreated()
        ->json('data.id');

    expect(CartPathModel::query()->where('cart_id', $cart)->orderBy('depth')->pluck('depth')->all())->toBe([0, 1])
        ->and(CartMemberModel::query()->where('cart_id', $cart)->value('role'))->toBe('owner')
        ->and(CartModel::query()->inScope($this->acme)->pluck('id')->all())->toBe([$cart])
        ->and(CartModel::query()->accessibleBy($this->ann)->pluck('id')->all())->toBe([$cart])
        ->and(CartModel::query()->accessibleBy($this->bob)->count())->toBe(0);

    // Nested carts copy their parent's paths.
    $set = Polycart::create('project', $this->ann, scope: $this->sales);
    $child = Polycart::create('set', $this->ann, ['parent_id' => $set->id]);

    expect($child->paths()->count())->toBe(2)
        ->and($set->descendants()->pluck('id')->all())->toBe([$child->id]);
});

it('manages lines, members and visibility over the API and MCP', function (): void {
    $cart = Polycart::create('cart', $this->ann, scope: $this->sales);
    $product = product(price: 1000);

    $line = $this->actingAs($this->ann)
        ->postJson("/polycart/carts/{$cart->id}/lines", ['lines' => [['purchasable_type' => 'product', 'purchasable_id' => (string) $product->id, 'quantity' => 2]]])
        ->assertCreated()
        ->json('data.0.id');

    mcpToolAs($this->ann, AddLinesTool::class, ['cart' => $cart->id, 'lines' => [['unit_price' => 100]]])->assertOk();

    $this->actingAs($this->ann)
        ->patchJson("/polycart/carts/{$cart->id}/lines", ['lines' => [['id' => $line, 'quantity' => 5]]])
        ->assertOk()
        ->assertJsonPath('data.quantity', 6);

    $member = $this->actingAs($this->ann)
        ->postJson("/polycart/carts/{$cart->id}/members", ['member_type' => 'person', 'member_id' => (string) $this->bob->id, 'role' => 'viewer'])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($this->bob)->getJson('/polycart/carts')->assertOk()->assertJsonCount(1, 'data');
    $this->actingAs($this->bob)->getJson("/polycart/carts/{$cart->id}/members")->assertOk()->assertJsonCount(2, 'data');
    $this->actingAs($this->ann)->deleteJson("/polycart/carts/{$cart->id}/members/{$member}")->assertNoContent();
    $this->actingAs($this->bob)->getJson('/polycart/carts')->assertOk()->assertJsonCount(0, 'data');

    $this->actingAs($this->ann)->putJson("/polycart/carts/{$cart->id}/visibility", ['visibility' => 'scope'])->assertOk();
    $this->actingAs($this->bob)->getJson('/polycart/carts')->assertOk()->assertJsonCount(1, 'data');

    $this->actingAs($this->ann)->deleteJson("/polycart/carts/{$cart->id}/lines", ['lines' => [$line]])->assertOk();

    expect(CartLineModel::query()->where('cart_id', $cart->id)->count())->toBe(1);
});

it('converts, merges and deletes carts', function (): void {
    $cart = Polycart::create('cart', $this->ann, scope: $this->sales);
    $cart->add(product(sku: 'A'), 2);

    $quote = $cart->convertTo('quote');

    expect($quote->paths()->count())->toBe(2)
        ->and($quote->lines()->count())->toBe(1)
        ->and($quote->members()->count())->toBe(1);

    $other = Polycart::create('cart', $this->ann, scope: $this->sales);
    $other->add(product(sku: 'B'));

    $this->actingAs($this->ann)
        ->postJson("/polycart/carts/{$other->id}/merge", ['into' => $cart->id])
        ->assertOk()
        ->assertJsonCount(2, 'data.lines');

    $this->actingAs($this->ann)->deleteJson("/polycart/carts/{$cart->id}")->assertNoContent();

    expect(CartModel::query()->find($cart->id))->toBeNull()
        ->and(CartModel::withTrashed()->find($cart->id))->not->toBeNull();

    CartModel::withTrashed()->findOrFail($cart->id)->forceDelete();

    expect(CartLineModel::query()->where('cart_id', $cart->id)->exists())->toBeFalse()
        ->and(CartPathModel::query()->where('cart_id', $cart->id)->exists())->toBeFalse()
        ->and(CartMemberModel::query()->where('cart_id', $cart->id)->exists())->toBeFalse();
});
