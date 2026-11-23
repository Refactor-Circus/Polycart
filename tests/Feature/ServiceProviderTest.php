<?php

declare(strict_types=1);

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use RefactorCircus\Keystone\Auth\Authorizer;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Polycart\Domains\Cart\CartServiceProvider;
use RefactorCircus\Polycart\Domains\CartLine\Contracts\PriceResolver;
use RefactorCircus\Polycart\Domains\CartLine\Services\PurchasablePriceResolver;
use RefactorCircus\Polycart\Mcp\PolycartServer;
use RefactorCircus\Polycart\Polycart;
use RefactorCircus\Polycart\PolycartServiceProvider;

function packagePath(string $path): string
{
    return dirname(__DIR__, 2).'/'.$path;
}

it('resolves the entry point as a singleton', function (): void {
    expect(app(Polycart::class))->toBe(app(Polycart::class));
});

it('merges the package config', function (): void {
    expect(config('polycart.default_type'))->toBe('cart')
        ->and(config('polycart.prune_after_days'))->toBe(30);
});

it('prices through purchasables by default', function (): void {
    expect(app(PriceResolver::class))->toBeInstanceOf(PurchasablePriceResolver::class);
});

it('publishes the config and migrations', function (string $tag): void {
    expect(ServiceProvider::pathsToPublish(PolycartServiceProvider::class, $tag))->not->toBeEmpty();
})->with(['polycart', 'polycart-config', 'polycart-migrations']);

it('publishes only the config and migrations under the polycart tag', function (): void {
    $sources = array_map(
        fn (string $path): string|false => realpath($path),
        array_keys(ServiceProvider::pathsToPublish(PolycartServiceProvider::class, 'polycart')),
    );

    expect($sources)->toEqualCanonicalizing([
        realpath(packagePath('config/polycart.php')),
        realpath(packagePath('database/migrations')),
    ]);
});

it('publishes no views or translations', function (): void {
    expect(ServiceProvider::publishableGroups())->not->toContain('polycart-views')
        ->and(ServiceProvider::publishableGroups())->not->toContain('polycart-lang')
        ->and(ServiceProvider::pathsToPublish(PolycartServiceProvider::class, 'polycart-views'))->toBe([])
        ->and(ServiceProvider::pathsToPublish(PolycartServiceProvider::class, 'polycart-lang'))->toBe([]);
});

it('loads no polycart views or translations', function (): void {
    expect(app('view')->getFinder()->getHints())->not->toHaveKey('polycart')
        ->and(app('translator')->getLoader()->namespaces())->not->toHaveKey('polycart');
});

it('registers no polycartCan Blade directive', function (): void {
    $directives = Blade::getCustomDirectives();

    expect($directives)->not->toHaveKey('polycartCan')
        ->and($directives)->not->toHaveKey('endpolycartCan')
        ->and($directives)->not->toHaveKey('unlesspolycartCan');
});

it('registers with the shared runtime, authorizing through the Gate by default', function (): void {
    $package = app(PackageRegistry::class)->get('polycart');

    $config = config('polycart');
    unset($config['authorization']);
    config()->set('polycart', $config);

    expect($package->server)->toBe(PolycartServer::class)
        ->and($package->label)->toBe('Polycart')
        ->and(Authorizer::for($package)->enabled())->toBeTrue();
});

it('ships a config with no dashboard sections', function (): void {
    $contents = (string) file_get_contents(packagePath('config/polycart.php'));

    /** @var array<string, mixed> $config */
    $config = require packagePath('config/polycart.php');

    expect($config)->not->toHaveKey('ui')
        ->and($config)->not->toHaveKey('atrium')
        ->and($contents)->not->toContain('PolycartSupportFeature')
        ->and(stripos($contents, 'atrium'))->toBeFalse()
        ->and(stripos($contents, 'dashboard'))->toBeFalse();
});

it('keeps the JSON API off by default, and says what describes it and how to expose it', function (): void {
    $contents = (string) file_get_contents(packagePath('config/polycart.php'));

    /** @var array{routes: array{enabled: bool}} $config */
    $config = require packagePath('config/polycart.php');

    expect($config['routes']['enabled'])->toBeFalse()
        ->and($contents)->toContain('openapi.json', 'client', 'auth middleware');
});

it('registers the stats and search routes only while the JSON API is on', function (): void {
    $routes = function (bool $enabled): array {
        app('router')->setRoutes(new RouteCollection);
        config()->set('polycart.routes.enabled', $enabled);

        app()->register(CartServiceProvider::class, force: true);
        app('router')->getRoutes()->refreshNameLookups();

        return ['stats' => Route::has('polycart.stats.show'), 'search' => Route::has('polycart.search.index')];
    };

    expect($routes(false))->toBe(['stats' => false, 'search' => false])
        ->and($routes(true))->toBe(['stats' => true, 'search' => true]);
});
