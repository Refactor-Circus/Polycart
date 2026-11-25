<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;
use Workbench\App\Providers\WorkbenchServiceProvider;

/**
 * Every file under a path of the package, relative to its root.
 *
 * @return list<string>
 */
function packageFiles(string $path): array
{
    $root = dirname(__DIR__, 2);

    if (is_file($root.'/'.$path)) {
        return [$path];
    }

    $files = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root.'/'.$path, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->isFile()) {
            $files[] = substr($file->getPathname(), strlen($root) + 1);
        }
    }

    sort($files);

    return $files;
}

/**
 * The workbench's testbench.yaml.
 *
 * @return array<string, mixed>
 */
function testbenchYaml(): array
{
    $yaml = Yaml::parseFile(dirname(__DIR__, 2).'/testbench.yaml');

    expect($yaml)->toBeArray();

    /** @var array<string, mixed> $yaml */
    return $yaml;
}

it('mentions Atrium nowhere in the PHP package, its workbench or its build files', function (): void {
    $root = dirname(__DIR__, 2);

    // The Atrium module is JavaScript (atrium/, dist/atrium/) and is allowed to
    // name Atrium where it is declared, shipped and built: composer.json's
    // extra.atrium block, the /atrium export-ignore and the ui workflow.
    $paths = ['src', 'config', 'database', 'workbench', 'testbench.yaml', 'phpunit.xml.dist', '.github'];
    $allowed = ['.github/workflows/ui.yml'];

    $mentions = [];

    foreach ($paths as $path) {
        foreach (packageFiles($path) as $file) {
            if (in_array($file, $allowed, true)) {
                continue;
            }

            if (stripos($file, 'atrium') !== false || stripos((string) file_get_contents($root.'/'.$file), 'atrium') !== false) {
                $mentions[] = $file;
            }
        }
    }

    /** @var array<string, mixed> $composer */
    $composer = json_decode((string) file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    /** @var array<string, mixed> $extra */
    $extra = $composer['extra'];
    unset($extra['atrium']);
    $composer['extra'] = $extra;

    if (stripos((string) json_encode($composer), 'atrium') !== false) {
        $mentions[] = 'composer.json';
    }

    $attributes = array_filter(
        file($root.'/.gitattributes', FILE_IGNORE_NEW_LINES) ?: [],
        fn (string $line): bool => preg_match('#^/atrium\s+export-ignore$#', $line) !== 1,
    );

    if (stripos(implode("\n", $attributes), 'atrium') !== false) {
        $mentions[] = '.gitattributes';
    }

    expect($mentions)->toBe([]);
});

it('runs the Atrium module only in the ui workflow, never in the PHP test workflow', function (): void {
    $workflow = (string) file_get_contents(dirname(__DIR__, 2).'/.github/workflows/tests.yml');

    expect(stripos($workflow, 'atrium'))->toBeFalse()
        ->and(stripos($workflow, 'pnpm'))->toBeFalse();
});

it('has no WithoutAtrium suite or CI lane', function (): void {
    $root = dirname(__DIR__, 2);

    expect($root.'/phpunit.without-atrium.xml')->not->toBeFile()
        ->and($root.'/tests/WithoutAtrium')->not->toBeDirectory()
        ->and((string) file_get_contents($root.'/.github/workflows/tests.yml'))->not->toContain('without-atrium');
});

it('turns the JSON API on in the workbench, with no Atrium or Pennant settings', function (): void {
    config(['polycart.routes.enabled' => false]);

    (new WorkbenchServiceProvider(app()))->register();

    expect(config('polycart.routes.enabled'))->toBeTrue()
        ->and(config()->has('polycart.ui'))->toBeFalse()
        ->and(config('pennant.default'))->not->toBe('pennantplus')
        ->and(config()->has('pennant.stores.pennantplus'))->toBeFalse();
});

it('starts the workbench on a JSON API path, with no Atrium provider, assets or Pennant migrations', function (): void {
    $yaml = testbenchYaml();

    /** @var array<string, mixed> $workbench */
    $workbench = $yaml['workbench'];

    expect($workbench['start'])->toBeString()->toStartWith('/polycart/')
        ->and(implode("\n", (array) $yaml['providers']))->not->toContain('Atrium')
        ->and(implode("\n", (array) $yaml['migrations']))->not->toContain('pennant')
        ->and((array) $workbench['assets'])->toBe(['laravel-assets']);
});

it('seeds no cart from a removed surface', function (): void {
    $seeder = (string) file_get_contents(dirname(__DIR__, 2).'/workbench/database/seeders/DatabaseSeeder.php');

    preg_match_all("/\\\$this->(?:step|lines)\\([^,]+, [^,]+, '([a-z]+)'/", $seeder, $matches);

    expect($matches[1])->not->toBeEmpty()
        ->and(array_values(array_unique($matches[1])))->each->toBeIn(['http', 'mcp', 'code']);
});
