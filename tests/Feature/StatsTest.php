<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Polycart\Domains\Cart\Actions\ShowCartStatsAction;
use RefactorCircus\Polycart\Domains\Cart\Events\CartStatsShowingActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Events\CartStatsShownActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\Cart\Support\CartStats;
use RefactorCircus\Polycart\Facades\Polycart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\OrderCart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\QuoteCart;
use RefactorCircus\Polycart\Tests\Fixtures\Types\RetailCart;

function showCartStats(?Model $viewer = null): CartStats
{
    return app(ShowCartStatsAction::class)->execute($viewer);
}

/**
 * Set a cart's columns without touching its timestamps or firing its events.
 *
 * @param  array<string, mixed>  $columns
 */
function stampCart(CartModel $cart, array $columns): void
{
    CartModel::query()->whereKey($cart->id)->toBase()->update($columns);
}

it('counts carts per registered type in registry order, with zeros', function (): void {
    config()->set('polycart.types', ['quote' => QuoteCart::class, 'cart' => RetailCart::class, 'order' => OrderCart::class]);

    Polycart::create('cart');
    Polycart::create('quote');
    Polycart::create('quote');

    expect(showCartStats()->byType)->toBe(['quote' => 2, 'cart' => 1, 'order' => 0]);
});

it('counts no expired, deleted or unregistered-type carts, and lists expired ones as recent', function (): void {
    $live = Polycart::create('cart');
    $expired = Polycart::create('cart');
    $deleted = Polycart::create('cart');
    $retired = Polycart::create('cart');

    stampCart($expired, ['expires_at' => Carbon::now()->subDay()]);
    stampCart($retired, ['type' => 'retired']);
    $deleted->delete();

    $stats = showCartStats();

    expect($stats->byType['cart'])->toBe(1)
        ->and($stats->byType)->not->toHaveKey('retired')
        ->and(array_sum($stats->byType))->toBe(1)
        ->and($stats->recent->modelKeys())->toEqualCanonicalizing([$live->id, $expired->id, $retired->id]);
});

it('counts and lists only the carts the viewer can access', function (): void {
    $sales = team(organization(), 'Sales');
    $ann = person($sales);
    $bob = person($sales);

    $mine = Polycart::create('cart', $ann, scope: $sales);
    Polycart::create('cart', $bob, scope: $sales);
    Polycart::create('quote', $bob, scope: $sales);

    $stats = showCartStats($ann);

    expect($stats->byType['cart'])->toBe(1)
        ->and($stats->byType['quote'])->toBe(0)
        ->and($stats->recent->modelKeys())->toBe([$mine->id]);

    // Without a viewer, every cart.
    expect(showCartStats()->byType['cart'])->toBe(2)
        ->and(showCartStats()->byType['quote'])->toBe(1);
});

it('lists the five most recently updated carts, ties by id, with their line counts', function (): void {
    $carts = array_map(fn (int $i): CartModel => Polycart::create('cart'), range(0, 6));
    $carts[6]->add(product(sku: 'A'));
    $carts[6]->add(product(sku: 'B'));

    foreach (range(0, 4) as $day) {
        stampCart($carts[$day], ['updated_at' => Carbon::parse('2026-01-01')->addDays($day)]);
    }

    // The two newest share a timestamp, so the larger id comes first.
    stampCart($carts[5], ['updated_at' => Carbon::parse('2026-02-01')]);
    stampCart($carts[6], ['updated_at' => Carbon::parse('2026-02-01')]);
    $tied = [$carts[5]->id, $carts[6]->id];
    rsort($tied, SORT_STRING);

    $recent = showCartStats()->recent;

    expect($recent->modelKeys())->toBe([...$tied, $carts[4]->id, $carts[3]->id, $carts[2]->id])
        ->and($recent->firstWhere('id', $carts[6]->id)?->lines_count)->toBe(2)
        ->and($recent->firstWhere('id', $carts[4]->id)?->lines_count)->toBe(0)
        ->and($recent->contains(fn (CartModel $cart): bool => $cart->relationLoaded('lines')))->toBeFalse();
});

it('announces the read before it and the result after it', function (): void {
    $viewer = person(team(organization()));
    Polycart::create('cart', $viewer);

    /** @var array<int, string> $seen */
    $seen = [];
    $shown = null;

    DB::listen(function () use (&$seen): void {
        $seen[] = 'query';
    });

    Event::listen(CartStatsShowingActionEvent::class, function (CartStatsShowingActionEvent $event) use (&$seen, $viewer): void {
        expect($event->viewer)->toBe($viewer);
        $seen[] = 'showing';
    });

    Event::listen(CartStatsShownActionEvent::class, function (CartStatsShownActionEvent $event) use (&$seen, &$shown, $viewer): void {
        expect($event->viewer)->toBe($viewer);
        $shown = $event->stats;
        $seen[] = 'shown';
    });

    $stats = showCartStats($viewer);

    expect($seen[0])->toBe('showing')
        ->and($seen[count($seen) - 1])->toBe('shown')
        ->and($seen)->toContain('query')
        ->and($shown)->toBe($stats);
});

it('reads the stats in the same queries however many carts and types there are', function (): void {
    $sales = team(organization(), 'Sales');
    $ann = person($sales);
    $all = config('polycart.types');

    $queries = function () use ($ann): int {
        app()->forgetScopedInstances();
        DB::flushQueryLog();
        DB::enableQueryLog();
        showCartStats($ann);
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    Polycart::create('cart', $ann, scope: $sales)->add(product());
    $oneCart = $queries();

    foreach (range(2, 20) as $i) {
        Polycart::create($i % 2 === 0 ? 'quote' : 'cart', $ann, scope: $sales)->add(product(sku: 'SKU-'.$i));
    }

    $twentyCarts = $queries();

    config()->set('polycart.types', array_slice($all, 0, 1, true));
    $oneType = $queries();

    config()->set('polycart.types', $all);
    $tenTypes = $queries();

    expect($all)->toHaveCount(10)
        ->and($twentyCarts)->toBe($oneCart)
        ->and($tenTypes)->toBe($oneType)
        ->and($tenTypes)->toBe($oneCart);
});
