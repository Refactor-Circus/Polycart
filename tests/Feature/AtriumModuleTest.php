<?php

declare(strict_types=1);

/*
 * Polycart's Atrium module: JavaScript built from atrium/ into the committed
 * dist/atrium/ and declared in composer.json's extra.atrium. The build writes
 * dist/atrium/build.json with the SHA-256 of the files it was built from;
 * this recomputes it as the Atrium SDK's preset does (`hashInputs()`), so a
 * source changed without a rebuild fails here with no Node needed. CI also
 * rebuilds and diffs (.github/workflows/ui.yml).
 */

function atriumModulePath(string $path = ''): string
{
    return dirname(__DIR__, 2).($path === '' ? '' : '/'.$path);
}

/**
 * The committed build's build.json.
 *
 * @return array{key: string, shared_api: string, root: string, inputs: list<string>, hash: string}
 */
function atriumModuleBuild(): array
{
    /** @var array{key: string, shared_api: string, root: string, inputs: list<string>, hash: string} $build */
    $build = json_decode((string) file_get_contents(atriumModulePath('dist/atrium/build.json')), true, flags: JSON_THROW_ON_ERROR);

    return $build;
}

/**
 * The SHA-256 of `inputs` under `root`, as the preset's `hashInputs()` takes it:
 * each file (directories walked; node_modules, dist and dotfiles skipped) as
 * `{path}\0{sha256 of its content, CRLF as LF}\n`, sorted by path.
 *
 * @param  list<string>  $inputs
 */
function atriumModuleSourcesHash(string $root, array $inputs): string
{
    $files = [];

    $walk = function (string $path) use (&$walk, &$files, $root): void {
        $absolute = $root.'/'.$path;

        if (! file_exists($absolute)) {
            return;
        }

        if (is_dir($absolute)) {
            foreach (scandir($absolute) ?: [] as $entry) {
                if (! in_array($entry, ['node_modules', 'dist'], true) && ! str_starts_with($entry, '.')) {
                    $walk($path.'/'.$entry);
                }
            }

            return;
        }

        $files[] = $path;
    };

    foreach ($inputs as $input) {
        $walk(rtrim($input, '/'));
    }

    $files = array_values(array_unique($files));
    sort($files, SORT_STRING);

    $lines = array_map(
        fn (string $path): string => $path."\0".hash('sha256', str_replace("\r\n", "\n", (string) file_get_contents($root.'/'.$path)))."\n",
        $files,
    );

    return hash('sha256', implode('', $lines));
}

it('declares the built script and stylesheet in extra.atrium, and both are committed', function (): void {
    /** @var array{extra: array{atrium: array{key: string, scripts: list<string>, styles: list<string>}}} $composer */
    $composer = json_decode((string) file_get_contents(atriumModulePath('composer.json')), true, flags: JSON_THROW_ON_ERROR);

    $atrium = $composer['extra']['atrium'];

    expect($atrium['key'])->toBe('polycart')
        ->and($atrium['scripts'])->toBe(['dist/atrium/atrium.js'])
        ->and($atrium['styles'])->toBe(['dist/atrium/atrium.css']);

    foreach ([...$atrium['scripts'], ...$atrium['styles']] as $file) {
        expect(atriumModulePath($file))->toBeFile()
            ->and(filesize(atriumModulePath($file)))->toBeGreaterThan(0);
    }
});

it('declares its Atrium integration in the committed manifest the build writes', function (): void {
    /** @var array{extra: array{atrium: array{integration?: string}}} $composer */
    $composer = json_decode((string) file_get_contents(atriumModulePath('composer.json')), true, flags: JSON_THROW_ON_ERROR);

    $integration = $composer['extra']['atrium']['integration'] ?? null;

    expect($integration)->toBe('dist/atrium/integration.json')
        ->and(atriumModulePath((string) $integration))->toBeFile();

    /** @var array{key: string, version: int, screens: list<array{key: string}>} $manifest */
    $manifest = json_decode((string) file_get_contents(atriumModulePath((string) $integration)), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['key'])->toBe('polycart')
        ->and($manifest['version'])->toBe(1)
        ->and(array_column($manifest['screens'], 'key'))->toBe(['polycart.carts', 'polycart.cart', 'polycart.types'])
        ->and($manifest)->toBe(json_decode((string) file_get_contents(atriumModulePath('atrium/src/integration.json')), true, flags: JSON_THROW_ON_ERROR));
});

it('was built for the polycart key and Atrium\'s shared API 1.0', function (): void {
    $build = atriumModuleBuild();

    expect($build['key'])->toBe('polycart')
        ->and($build['shared_api'])->toBe('1.0')
        ->and(realpath(atriumModulePath('dist/atrium').'/'.$build['root']))->toBe(realpath(atriumModulePath('atrium')))
        ->and($build['inputs'])->toContain('src', 'package.json', 'vite.config.ts', 'pnpm-lock.yaml');
});

it('was built from the module\'s sources as they are now', function (): void {
    $build = atriumModuleBuild();
    $root = (string) realpath(atriumModulePath('dist/atrium').'/'.$build['root']);

    expect(atriumModuleSourcesHash($root, $build['inputs']))
        ->toBe($build['hash'], 'dist/atrium is older than its sources: run `pnpm install && pnpm build` in atrium/ and commit dist/atrium.');
});

it('ships dist/atrium and leaves the module\'s sources out of the Composer dist', function (): void {
    $attributes = (string) file_get_contents(atriumModulePath('.gitattributes'));

    expect($attributes)->toMatch('#^/atrium\s+export-ignore$#m')
        ->and($attributes)->not->toMatch('#^/dist\S*\s+export-ignore#m');
});

it('pins the Atrium SDK as a development dependency of the module only, with nothing it brings itself', function (): void {
    /** @var array{name: string, private: bool, devDependencies: array<string, string>, dependencies?: array<string, string>} $package */
    $package = json_decode((string) file_get_contents(atriumModulePath('atrium/package.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($package['name'])->toBe('@refactor-circus/polycart-atrium')
        ->and($package['private'])->toBeTrue()
        ->and($package)->not->toHaveKey('dependencies')
        ->and($package['devDependencies']['@refactor-circus/atrium'])->toBe('github:refactor-circus/atrium#bcca657fb17e5462741517b1fde4e3aadf636f4e&path:ui/sdk')
        ->and($package['devDependencies'])->not->toHaveKeys(['@types/sortablejs', 'unplugin-vue-components'])
        ->and(atriumModulePath('atrium/pnpm-lock.yaml'))->toBeFile();
});

it('builds, type-checks and tests the module in the ui workflow and fails on a stale build', function (): void {
    $workflow = (string) file_get_contents(atriumModulePath('.github/workflows/ui.yml'));

    expect($workflow)
        ->toContain('working-directory: atrium')
        ->toContain('pnpm install --frozen-lockfile')
        ->toContain('pnpm typecheck')
        ->toContain('pnpm test')
        ->toContain('pnpm build')
        ->toContain('git diff --exit-code -- ../dist/atrium');
});
