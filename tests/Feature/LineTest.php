<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Actions\UpdateLineAction;
use RefactorCircus\Polycart\Domains\CartLine\Contracts\PriceResolver;
use RefactorCircus\Polycart\Domains\CartLine\Data\UpdateLineData;
use RefactorCircus\Polycart\Domains\CartLine\Events\LineAddedActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Events\LineRemovedActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Events\LineUpdatedActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Exceptions\LineRejectedException;
use RefactorCircus\Polycart\Facades\Polycart;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Service;

it('adds a purchasable at its own price', function (): void {
    $cart = Polycart::create('cart');
    $product = product(price: 1250);

    $line = $cart->add($product, 2);

    expect($line->purchasable?->is($product))->toBeTrue()
        ->and($line->quantity)->toBe(2)
        ->and($line->unit_price)->toBe(1250)
        ->and($line->total())->toBe(2500)
        ->and($line->isCustom())->toBeFalse()
        ->and($cart->subtotal())->toBe(2500);
});

it('adds to a matching line instead of repeating it', function (): void {
    Event::fake([LineAddedActionEvent::class]);

    $cart = Polycart::create('cart');
    $product = product();

    $first = $cart->add($product, 1, ['finish' => 'satin', 'keying' => ['system' => 'A1', 'cores' => 2]]);
    $second = $cart->add($product, 3, ['keying' => ['cores' => 2, 'system' => 'A1'], 'finish' => 'satin']);

    expect($second->id)->toBe($first->id)
        ->and($second->quantity)->toBe(4)
        ->and($cart->lines)->toHaveCount(1);

    // The second add merged into the first line, and says so.
    Event::assertDispatchedTimes(LineAddedActionEvent::class, 2);
    Event::assertDispatched(LineAddedActionEvent::class, fn (LineAddedActionEvent $event): bool => ! $event->merged);
    Event::assertDispatched(LineAddedActionEvent::class, fn (LineAddedActionEvent $event): bool => $event->merged && $event->line->quantity === 4);
});

it('keeps lines with different options apart and prices each', function (): void {
    $cart = Polycart::create('cart');
    $product = product(price: 1000);

    $cart->add($product, 1, ['finish' => 'satin']);
    $cart->add($product, 1, ['finish' => 'gold']);

    expect($cart->lines->pluck('unit_price')->all())->toBe([1000, 1500])
        ->and($cart->subtotal())->toBe(2500)
        ->and($cart->quantity())->toBe(2);
});

it('prefers an explicit price over the resolved one', function (): void {
    $line = Polycart::create('cart')->add(product(price: 1000), 1, unitPrice: 800);

    expect($line->unit_price)->toBe(800);
});

it('leaves a purchasable that cannot price itself unpriced', function (): void {
    $cart = Polycart::create('cart');
    $cart->add(Service::query()->create());

    expect($cart->lines->first()?->unit_price)->toBeNull()
        ->and($cart->lines->first()?->total())->toBeNull()
        ->and($cart->isFullyPriced())->toBeFalse();
});

it('prices through a bound resolver', function (): void {
    app()->instance(PriceResolver::class, new class implements PriceResolver
    {
        public function price(CartModel $cart, Model $purchasable, array $options): int
        {
            return 42;
        }
    });

    expect(Polycart::create('cart')->add(Service::query()->create())->unit_price)->toBe(42);
});

it('takes custom lines described by their meta', function (): void {
    $cart = Polycart::create('cart');

    $a = $cart->add(null, 1, meta: ['description' => 'Non-catalog hinge'], unitPrice: 900);
    $b = $cart->add(null, 1, meta: ['description' => 'Non-catalog hinge']);
    $c = $cart->add(null, 1, meta: ['description' => 'Site survey']);

    expect($a->isCustom())->toBeTrue()
        ->and($b->id)->toBe($a->id)
        ->and($b->quantity)->toBe(2)
        ->and($c->id)->not->toBe($a->id);
});

it('refuses a quantity below one', function (): void {
    Polycart::create('cart')->add(product(), 0);
})->throws(LineRejectedException::class, 'at least 1');

it('refuses an unpriced line where the type requires prices', function (): void {
    Polycart::create('quote')->add(product(price: null));
})->throws(LineRejectedException::class, 'needs a unit price');

it('refuses what the type does not accept', function (?Model $purchasable): void {
    $project = Polycart::create('project');
    $set = Polycart::create('set', attributes: ['parent_id' => $project->id]);

    Polycart::create('opening', attributes: ['parent_id' => $set->id])->add($purchasable);
})->with([
    'a model it does not hold' => fn (): Service => Service::query()->create(),
    'a custom line' => null,
])->throws(LineRejectedException::class, 'does not accept');

it('lists every add separately and trims meta where the type says so', function (): void {
    $project = Polycart::create('project');
    $set = Polycart::create('set', attributes: ['parent_id' => $project->id]);
    $opening = Polycart::create('opening', attributes: ['parent_id' => $set->id]);
    $product = product();

    $opening->add($product, meta: ['note' => 'Door 101', 'internal' => 'x']);
    $opening->add($product, meta: ['note' => 'Door 101']);

    expect($opening->lines)->toHaveCount(2)
        ->and($opening->lines->first()?->meta)->toBe(['note' => 'Door 101'])
        ->and($opening->lines->pluck('position')->all())->toBe([1, 2]);
});

it('changes a line\'s quantity and removes it at zero', function (): void {
    Event::fake([LineUpdatedActionEvent::class, LineRemovedActionEvent::class]);

    $cart = Polycart::create('cart');
    $line = $cart->add(product(), 2);

    app(UpdateLineAction::class)->execute($line, new UpdateLineData(5));
    expect($line->fresh()?->quantity)->toBe(5);

    expect(app(UpdateLineAction::class)->execute($line, new UpdateLineData(0)))->toBeNull()
        ->and($cart->lines)->toBeEmpty();

    Event::assertDispatched(LineUpdatedActionEvent::class, fn (LineUpdatedActionEvent $event): bool => $event->line?->quantity === 5);
    // A quantity of zero removes the line: the update reports no line left.
    Event::assertDispatched(LineUpdatedActionEvent::class, fn (LineUpdatedActionEvent $event): bool => $event->line === null);
    Event::assertDispatched(LineRemovedActionEvent::class, fn (LineRemovedActionEvent $event): bool => $event->lineId === $line->id);
});

it('checks a changed line against the type', function (): void {
    $quote = Polycart::create('quote');
    $line = $quote->add(product());

    $line->unit_price = null;
    app(UpdateLineAction::class)->execute($line, new UpdateLineData(3));
})->throws(LineRejectedException::class, 'needs a unit price');

it('removes a line and clears a cart', function (): void {
    $cart = Polycart::create('cart');
    $line = $cart->add(product(sku: 'A'));
    $cart->add(product(sku: 'B'));
    $cart->add(product(sku: 'C'));

    $cart->remove($line);
    expect($cart->lines)->toHaveCount(2);

    $cart->clear();
    expect($cart->lines)->toBeEmpty();
});

it('changes and removes several lines at once from code', function (): void {
    $cart = Polycart::create('cart');
    $a = $cart->add(product(sku: 'A'));
    $b = $cart->add(product(sku: 'B'));
    $c = $cart->add(product(sku: 'C'));

    $cart->updateLines([
        ['id' => $a, 'quantity' => 4],
        ['id' => $b->id, 'quantity' => 0],
    ]);

    expect($cart->lines->pluck('quantity', 'id')->all())->toBe([$a->id => 4, $c->id => 1]);

    $cart->removeLines([$a, $c->id]);

    expect($cart->lines()->count())->toBe(0);
});
