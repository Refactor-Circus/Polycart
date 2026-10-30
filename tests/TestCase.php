<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Tests;

use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RefactorCircus\Atrium\AtriumServiceProvider;
use RefactorCircus\Atrium\Testing\ResetsScreenExtensions;
use RefactorCircus\Polycart\PolycartServiceProvider;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Organization;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Person;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Product;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Team;
use RefactorCircus\Polycart\Tests\Fixtures\Types\AuditedCart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\CarelessCart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\OpeningCart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\OrderCart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\ProjectCart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\QuoteCart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\RetailCart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\SavedCart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\SetCart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\WholesaleCart;
use Workbench\App\Models\User;

abstract class TestCase extends Orchestra
{
    // What a test registers on Atrium's screens is gone by the next test.
    use ResetsScreenExtensions;

    protected function setUp(): void
    {
        parent::setUp();

        // Views the screen extension tests register as tabs and sections.
        $this->app['view']->addNamespace('polycart-tests', __DIR__.'/Fixtures/views');
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
            AtriumServiceProvider::class,
            PolycartServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // refactor-circus/cortex is a dev dependency, so Atrium discovers its plugin here
        // without its migrations; its navigation would query missing tables.
        $app['config']->set('atrium.disabled', ['cortex']);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('polycart.routes.enabled', true);
        // Surface tests cover behaviour; AccessTest turns authorization on.
        $app['config']->set('polycart.authorization', false);
        $app['config']->set('polycart.owners', ['user' => User::class, 'person' => Person::class]);
        $app['config']->set('polycart.scopes', ['team' => Team::class, 'organization' => Organization::class]);
        $app['config']->set('polycart.purchasables', ['product' => Product::class]);
        $app['config']->set('database.connections.testing.foreign_key_constraints', true);
        $app['config']->set('polycart.types', [
            'cart' => RetailCart::class,
            'saved' => SavedCart::class,
            'quote' => QuoteCart::class,
            'order' => OrderCart::class,
            'project' => ProjectCart::class,
            'set' => SetCart::class,
            'opening' => OpeningCart::class,
            'wholesale' => WholesaleCart::class,
            'audited' => AuditedCart::class,
            'careless' => CarelessCart::class,
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        // Laravel's own migrations give the owner tests a real user to attach.
        $this->loadLaravelMigrations();

        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');
    }
}
