<?php

declare(strict_types=1);

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Tool as AgentTool;
use Laravel\Ai\Events\InvokingTool;
use Laravel\Ai\Events\ToolInvoked;
use Laravel\Ai\Tools\Request;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use RefactorCircus\Cortex\Domains\McpServer\Actions\CreateMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\McpServer\Data\CreateMcpInstructionVersionData;
use RefactorCircus\Cortex\Domains\McpServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\Tool\Actions\CreateToolDescriptionVersionAction;
use RefactorCircus\Cortex\Domains\Tool\Data\CreateToolDescriptionVersionData;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Facades\Cortex;
use RefactorCircus\Keystone\Cortex\CortexIntegration;
use RefactorCircus\Keystone\Mcp\Server;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Polycart\Domains\Cart\Mcp\Tools\ListCartsTool;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Facades\Polycart;
use RefactorCircus\Polycart\Mcp\PolycartServer;

it('registers the MCP server with Cortex', function (): void {
    $servers = app(McpServerRegistry::class);

    expect($servers->has('polycart'))->toBeTrue()
        ->and($servers->get('polycart'))->toBe(PolycartServer::class)
        ->and($servers->defaultInstructions('polycart'))->toStartWith('Manage carts of every type');
});

it('offers every cart tool to Cortex agents', function (): void {
    $tools = app(ToolRegistry::class);

    $names = array_map(fn (string $class): string => app($class)->name(), Server::withoutHistory(PolycartServer::TOOLS));

    // The history tool only while Polycart audits, and no audit log is installed here.
    expect(array_diff($names, $tools->names()))->toBe([])
        ->and($tools->has('add-lines'))->toBeTrue()
        ->and($tools->has('list-polycart-history-tool'))->toBeFalse()
        ->and(array_intersect($tools->names(), ['list-carts', 'share-cart', 'remove-lines']))->toHaveCount(3)
        ->and($tools->get('create-cart'))->toBeInstanceOf(AgentTool::class);
});

it('tags the cart tools in Cortex', function (): void {
    $tools = app(ToolRegistry::class);

    expect($tools->tagsFor('add-lines'))->toBe(['polycart'])
        ->and(array_column($tools->all('polycart'), 'name'))->toContain('list-carts', 'convert-cart');
});

it('tags the cart tools with the tags in config', function (): void {
    config()->set('polycart.cortex.tags', ['sales', 'carts']);
    app()->forgetInstance(ToolRegistry::class);

    expect(app(ToolRegistry::class)->tagsFor('show-cart'))->toBe(['carts', 'sales']);
});

it('offers only the tools listed in config', function (): void {
    config()->set('polycart.cortex.tools', ['list-carts', 'show-cart']);
    app()->forgetInstance(ToolRegistry::class);

    $tools = app(ToolRegistry::class);

    expect($tools->has('list-carts'))->toBeTrue()
        ->and($tools->has('show-cart'))->toBeTrue()
        ->and($tools->has('add-lines'))->toBeFalse();
});

it('serves the instructions published in Cortex', function (): void {
    app(CreateMcpInstructionVersionAction::class)->execute('polycart', new CreateMcpInstructionVersionData(content: 'Only ever touch quotes.', publish: true));

    expect((new PolycartServer(new FakeTransporter))->createContext()->instructions)->toBe('Only ever touch quotes.');
});

it('serves tool descriptions published in Cortex, to MCP clients and agents alike', function (): void {
    app(CreateToolDescriptionVersionAction::class)->execute('list-carts', new CreateToolDescriptionVersionData(content: 'Find carts for the signed-in customer.', publish: true));

    expect(app(ListCartsTool::class)->description())->toBe('Find carts for the signed-in customer.')
        ->and(app(ToolRegistry::class)->get('list-carts')->description())->toBe('Find carts for the signed-in customer.');
});

it('lets an agent run a cart tool, recording cortex as the source', function (): void {
    $tool = Cortex::tools()->get('create-cart');

    // What an agent run does around each call.
    $events = app('events');
    $agent = Mockery::mock(Agent::class);
    $events->dispatch(new InvokingTool('run', 'call', $agent, $tool, ['type' => 'quote']));
    $result = $tool->handle(new Request(['type' => 'quote', 'label' => 'From an agent']));
    $events->dispatch(new ToolInvoked('run', 'call', $agent, $tool, ['type' => 'quote'], $result, 1.0));

    $quote = CartModel::query()->sole();

    expect((string) $result)->toContain('From an agent')
        ->and($quote->source)->toBe('cortex')
        ->and(Polycart::create('cart')->source)->toBe('cli');
});

it('reports whether the integration is active', function (): void {
    $integration = CortexIntegration::for(app(PackageRegistry::class)->get('polycart'));

    expect($integration->active())->toBeTrue();

    config()->set('polycart.cortex.enabled', false);

    expect($integration->active())->toBeFalse()
        ->and($integration->description('list-carts'))->toBeNull()
        ->and($integration->instructions())->toBeNull();
});
