<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

const POLYCART_OPENAPI_REGENERATE = 'openapi.json is out of date: run `composer client:generate` and commit the result.';

/**
 * The package root, where openapi.json and client/ live.
 */
function polycartRoot(string $path = ''): string
{
    return dirname(__DIR__, 2).($path === '' ? '' : '/'.$path);
}

/**
 * Run a Testbench command against the workbench, as `composer client:generate`
 * does, and return its output.
 *
 * @param  array<int, string>  $arguments
 */
function polycartTestbench(array $arguments): string
{
    $process = new Process([PHP_BINARY, 'vendor/bin/testbench', ...$arguments], polycartRoot(), timeout: 120);
    $process->run();

    expect($process->getExitCode())->toBe(0, $process->getErrorOutput().$process->getOutput());

    return $process->getOutput();
}

/**
 * A fresh `workbench:openapi` export, decoded. Exported once per run: the
 * export boots the workbench in a child process.
 *
 * @return array<string, mixed>
 */
function polycartExportedOpenApi(): array
{
    static $document = null;

    if ($document === null) {
        $path = (string) tempnam(sys_get_temp_dir(), 'polycart-openapi-');

        try {
            polycartTestbench(['workbench:openapi', $path]);
            $document = polycartDecode((string) file_get_contents($path));
        } finally {
            @unlink($path);
        }
    }

    return $document;
}

/**
 * The names of the `polycart.*` routes the workbench registers (the API is on
 * there).
 *
 * @return array<int, string>
 */
function polycartWorkbenchRouteNames(): array
{
    static $names = null;

    if ($names === null) {
        /** @var array<int, array{name?: string|null}> $routes */
        $routes = json_decode(polycartTestbench(['route:list', '--json', '--name=polycart.']), true, flags: JSON_THROW_ON_ERROR);

        $names = array_values(array_filter(
            array_map(fn (array $route): string => (string) ($route['name'] ?? ''), $routes),
            fn (string $name): bool => str_starts_with($name, 'polycart.'),
        ));
        sort($names);
    }

    return $names;
}

/**
 * @return array<string, mixed>
 */
function polycartDecode(string $json): array
{
    /** @var array<string, mixed> $decoded */
    $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

    return $decoded;
}

/**
 * The committed openapi.json, decoded.
 *
 * @return array<string, mixed>
 */
function polycartOpenApi(): array
{
    return polycartDecode((string) file_get_contents(polycartRoot('openapi.json')));
}

/**
 * Follow `$ref`s (`#/components/...`) until the value is not a reference.
 *
 * @param  array<string, mixed>  $document
 * @return array<array-key, mixed>
 */
function polycartResolve(array $document, mixed $node): array
{
    $node = is_array($node) ? $node : [];

    while (isset($node['$ref']) && is_string($node['$ref'])) {
        $value = $document;

        foreach (explode('/', substr($node['$ref'], 2)) as $key) {
            $key = str_replace(['~1', '~0'], ['/', '~'], $key);
            $value = is_array($value) ? ($value[$key] ?? null) : null;
        }

        $node = is_array($value) ? $value : [];
    }

    return $node;
}

/**
 * An operation of the committed document, by its operationId (the route name).
 *
 * @return array<string, mixed>
 */
function polycartOperation(string $routeName): array
{
    foreach ((array) polycartOpenApi()['paths'] as $item) {
        foreach ((array) $item as $operation) {
            if (is_array($operation) && ($operation['operationId'] ?? null) === $routeName) {
                /** @var array<string, mixed> $operation */
                return $operation;
            }
        }
    }

    return [];
}

/**
 * The operationIds of the committed document.
 *
 * @return array<int, string>
 */
function polycartOperationIds(): array
{
    $ids = [];

    foreach ((array) polycartOpenApi()['paths'] as $item) {
        foreach ((array) $item as $method => $operation) {
            if (in_array($method, ['get', 'put', 'post', 'patch', 'delete'], true) && is_array($operation)) {
                $ids[] = (string) ($operation['operationId'] ?? strtoupper((string) $method).' (no operationId)');
            }
        }
    }

    sort($ids);

    return $ids;
}

/**
 * @return array<string, mixed>
 */
function polycartClientManifest(): array
{
    return polycartDecode((string) file_get_contents(polycartRoot('client/package.json')));
}

it('matches a fresh workbench:openapi export', function (): void {
    expect(polycartExportedOpenApi())->toBe(polycartOpenApi(), POLYCART_OPENAPI_REGENERATE);
});

it('describes Polycart 2.0.0 under a fixed /polycart server', function (): void {
    $document = polycartOpenApi();
    $info = (array) $document['info'];

    expect($info['version'])->toBe('2.0.0')
        ->and($info['title'])->toBe('Polycart')
        ->and(array_column((array) $document['servers'], 'url'))->toBe(['/polycart']);
});

it('has one operation for every polycart route the workbench registers', function (): void {
    $routes = polycartWorkbenchRouteNames();

    expect($routes)->toContain('polycart.stats.show', 'polycart.search.index', 'polycart.carts.index')
        ->and(polycartOperationIds())->toBe($routes, POLYCART_OPENAPI_REGENERATE);
});

it('describes the stats payload', function (): void {
    $document = polycartOpenApi();
    $response = polycartResolve($document, polycartOperation('polycart.stats.show')['responses']['200'] ?? null);
    $body = polycartResolve($document, $response['content']['application/json']['schema'] ?? null);
    $data = polycartResolve($document, $body['properties']['data'] ?? null);

    expect(array_keys((array) ($data['properties'] ?? [])))->toContain('by_type', 'recent');
});

it('requires the search query', function (): void {
    $document = polycartOpenApi();
    $q = array_values(array_filter(
        array_map(fn (mixed $parameter): array => polycartResolve($document, $parameter), (array) (polycartOperation('polycart.search.index')['parameters'] ?? [])),
        fn (array $parameter): bool => ($parameter['name'] ?? null) === 'q' && ($parameter['in'] ?? null) === 'query',
    ));

    expect($q)->toHaveCount(1)
        ->and($q[0]['required'] ?? false)->toBeTrue();
});

it('holds no machine-specific value', function (): void {
    $json = (string) file_get_contents(polycartRoot('openapi.json'));

    expect($json)->not->toContain(polycartRoot(), 'base64:')
        ->and($json)->not->toMatch('#https?://#');
});

it('stamps the client with the sha-256 of openapi.json', function (): void {
    expect(trim((string) file_get_contents(polycartRoot('client/openapi.sha256'))))
        ->toBe(hash_file('sha256', polycartRoot('openapi.json')), 'The TypeScript client is out of date: run `composer client:generate` and commit the result.');
});

it('commits the generated schema and the lockfile', function (string $path): void {
    expect(polycartRoot('client/'.$path))->toBeFile();
})->with(['src/schema.d.ts', 'src/index.ts', 'package-lock.json', 'tsconfig.json', 'scripts/stamp.mjs']);

it('ships the client as a TypeScript ESM package', function (): void {
    $manifest = polycartClientManifest();

    expect($manifest['name'])->toBe('@refactor-circus/polycart-client')
        ->and($manifest['version'])->toBe('2.0.0')
        ->and($manifest['type'])->toBe('module')
        ->and($manifest['types'])->toBe('./src/index.ts')
        ->and($manifest['exports'])->toBe(['.' => ['types' => './src/index.ts', 'default' => './src/index.ts']]);
});

it('depends on openapi-fetch and pins its generators exactly', function (): void {
    $manifest = polycartClientManifest();
    $dependencies = (array) $manifest['dependencies'];
    $devDependencies = (array) $manifest['devDependencies'];

    expect(array_keys($dependencies))->toBe(['openapi-fetch'])
        ->and(array_keys($devDependencies))->toContain('openapi-typescript', 'typescript');

    foreach ([...$dependencies, ...$devDependencies] as $version) {
        expect((string) $version)->toMatch('/^\d+\.\d+\.\d+$/');
    }
});

it('generates the schema and the stamp, and type-checks', function (): void {
    expect((array) polycartClientManifest()['scripts'])
        ->toHaveKey('generate', 'openapi-typescript ../openapi.json -o src/schema.d.ts && node scripts/stamp.mjs')
        ->toHaveKey('typecheck', 'tsc --noEmit');
});

it('exports createPolycartClient and the schema types', function (): void {
    expect((string) file_get_contents(polycartRoot('client/src/index.ts')))
        ->toMatch('/export function createPolycartClient\(options/')
        ->toContain('createClient<paths>(options)')
        ->toMatch('/export type \{[^}]*\bcomponents\b[^}]*\}/')
        ->toMatch('/export type \{[^}]*\boperations\b[^}]*\}/')
        ->toMatch('/export type \{[^}]*\bpaths\b[^}]*\}/');
});
