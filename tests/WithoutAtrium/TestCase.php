<?php

declare(strict_types=1);

namespace RefactorCircus\Polycart\Tests\WithoutAtrium;

use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RefactorCircus\Polycart\PolycartServiceProvider;

/**
 * Boots Polycart the way an application without refactor-circus/atrium
 * does: no Atrium provider, and (through bootstrap.php) no Atrium class to
 * load. It does not extend the main TestCase, which uses Atrium's testing
 * traits.
 */
abstract class TestCase extends Orchestra
{
    /**
     * The namespace no class may be loaded from.
     */
    public const string ATRIUM = 'RefactorCircus\\Atrium\\';

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
            PolycartServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('polycart.routes.enabled', true);
        // Surface wiring is what this suite proves; AccessTest covers authorization.
        $app['config']->set('polycart.authorization', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__, 2).'/database/migrations');
    }

    /**
     * Every class, interface, trait and enum declared so far from Atrium's
     * namespace.
     *
     * @return array<int, string>
     */
    protected function loadedAtriumClasses(): array
    {
        return array_values(array_filter(
            [...get_declared_classes(), ...get_declared_interfaces(), ...get_declared_traits()],
            fn (string $class): bool => str_starts_with($class, self::ATRIUM),
        ));
    }
}
