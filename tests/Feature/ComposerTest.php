<?php

declare(strict_types=1);

/**
 * One top-level section of Polycart's composer.json.
 *
 * @return array<array-key, mixed>
 */
function composerSection(string $key): array
{
    /** @var array<string, mixed> $composer */
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    $section = $composer[$key] ?? [];

    expect($section)->toBeArray();

    /** @var array<array-key, mixed> $section */
    return $section;
}

it('requires Keystone and no other suite package', function (): void {
    $suite = array_values(array_filter(
        array_keys(composerSection('require')),
        fn (int|string $package): bool => str_starts_with((string) $package, 'refactor-circus/'),
    ));

    expect($suite)->toBe(['refactor-circus/keystone']);
});

it('names neither Atrium nor PennantPlus in any dependency list', function (string $section): void {
    expect(composerSection($section))
        ->not->toHaveKey('refactor-circus/atrium')
        ->not->toHaveKey('refactor-circus/pennantplus');
})->with(['require', 'require-dev', 'suggest', 'conflict']);

it('has no repository for Atrium or PennantPlus', function (): void {
    $urls = array_map(
        fn (mixed $repository): string => is_array($repository) && is_string($repository['url'] ?? null) ? $repository['url'] : '',
        composerSection('repositories'),
    );

    expect($urls)->not->toBeEmpty()
        ->and(preg_grep('/atrium|pennantplus/i', $urls))->toBe([]);
});

it('declares only its JavaScript Atrium module in extra, with no PHP plugin', function (): void {
    expect(composerSection('extra')['atrium'] ?? null)->toBe([
        'key' => 'polycart',
        'scripts' => ['dist/atrium/atrium.js'],
        'styles' => ['dist/atrium/atrium.css'],
    ])
        ->and(json_encode(composerSection('extra')['laravel'] ?? []))->not->toContain('Atrium');
});

it('has no WithoutAtrium suite in its scripts', function (): void {
    $scripts = composerSection('scripts');

    expect($scripts)->not->toHaveKey('test:without-atrium')
        ->and((array) $scripts['test'])->toBe(['@analyse', '@lint:check', '@test:types', '@test:unit']);
});

it('installs Scramble for development only', function (): void {
    expect(composerSection('require-dev'))->toHaveKey('dedoc/scramble')
        ->and(composerSection('require'))->not->toHaveKey('dedoc/scramble');
});

it('regenerates openapi.json and the client in client:generate', function (): void {
    $steps = (array) composerSection('scripts')['client:generate'];

    expect($steps)->toHaveCount(3)
        ->and((string) $steps[0])->toContain('vendor/bin/testbench workbench:openapi')
        ->and($steps[1])->toBe('npm --prefix client ci')
        ->and($steps[2])->toBe('npm --prefix client run generate');
});

it('builds against Cortex and Keen to test their integrations', function (): void {
    expect(composerSection('require-dev'))
        ->toHaveKey('refactor-circus/cortex', '^2.0')
        ->toHaveKey('refactor-circus/keen', '^2.0');
});
