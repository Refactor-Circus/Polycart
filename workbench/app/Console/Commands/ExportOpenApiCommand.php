<?php

declare(strict_types=1);

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Exports the OpenAPI document of Polycart's JSON API (the `polycart/*`
 * routes), the same on every run: Scramble reads model columns when their
 * tables exist, so the export always reads a fresh, migrated in-memory SQLite
 * database rather than the developer's workbench database. The server URL and
 * version are fixed in WorkbenchServiceProvider.
 *
 * `composer client:generate` runs it to write the committed openapi.json, and
 * tests/Feature/OpenApiTest.php runs it to check that file is current.
 */
final class ExportOpenApiCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'workbench:openapi {path=openapi.json : Where to write the document}';

    /**
     * @var string
     */
    protected $description = "Export the OpenAPI document of Polycart's JSON API";

    public function handle(): int
    {
        config([
            'database.connections.openapi' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'database.default' => 'openapi',
        ]);

        DB::purge('openapi');

        // The package, workbench and Cortex migrations testbench.yaml lists.
        $migrated = $this->callSilently('migrate', ['--database' => 'openapi', '--force' => true]);

        if ($migrated !== self::SUCCESS) {
            $this->components->error('The migrations failed.');

            return self::FAILURE;
        }

        return $this->call('scramble:export', ['--path' => (string) $this->argument('path')]);
    }
}
