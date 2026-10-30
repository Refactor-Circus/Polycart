<?php

declare(strict_types=1);

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\ListCartsTool;
use RefactorCircus\Polycart\Domains\CartType\Mcp\Tools\ListCartTypesTool;
use RefactorCircus\Polycart\Facades\Polycart;
use RefactorCircus\Polycart\Mcp\PolycartServer;
use RefactorCircus\Polycart\PolycartServiceProvider;
use RefactorCircus\Polycart\Tests\WithoutAtrium\TestCase;

/**
 * The names of every registered route.
 *
 * @return array<int, string>
 */
function routeNames(): array
{
    return array_values(array_filter(array_map(
        fn (Route $route): ?string => $route->getName(),
        Router::getRoutes()->getRoutes(),
    )));
}

it('cannot load any Atrium class', function (): void {
    expect(class_exists(TestCase::ATRIUM.'Atrium'))->toBeFalse()
        ->and(class_exists(TestCase::ATRIUM.'AtriumServiceProvider'))->toBeFalse()
        ->and(class_exists(TestCase::ATRIUM.'Support\ScreenAccess'))->toBeFalse();
});

it('boots the package without Atrium', function (): void {
    expect(app()->isBooted())->toBeTrue()
        ->and(app()->getProvider(PolycartServiceProvider::class))->not->toBeNull()
        ->and(app(PackageRegistry::class)->get('polycart')->server)->toBe(PolycartServer::class)
        ->and($this->loadedAtriumClasses())->toBe([]);
});

it('registers no Atrium route', function (): void {
    $names = routeNames();

    expect($names)->toContain('polycart.carts.index')
        ->and(array_filter($names, fn (string $name): bool => str_starts_with($name, 'atrium.')))->toBe([]);
});

it('answers the JSON API', function (): void {
    $cart = Polycart::create('cart');

    $this->getJson(route('polycart.carts.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $cart->id)
        ->assertJsonPath('data.0.type', 'cart');

    expect($this->loadedAtriumClasses())->toBe([]);
});

it('lists the MCP tools', function (): void {
    PolycartServer::tools()->assertRegistered([ListCartTypesTool::class, ListCartsTool::class]);

    expect($this->loadedAtriumClasses())->toBe([]);
});
