<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use RefactorCircus\Keen\Domains\Audit\Models\AuditEntryModel;
use RefactorCircus\Keen\Facades\Keen;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Facades\Polycart;
use RefactorCircus\Polycart\Mcp\PolycartServer;
use RefactorCircus\Polycart\Mcp\Tools\ListPolycartHistoryTool;

beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    // Atrium denies access outside local until a gate is defined.
    app()->detectEnvironment(fn (): string => 'local');
});

/**
 * @return Collection<int, AuditEntryModel>
 */
function polycartEntries(string $action): Collection
{
    return AuditEntryModel::query()->where('source', 'polycart')->where('action', $action)->get();
}

it('records adding a line as an entry about the cart', function (): void {
    $cart = Polycart::create('cart', attributes: ['label' => 'Lobby']);
    $line = $cart->add(product(), 2);

    $entry = polycartEntries('line.added')->sole();

    expect($entry->subject_type)->toBe($cart->getMorphClass())
        ->and($entry->subject_id)->toBe($cart->id)
        ->and($entry->subject_label)->toBe('Lobby')
        ->and($entry->context)->toMatchArray(['line' => $line->id, 'quantity' => 2, 'merged' => false]);
});

it('records sharing as an entry about the cart', function (): void {
    $sales = team(organization());
    $cart = Polycart::create('cart', person($sales), scope: $sales);
    $bob = person($sales);

    Polycart::share($cart, $bob, 'viewer');

    $entry = polycartEntries('cart.shared')->sole();

    expect($entry->subject_id)->toBe($cart->id)
        ->and($entry->context)->toMatchArray(['member_id' => (string) $bob->id, 'role' => 'viewer']);
});

it('records a merge on the cart merged into, naming where its lines came from', function (): void {
    $guest = Polycart::create('cart', 'guest-1');
    $guest->add(product());
    $mine = Polycart::create('cart');

    Polycart::merge($guest, $mine);

    $entry = polycartEntries('carts.merged')->sole();

    expect($entry->subject_id)->toBe($mine->id)
        ->and($entry->context)->toMatchArray(['merged_from' => $guest->id]);
});

it('records a copying conversion on the copy, naming the original', function (): void {
    $cart = Polycart::create('cart');
    $cart->add(product());

    $quote = $cart->convertTo('quote');

    $entry = polycartEntries('cart.converted')->sole();

    expect($entry->subject_id)->toBe($quote->id)
        ->and($entry->context)->toMatchArray(['converted_from' => $cart->id, 'from' => 'cart', 'to' => 'quote']);
});

it('keeps the history route, tool and listeners with Keen installed, beside screens that leave history to Keen', function (): void {
    $cart = Polycart::create('cart');
    $cart->add(product());

    // The screens answer as before; history on them is Keen's own section.
    $this->get(route('atrium.polycart.carts.index'))->assertOk();
    $this->get(route('atrium.polycart.carts.show', $cart))->assertOk();

    expect(polycartEntries('line.added'))->toHaveCount(1);

    $query = ['subject_type' => $cart->getMorphClass(), 'subject_id' => $cart->id];

    $this->getJson('/polycart/history?'.http_build_query($query))
        ->assertOk()
        ->assertJsonFragment(['action' => 'line.added']);

    mcpTool(ListPolycartHistoryTool::class, $query)
        ->assertOk()
        ->assertSee('line.added');
});

it('serves a cart\'s history from the history endpoint and tool', function (): void {
    $cart = Polycart::create('cart');
    $cart->add(product());

    $query = ['subject_type' => $cart->getMorphClass(), 'subject_id' => $cart->id];

    $this->getJson('/polycart/history?'.http_build_query($query))
        ->assertOk()
        ->assertJsonFragment(['action' => 'line.added']);

    mcpTool(ListPolycartHistoryTool::class, $query)
        ->assertOk()
        ->assertSee('line.added');
});

it('records the application\'s own events about a cart in its history', function (): void {
    $cart = Polycart::create('cart');

    Keen::record('cart.exported')->source('polycart')->on($cart)->with(['reference' => 'SO-1'])->save();

    $entry = AuditEntryModel::query()->where('action', 'cart.exported')->sole();

    expect($entry->source)->toBe('polycart')
        ->and($entry->subject_id)->toBe($cart->id)
        ->and($entry->context)->toMatchArray(['reference' => 'SO-1']);
});

it('keeps every cart change in the audit log rather than a table of its own', function (): void {
    expect(Schema::hasTable('polycart_cart_activities'))->toBeFalse()
        ->and(Schema::hasColumn('polycart_carts', 'sources'))->toBeFalse()
        ->and(method_exists(CartModel::class, 'activities'))->toBeFalse()
        ->and(method_exists(CartModel::class, 'recordActivity'))->toBeFalse();
});

it('keeps Polycart out of the audit log while polycart.audit.enabled is off', function (): void {
    config()->set('polycart.audit.enabled', false);

    Polycart::create('cart')->add(product());

    expect(AuditEntryModel::query()->where('source', 'polycart')->count())->toBe(0)
        ->and((new PolycartServer(new FakeTransporter))->createContext()->tools()->contains(fn (mixed $tool): bool => $tool instanceof ListPolycartHistoryTool))->toBeFalse();

    $this->getJson('/polycart/history')->assertNotFound();
});
