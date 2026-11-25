<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

function docsPath(string $path = ''): string
{
    return dirname(__DIR__, 2).($path === '' ? '' : '/'.$path);
}

function docsContents(string $path): string
{
    return (string) file_get_contents(docsPath($path));
}

/**
 * The README, the agent guides, the Boost skill and every guide in docs/.
 *
 * @return list<string>
 */
function packageDocs(): array
{
    $guides = array_map(
        fn (SplFileInfo $file): string => 'docs/'.$file->getFilename(),
        iterator_to_array(Finder::create()->files()->in(docsPath('docs'))->depth(0)->name('*.md')->sortByName(), false),
    );

    return ['README.md', 'CLAUDE.md', 'AGENTS.md', 'resources/boost/skills/polycart-development/SKILL.md', ...$guides];
}

it('leaves the audit trail component and the History nav item out of the code and docs', function (): void {
    $files = iterator_to_array(Finder::create()
        ->files()
        ->in([docsPath('src'), docsPath('resources'), docsPath('docs')])
        ->notPath('agent/specs')
        ->append([docsPath('README.md'), docsPath('CLAUDE.md')]), false);

    expect($files)->not->toBeEmpty();

    $offenders = [];

    foreach ($files as $file) {
        $contents = (string) file_get_contents((string) $file);

        foreach (['audit-trail', 'historyNavItem'] as $needle) {
            if (str_contains($contents, $needle)) {
                $offenders[] = str_replace(docsPath().'/', '', (string) $file).': '.$needle;
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('documents the API, the MCP tools, the OpenAPI document and the client in the README', function (string $promise): void {
    expect(docsContents('README.md'))->toContain($promise);
})->with([
    'polycart.stats.show',
    'polycart.search.index',
    'cart-stats',
    'polycart.routes.enabled',
    'openapi.json',
    'createPolycartClient',
    'client:generate',
    'Atrium v2',
]);

it('says in the README that the API is off by default and needs auth middleware', function (): void {
    $readme = docsContents('README.md');

    expect($readme)
        ->toContain('**off by default**')
        ->toContain('auth middleware')
        ->toContain('"by_type"')
        ->toContain('"recent"');

    foreach (['## Atrium dashboard', 'Screen extensions', 'Operators', 'show_all'] as $removed) {
        expect($readme)->not->toContain($removed);
    }
});

it('describes no Atrium plugin in the docs', function (string $file): void {
    $contents = docsContents($file);

    $needles = [
        'src/Atrium',
        'ScreenAccess',
        '@polycartCan',
        'PolycartSupportFeature',
        'polycart.atrium',
        'polycart.ui',
        'polycart-views',
        'polycart-lang',
        'CartTypeRow',
        '`atrium`',
    ];

    $found = array_values(array_filter($needles, fn (string $needle): bool => str_contains($contents, $needle)));

    // The v1 screen keys, quoted as code or strings (route names such as polycart.carts.index are fine).
    preg_match_all('/[`\'"]polycart\.(?:carts?|types)[`\'"]/', $contents, $screenKeys);

    expect([...$found, ...$screenKeys[0]])->toBe([]);
})->with(packageDocs());

it('keeps CLAUDE.md and AGENTS.md identical', function (): void {
    expect(docsContents('CLAUDE.md'))->toBe(docsContents('AGENTS.md'));
});

it('documents the client regeneration for agents', function (string $file): void {
    expect(docsContents($file))
        ->toContain('composer client:generate')
        ->toContain('cart-stats')
        ->toContain('polycart.stats.show')
        ->toContain('polycart.search.index');
})->with(['AGENTS.md', 'resources/boost/skills/polycart-development/SKILL.md']);

it('has a v2.0.0 CHANGELOG section that points upgraders to Atrium v2 and the API switch', function (): void {
    $changelog = docsContents('CHANGELOG.md');

    $section = preg_match('/^## \[v2\.0\.0\].*?(?=^## \[)/ms', $changelog, $matches) === 1 ? $matches[0] : '';

    expect($section)
        ->not->toBe('')
        ->toContain('### Breaking')
        ->toContain('### Added')
        ->toContain('Atrium v2')
        ->toContain('polycart.routes.enabled')
        ->toContain('polycart-views')
        ->toContain('polycart-lang')
        ->toContain('polycart.stats.show')
        ->toContain('polycart.search.index')
        ->toContain('cart-stats')
        ->toContain('openapi.json')
        ->toContain('@refactor-circus/polycart-client');
});
