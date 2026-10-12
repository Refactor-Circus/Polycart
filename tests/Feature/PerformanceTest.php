<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Polycart\Atrium\PolycartPlugin;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Data\AddLinesData;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;
use RefactorCircus\Polycart\Facades\Polycart;
use RefactorCircus\Polycart\Support\Tables;

/*
 * Query budgets: the paths that run once per line, member or cart must not
 * grow with how many there are. Each test measures the same path at two
 * sizes, or counts the queries a batch makes once.
 */

/**
 * The SQL of every query the callback runs a minute later, in a fresh
 * request's worth of per-request caches.
 *
 * @return array<int, string>
 */
function polycartQueries(Closure $callback): array
{
    // Later than whatever came before, so a touch writes.
    Carbon::setTestNow(Carbon::now()->addMinute());
    app()->forgetScopedInstances();
    DB::flushQueryLog();
    DB::enableQueryLog();
    $callback();
    DB::disableQueryLog();

    return array_map(fn (array $query): string => strtolower((string) $query['query']), DB::getQueryLog());
}

/**
 * How many of the queries match a pattern.
 *
 * @param  array<int, string>  $queries
 */
function polycartCount(array $queries, string $pattern): int
{
    return count(preg_grep($pattern, $queries) ?: []);
}

function cartUpdates(): string
{
    return '/^update "'.Tables::name('carts').'"/';
}

/**
 * A cart holding `$count` lines of different products.
 */
function cartWithLines(int $count): CartModel
{
    $cart = Polycart::create('cart', person());

    foreach (range(1, $count) as $i) {
        $cart->add(product(sku: 'SKU-'.$i));
    }

    return $cart->unsetRelation('lines');
}

it('shows a cart in the dashboard with the same queries however many lines and members it has', function (): void {
    ValidateCsrfToken::except(['*']);
    app()->detectEnvironment(fn (): string => 'local');
    config()->set('polycart.authorization', true);

    $show = function (int $count): int {
        $sales = team(organization(), 'Sales');
        $ann = person($sales);
        $cart = Polycart::create('cart', $ann, scope: $sales);

        foreach (range(1, $count) as $i) {
            $cart->add(product(sku: 'SKU-'.$i));
            Polycart::share($cart, person($sales), 'viewer');
        }

        $this->actingAs($ann);

        return count(polycartQueries(fn () => $this->get(route('atrium.polycart.carts.show', $cart))->assertOk()));
    };

    expect($show(2))->toBe($show(8));
});

it('adds a batch of lines reading the next position and extending the lifetime once', function (): void {
    $cart = cartWithLines(2);
    $products = array_map(fn (int $i): array => ['purchasable' => product(sku: 'NEW-'.$i)], range(1, 5));

    $queries = polycartQueries(fn () => $cart->addLines($products));

    expect(polycartCount($queries, '/max\("position"\)/'))->toBe(1)
        ->and(polycartCount($queries, cartUpdates()))->toBe(1)
        ->and($cart->lines()->pluck('position')->all())->toBe([1, 2, 3, 4, 5, 6, 7]);
});

it('finds a batch of purchasables with one query per kind', function (): void {
    $products = array_map(fn (int $i): array => ['purchasable_type' => 'product', 'purchasable_id' => (string) product(sku: 'NEW-'.$i)->id], range(1, 5));

    $queries = polycartQueries(fn () => AddLinesData::from(['lines' => [...$products, ['quantity' => 1, 'meta' => ['note' => 'custom']]]]));

    expect(polycartCount($queries, '/from "products"/'))->toBe(1);
});

it('refuses a batch naming a missing purchasable', function (): void {
    $cart = Polycart::create('cart', person());

    $this->postJson(route('polycart.carts.lines.store', $cart), ['lines' => [
        ['purchasable_type' => 'product', 'purchasable_id' => (string) product()->id],
        ['purchasable_type' => 'product', 'purchasable_id' => '999999'],
    ]])->assertNotFound();

    expect($cart->lines()->count())->toBe(0);
});

it('changes a batch of lines with one locking read and one lifetime extension', function (): void {
    $cart = cartWithLines(4);
    $changes = $cart->lines()->get()->map(fn (CartLineModel $line): array => ['id' => $line->id, 'quantity' => 3])->all();

    $queries = polycartQueries(fn () => $cart->updateLines($changes));

    expect(polycartCount($queries, '/^select .* from "'.Tables::name('cart_lines').'" where .*"cart_id" = \? .*"id" in \(/'))->toBe(1)
        ->and(polycartCount($queries, '/^select .* from "'.Tables::name('carts').'"/'))->toBe(0)
        ->and(polycartCount($queries, cartUpdates()))->toBe(1);
});

it('removes a batch of lines, and clears a cart, extending the lifetime once', function (): void {
    $cart = cartWithLines(4);
    $ids = $cart->lines()->limit(2)->pluck('id')->all();

    $removed = polycartQueries(fn () => $cart->removeLines($ids));
    $cleared = polycartQueries(fn () => $cart->clear());

    expect(polycartCount($removed, cartUpdates()))->toBe(1)
        ->and(polycartCount($removed, '/^select .* from "'.Tables::name('carts').'"/'))->toBe(0)
        ->and(polycartCount($cleared, cartUpdates()))->toBe(1)
        ->and(polycartCount($cleared, '/^select .* from "'.Tables::name('carts').'"/'))->toBe(0)
        ->and($cart->lines()->count())->toBe(0);
});

it('keeps the caller\'s cart current after a batch of line changes', function (): void {
    Carbon::setTestNow('2026-01-01 12:00:00');
    $cart = cartWithLines(3);
    $lines = $cart->lines()->pluck('id')->all();

    Carbon::setTestNow('2026-01-10 12:00:00');
    $cart->updateLines([['id' => $lines[0], 'quantity' => 5]]);
    expect($cart->expires_at?->toDateString())->toBe('2026-02-24');

    Carbon::setTestNow('2026-01-20 12:00:00');
    $cart->removeLines([$lines[1]]);
    expect($cart->expires_at?->toDateString())->toBe('2026-03-06');

    Carbon::setTestNow('2026-01-30 12:00:00');
    $cart->clear();
    expect($cart->expires_at?->toDateString())->toBe('2026-03-16')
        ->and($cart->fresh()?->expires_at?->toDateString())->toBe('2026-03-16');
});

it('merges a cart reading the next position and extending the lifetime once', function (): void {
    $from = cartWithLines(4);
    $into = Polycart::create('cart', person());
    $into->add(product(sku: 'OTHER'));

    $queries = polycartQueries(fn () => Polycart::merge($from, $into));

    expect(polycartCount($queries, '/max\("position"\)/'))->toBe(1)
        ->and(polycartCount($queries, '/^update "'.Tables::name('carts').'" set "expires_at"/'))->toBe(1)
        ->and($into->lines()->count())->toBe(5);
});

it('counts the carts by type in one query', function (): void {
    Polycart::create('cart', person());
    Polycart::create('quote', person());
    Polycart::create('quote', person());

    $widget = collect(app(PolycartPlugin::class)->widgets())->firstWhere('key', 'polycart.carts-by-type');
    $data = [];

    $queries = polycartQueries(function () use ($widget, &$data): void {
        $data = $widget->resolveData();
    });

    expect($queries)->toHaveCount(1)
        ->and($data['counts']['quote'])->toBe(2)
        ->and($data['counts']['cart'])->toBe(1)
        ->and($data['counts']['order'])->toBe(0);
});
