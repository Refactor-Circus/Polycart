<?php

declare(strict_types=1);

/**
 * One top-level section of Polycart's composer.json.
 *
 * @return array<string, mixed>
 */
function composerSection(string $key): array
{
    /** @var array<string, mixed> $composer */
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    $section = $composer[$key] ?? [];

    expect($section)->toBeArray();

    /** @var array<string, mixed> $section */
    return $section;
}

it('requires Keystone and no other suite package', function (): void {
    $suite = array_values(array_filter(
        array_keys(composerSection('require')),
        fn (string $package): bool => str_starts_with($package, 'refactor-circus/'),
    ));

    expect($suite)->toBe(['refactor-circus/keystone']);
});

it('keeps Atrium optional: suggested, never required', function (): void {
    expect(composerSection('require'))->not->toHaveKey('refactor-circus/atrium')
        ->and(composerSection('suggest'))->toHaveKey('refactor-circus/atrium');
});

it('refuses an Atrium older than the screen extensions registry', function (): void {
    $conflict = composerSection('conflict');

    expect($conflict)->toHaveKey('refactor-circus/atrium', '<0.1.0');
});

it('builds against the unreleased Atrium and Keen until the group merges', function (): void {
    expect(composerSection('require-dev'))
        ->toHaveKey('refactor-circus/atrium', '^0.1')
        ->toHaveKey('refactor-circus/keen', '^0.1');
});

it('runs the WithoutAtrium suite in composer test, after the main suite', function (): void {
    $scripts = composerSection('scripts');

    expect($scripts)->toHaveKey('test:without-atrium')
        ->and(implode(' ', (array) $scripts['test:without-atrium']))
        ->toContain('--configuration=phpunit.without-atrium.xml')
        ->toContain('--test-directory=tests/WithoutAtrium');

    $test = (array) $scripts['test'];
    $unit = array_search('@test:unit', $test, true);

    expect($unit)->toBeInt()
        ->and($test[$unit + 1] ?? null)->toBe('@test:without-atrium');
});

it('loads the WithoutAtrium suite through the bootstrap that unloads Atrium', function (): void {
    $config = simplexml_load_file(dirname(__DIR__, 2).'/phpunit.without-atrium.xml');

    expect($config)->not->toBeFalse()
        ->and((string) $config['bootstrap'])->toBe('tests/WithoutAtrium/bootstrap.php')
        ->and((string) $config->testsuites->testsuite->directory)->toBe('tests/WithoutAtrium');
});

it('runs the WithoutAtrium suite in CI with Atrium uninstalled', function (): void {
    $workflow = (string) file_get_contents(dirname(__DIR__, 2).'/.github/workflows/tests.yml');

    expect($workflow)
        ->toContain('without-atrium:')
        ->toContain('composer remove --dev refactor-circus/atrium refactor-circus/cortex --no-update')
        ->toContain('composer test:without-atrium');
});
