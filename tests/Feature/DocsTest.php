<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

const DOCS_SCREEN_KEYS = ['polycart.carts', 'polycart.cart', 'polycart.types'];

function docsPath(string $path = ''): string
{
    return dirname(__DIR__, 2).($path === '' ? '' : '/'.$path);
}

it('names the three screen keys in the README', function (): void {
    expect((string) file_get_contents(docsPath('README.md')))->toContain(...DOCS_SCREEN_KEYS);
});

it('lists the three screen keys in every guide that documents the screens', function (string $file): void {
    expect((string) file_get_contents(docsPath($file)))->toContain(...DOCS_SCREEN_KEYS);
})->with([
    'README.md',
    'CLAUDE.md',
    'docs/sources-and-history.md',
    'resources/boost/skills/polycart-development/SKILL.md',
]);

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
