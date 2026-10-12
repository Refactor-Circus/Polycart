<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Mcp\Server\Tool;
use RefactorCircus\Keystone\Testing\McpTools;
use RefactorCircus\Keystone\Testing\Parity;
use RefactorCircus\Polycart\Mcp\PolycartServer;
use RefactorCircus\Polycart\Tests\CortexTestCase;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Organization;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Person;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Product;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Team;
use RefactorCircus\Polycart\Tests\KeenTestCase;
use RefactorCircus\Polycart\Tests\PennantPlusTestCase;
use RefactorCircus\Polycart\Tests\RenamedTablesTestCase;
use RefactorCircus\Polycart\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');
uses(CortexTestCase::class)->in('Cortex');
uses(PennantPlusTestCase::class)->in('PennantPlus');
uses(KeenTestCase::class)->in('Keen');
uses(RenamedTablesTestCase::class)->in('Modes/RenamedTables');

function product(?int $price = 1000, string $sku = 'LOCK-1', ?int $stock = null): Product
{
    return Product::query()->create(['sku' => $sku, 'price' => $price, 'stock' => $stock]);
}

function organization(string $name = 'Acme'): Organization
{
    return Organization::query()->create(['name' => $name]);
}

function team(Organization $organization, string $name = 'Team'): Team
{
    return Team::query()->create(['organization_id' => $organization->id, 'name' => $name]);
}

function person(Team ...$teams): Person
{
    $person = Person::query()->create(['name' => 'Person']);
    $person->teams()->attach(array_map(fn (Team $team): int => $team->id, $teams));

    return $person;
}

/**
 * Call a Polycart tool as an MCP client does, with its answer checked
 * against the tool's output schema.
 *
 * @param  class-string<Tool>  $tool
 * @param  array<string, mixed>  $arguments
 */
function mcpTool(string $tool, array $arguments = []): TestResponse
{
    return McpTools::call(PolycartServer::class, $tool, $arguments);
}

/**
 * Call a Polycart tool as the given user, as `mcpTool()` does.
 *
 * @param  class-string<Tool>  $tool
 * @param  array<string, mixed>  $arguments
 */
function mcpToolAs(Authenticatable $user, string $tool, array $arguments = []): TestResponse
{
    test()->actingAs($user);

    return mcpTool($tool, $arguments);
}

/**
 * Actions no surface calls directly, and why, by the surfaces they are
 * absent from.
 *
 * @var array<string, array<int, string>>
 */
const PARITY_EXCEPTIONS = [
    // The API and MCP only add in batches; each line in a batch goes through
    // this action, as does $cart->add() in code.
    'AddLineAction' => ['http', 'mcp'],
    'UpdateLineAction' => ['http', 'mcp'],
    'RemoveLineAction' => ['http', 'mcp'],
];

/**
 * Actions missing from a surface, keyed by surface.
 *
 * Every use case is reachable from both the JSON API and the MCP server, so
 * this is empty. A new Action without a request on each side fails the arch
 * test rather than shipping half-exposed.
 *
 * @return array<string, array<int, string>>
 */
function parityGaps(): array
{
    $root = dirname(__DIR__).'/src';

    return Parity::gaps($root.'/Domains/*/Actions/*.php', [
        'http' => $root.'/Domains/*/Http/Requests/*.php',
        'mcp' => $root.'/Domains/*/Mcp/Requests/*.php',
    ], PARITY_EXCEPTIONS);
}
