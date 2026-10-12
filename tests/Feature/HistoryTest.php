<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Transport\FakeTransporter;
use RefactorCircus\Polycart\Mcp\PolycartServer;
use RefactorCircus\Polycart\Mcp\Tools\ListPolycartHistoryTool;

it('serves the history route inside the JSON API', function (): void {
    expect(route('polycart.history.index', absolute: false))->toBe('/polycart/history');
});

it('answers 404 from the history route while no audit log is installed', function (): void {
    $this->getJson('/polycart/history')
        ->assertNotFound()
        ->assertJson(['message' => 'No audit log is installed. Install refactor-circus/keen to record history.']);
});

it('declares the history tool, but offers it only while Polycart audits', function (): void {
    expect(PolycartServer::TOOLS)->toContain(ListPolycartHistoryTool::class)
        ->and(app(ListPolycartHistoryTool::class)->name())->toBe('list-polycart-history-tool')
        ->and((new PolycartServer(new FakeTransporter))->createContext()->tools()->contains(fn (mixed $tool): bool => $tool instanceof ListPolycartHistoryTool))->toBeFalse();
});
