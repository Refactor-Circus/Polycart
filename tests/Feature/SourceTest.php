<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Keystone\Support\Surface;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\ConvertCartTool;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\CreateCartTool;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Sharing\Enums\Visibility;
use RefactorCircus\Polycart\Facades\Polycart;

it('records the surface of carts made directly', function (): void {
    // The test runner is a console process, so Keystone's Surface says `cli`.
    expect(Polycart::create('cart')->source)->toBe('cli');
});

it('stamps carts made inside a callback and restores the surface after', function (): void {
    $imported = Polycart::usingSource('import', fn (): CartModel => Polycart::create('cart'));
    $enum = Polycart::usingSource(Visibility::Scope, fn (): CartModel => Polycart::create('cart'));

    expect($imported->source)->toBe('import')
        ->and($enum->source)->toBe('scope')
        ->and(Polycart::create('cart')->source)->toBe('cli');
});

it('restores the surface when the callback throws', function (): void {
    try {
        Polycart::usingSource('import', fn () => throw new RuntimeException('boom'));
    } catch (RuntimeException) {
        //
    }

    expect(Polycart::create('cart')->source)->toBe('cli');
});

it('records carts made through the JSON API', function (): void {
    $this->postJson('/polycart/carts', ['type' => 'cart'])
        ->assertCreated()
        ->assertJsonPath('data.source', 'http');
});

it('records carts made through MCP', function (): void {
    mcpTool(CreateCartTool::class, ['type' => 'cart'])->assertOk();

    expect(CartModel::query()->sole()->source)->toBe('mcp')
        ->and(Polycart::create('cart')->source)->toBe('cli');
});

it('records copies made from the dashboard as the dashboard\'s', function (): void {
    ValidateCsrfToken::except(['*']);
    app()->detectEnvironment(fn (): string => 'local');

    $cart = Polycart::create('cart');
    $cart->add(product());

    $this->post(route('atrium.polycart.carts.convert', $cart), ['to' => 'quote', 'copy' => '1'])->assertRedirect();

    expect(CartModel::query()->ofType('quote')->sole()->source)->toBe('atrium')
        ->and($cart->fresh()?->source)->toBe('cli');
});

it('lets an application name the surface of its own routes', function (): void {
    app(Surface::class)->route('checkout.', 'web');

    Route::post('/checkout-web', fn (): string => Polycart::create('cart')->source ?? '')->name('checkout.store');

    $this->post('/checkout-web')->assertSee('web');
});

it('filters carts by source', function (): void {
    Polycart::create('cart');
    Polycart::usingSource('import', fn (): CartModel => Polycart::create('cart'));

    expect(CartModel::query()->fromSource('import')->count())->toBe(1)
        ->and(CartModel::query()->fromSource('cli', 'import')->count())->toBe(2);

    $this->getJson('/polycart/carts?source=import')->assertOk()->assertJsonCount(1, 'data');
});

it('records the surface of the conversion on the converted cart', function (bool $copy): void {
    $cartId = $this->postJson('/polycart/carts', ['type' => 'cart'])->assertCreated()->json('data.id');
    CartModel::query()->findOrFail($cartId)->add(product());

    mcpTool(ConvertCartTool::class, ['cart' => $cartId, 'to' => 'order', 'copy' => $copy])->assertOk();

    $order = CartModel::query()->ofType('order')->sole();

    expect($order->source)->toBe('mcp')
        ->and($order->id === $cartId)->toBe(! $copy);

    if ($copy) {
        expect(CartModel::query()->findOrFail($cartId)->source)->toBe('http');
    }
})->with(['copied' => true, 'in place' => false]);
