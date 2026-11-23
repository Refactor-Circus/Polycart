<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

function docsPath(string $path = ''): string
{
    return dirname(__DIR__, 2).($path === '' ? '' : '/'.$path);
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
