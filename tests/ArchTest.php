<?php

declare(strict_types=1);

use RefactorCircus\Keystone\Testing\Arch;
use RefactorCircus\Polycart\Support\Tables;

const POLYCART_DOMAINS = __DIR__.'/../src/Domains';

arch()->preset()->php();

arch()->preset()->security();

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->each->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('RefactorCircus\Polycart')
    ->toUseStrictTypes();

arch('actions are final')
    ->expect(Arch::domainNamespaces('RefactorCircus\Polycart', POLYCART_DOMAINS, 'Actions'))
    ->classes()
    ->toBeFinal()
    ->toHaveMethod('execute');

arch('actions take their input as Data objects, not arrays')
    ->expect(fn (): array => array_values(array_filter(
        array_map(function (string $action): ?string {
            foreach ((new ReflectionMethod($action, 'execute'))->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType && $type->getName() === 'array' && in_array($parameter->getName(), ['data', 'filters', 'input', 'attributes', 'values', 'lines', 'changes'], true)) {
                    return $action.'::execute($'.$parameter->getName().')';
                }
            }

            return null;
        }, Arch::domainClasses('RefactorCircus\Polycart', POLYCART_DOMAINS, 'Actions')),
    )))
    ->toBeEmpty();

arch('action input validates itself')
    ->expect(fn (): array => Arch::dataWithoutRules(Arch::domainClasses('RefactorCircus\Polycart', POLYCART_DOMAINS, 'Data')))
    ->toBeEmpty();

arch('every use case is reachable from the JSON API and MCP')
    ->expect(fn (): array => parityGaps())
    ->toBeEmpty();

arch('every action dispatches a starting and a finished event')
    ->expect(fn (): array => Arch::actionEventGaps(POLYCART_DOMAINS.'/*/Actions/*.php'))
    ->toBeEmpty();

arch('actions write inside a transaction')
    ->expect(fn (): array => Arch::writesOutsideTransactions(POLYCART_DOMAINS.'/*/Actions/*.php'))
    ->toBeEmpty();

arch('every model fires an event for every lifecycle hook')
    ->expect(fn (): array => Arch::modelEventGaps(Arch::domainClasses('RefactorCircus\Polycart', POLYCART_DOMAINS, 'Models')))
    ->toBeEmpty();

arch('requests validate once, with the Data rules, and build from what they validated')
    ->expect(fn (): array => array_values(array_filter(
        [...(array) glob(POLYCART_DOMAINS.'/*/Http/Requests/*.php'), ...(array) glob(POLYCART_DOMAINS.'/*/Mcp/Requests/*.php')],
        fn (string $path): bool => str_contains((string) file_get_contents($path), 'Data::from('),
    )))
    ->toBeEmpty();

arch('every MCP request has a tool on the server')
    ->expect(function (): array {
        $server = (string) file_get_contents(dirname(__DIR__).'/src/Mcp/PolycartServer.php');

        return array_values(array_filter(
            array_map(fn (string $path): string => basename($path, 'McpRequest.php'), (array) glob(dirname(__DIR__).'/src/Domains/*/Mcp/Requests/*McpRequest.php')),
            fn (string $name): bool => ! str_contains($server, $name.'Tool::class'),
        ));
    })
    ->toBeEmpty();

arch('domain models are named for their entity and end in Model')
    ->expect(array_map(
        fn (string $domain): string => 'RefactorCircus\\Polycart\\Domains\\'.basename($domain).'\Models',
        (array) glob(dirname(__DIR__).'/src/Domains/*/Models', GLOB_ONLYDIR),
    ))
    ->toHaveSuffix('Model');

arch('every model has a policy in the config')
    ->expect(fn (): array => Arch::modelsWithoutPolicies(
        Arch::domainClasses('RefactorCircus\Polycart', POLYCART_DOMAINS, 'Models'),
        (require __DIR__.'/../config/polycart.php')['policies'],
    ))
    ->toBeEmpty();

arch('tables are named through Tables, never as literals')
    ->expect(fn (): array => Arch::literalTableNames(
        [...array_values(array_filter(
            array_map(fn (SplFileInfo $file): string => $file->getPathname(), iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../src', FilesystemIterator::SKIP_DOTS)))),
            fn (string $path): bool => str_ends_with($path, '.php') && ! str_ends_with($path, 'Support/Tables.php'),
        )), ...(array) glob(__DIR__.'/../database/migrations/*.php')],
        array_values(Tables::DEFAULTS),
    ))
    ->toBeEmpty();
