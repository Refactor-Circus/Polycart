<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Polycart\Domains\Cart\Actions\DeleteCartAction;
use RefactorCircus\Polycart\Domains\Cart\Actions\ListCartsAction;
use RefactorCircus\Polycart\Domains\Cart\Actions\ShowCartAction;
use RefactorCircus\Polycart\Domains\Cart\Actions\UpdateCartAction;
use RefactorCircus\Polycart\Domains\Cart\Data\UpdateCartData;
use RefactorCircus\Polycart\Domains\Cart\Events\CartClearedActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Events\CartCreatedActionEvent;
use RefactorCircus\Polycart\Domains\Cart\Events\CartCreatingEvent;
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartLine\Events\CartLineDeletingEvent;
use RefactorCircus\Polycart\Domains\CartLine\Events\LineAddingActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Events\LinesAddedActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Events\LinesAddingActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Exceptions\LineRejectedException;
use RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel;
use RefactorCircus\Polycart\Domains\CartType\Actions\ListCartTypesAction;
use RefactorCircus\Polycart\Domains\Sharing\Actions\ListMembersAction;
use RefactorCircus\Polycart\Domains\Sharing\Enums\Visibility;
use RefactorCircus\Polycart\Facades\Polycart;
use RefactorCircus\Polycart\Tests\Fixtures\Models\Quote;
use RefactorCircus\Polycart\Tests\Fixtures\Types\QuoteStatus;

/**
 * Record every event of a kind, in order.
 *
 * @param  class-string  $kind
 * @return ArrayObject<int, object>
 */
function recordEvents(string $kind): ArrayObject
{
    /** @var ArrayObject<int, object> $seen */
    $seen = new ArrayObject;

    Event::listen($kind, function (object $event) use ($seen): void {
        $seen->append($event);
    });

    return $seen;
}

it('fires every lifecycle event of a cart', function (): void {
    $seen = recordEvents(ModelLifecycleEvent::class);

    $cart = Polycart::create('cart');
    $cart->label = 'Renamed';
    $cart->save();
    CartModel::query()->find($cart->id);
    $cart->replicate();
    $cart->delete();
    $cart->restore();
    $cart->forceDelete();

    $hooks = collect($seen)
        ->filter(fn (ModelLifecycleEvent $event): bool => $event->model() instanceof CartModel)
        ->map(fn (ModelLifecycleEvent $event): string => $event->hook())
        ->unique()
        ->values()
        ->all();

    expect($hooks)->toEqualCanonicalizing([
        'retrieved', 'creating', 'created', 'updating', 'updated', 'saving', 'saved',
        'deleting', 'deleted', 'restoring', 'restored', 'trashed', 'forceDeleting', 'forceDeleted', 'replicating',
    ]);
});

it('fires the lifecycle events of lines, members and paths', function (): void {
    $seen = recordEvents(ModelLifecycleEvent::class);

    $sales = team(organization());
    $cart = Polycart::create('cart', person($sales), scope: $sales);
    $line = $cart->add(product());
    $cart->updateLine($line, 3);
    $cart->remove($line);

    $models = collect($seen)
        ->map(fn (ModelLifecycleEvent $event): string => class_basename($event->model()).'.'.$event->hook())
        ->unique();

    expect($models)->toContain(
        'CartLineModel.creating', 'CartLineModel.created', 'CartLineModel.updating', 'CartLineModel.updated', 'CartLineModel.deleting', 'CartLineModel.deleted',
        'CartMemberModel.created', 'CartPathModel.created',
    );
});

it('fires the cart events for a cart subclass', function (): void {
    Event::fake([CartCreatingEvent::class]);

    $quote = Polycart::create('quote');

    expect($quote)->toBeInstanceOf(Quote::class);

    Event::assertDispatched(CartCreatingEvent::class, fn (CartCreatingEvent $event): bool => $event->cart instanceof Quote);
});

it('lets a creating listener stop a cart being created', function (): void {
    Event::listen(CartCreatingEvent::class, fn (): bool => false);

    $cart = Polycart::create('cart');

    expect($cart->exists)->toBeFalse()
        ->and(CartModel::query()->count())->toBe(0);
});

it('starts and finishes every action once, in order', function (): void {
    $starts = recordEvents(ActionStartingEvent::class);
    $stops = recordEvents(ActionFinishedEvent::class);

    $sales = team(organization());
    $ann = person($sales);

    $cart = Polycart::active('cart', $ann, $sales);
    $lines = $cart->addLines([['purchasable' => product(sku: 'A')], ['purchasable' => product(sku: 'B')]]);
    $cart->updateLines([['id' => $lines[0], 'quantity' => 2]]);
    $cart->removeLines([$lines[1]]);
    $bob = person($sales);
    Polycart::share($cart, $bob, 'viewer');
    Polycart::unshare($cart, $bob);
    $cart->setVisibility(Visibility::Scope);
    app(UpdateCartAction::class)->execute($cart, new UpdateCartData(label: 'Lobby'));
    app(ShowCartAction::class)->execute($cart);
    app(ListCartsAction::class)->execute();
    app(ListCartTypesAction::class)->execute();
    app(ListMembersAction::class)->execute($cart);
    $quote = $cart->convertTo('quote');
    $quote->transitionTo(QuoteStatus::Submitted);
    Polycart::merge(Polycart::create('cart', 'guest'), $cart);
    $cart->clear();
    app(DeleteCartAction::class)->execute($cart);

    // Every action that started finished, and each has its own pair of events.
    $started = collect($starts)->map(fn (object $event): string => class_basename($event))->unique();
    $finished = collect($stops)->map(fn (object $event): string => class_basename($event))->unique();

    expect($started)->toHaveCount($finished->count())
        ->and(count($starts))->toBe(count($stops))
        ->and($started->all())->toContain(
            'ActiveCartResolvingActionEvent', 'CartCreatingActionEvent', 'LinesAddingActionEvent', 'LineAddingActionEvent',
            'LinesUpdatingActionEvent', 'LineUpdatingActionEvent', 'LinesRemovingActionEvent', 'LineRemovingActionEvent',
            'CartSharingActionEvent', 'CartUnsharingActionEvent', 'VisibilityChangingActionEvent', 'CartUpdatingActionEvent',
            'CartShowingActionEvent', 'CartsListingActionEvent', 'CartTypesListingActionEvent', 'MembersListingActionEvent',
            'CartConvertingActionEvent', 'CartTransitioningActionEvent', 'CartsMergingActionEvent',
            'CartClearingActionEvent', 'CartDeletingActionEvent',
        );
});

it('gives every action exactly one start and one finish event', function (): void {
    $actions = glob(dirname(__DIR__, 2).'/src/Domains/*/Actions/*Action.php') ?: [];

    $unpaired = [];

    foreach ($actions as $path) {
        $source = (string) file_get_contents($path);
        preg_match_all('/([A-Za-z]+ActionEvent)::dispatch/', $source, $matches);

        $kinds = array_map(
            fn (string $event): string => is_subclass_of('RefactorCircus\\Polycart\\Domains\\'.basename(dirname($path, 2)).'\\Events\\'.$event, ActionStartingEvent::class) ? 'start' : 'finish',
            $matches[1],
        );

        sort($kinds);

        if ($kinds !== ['finish', 'start']) {
            $unpaired[] = basename($path, '.php');
        }
    }

    expect($actions)->not->toBeEmpty()
        ->and($unpaired)->toBe([]);
});

it('starts before the work and finishes only once it is committed', function (): void {
    $cart = Polycart::create('cart');
    $linesAtStart = null;

    Event::listen(LineAddingActionEvent::class, function (LineAddingActionEvent $event) use (&$linesAtStart): void {
        $linesAtStart = $event->cart->lines()->count();
    });

    $finished = recordEvents(LinesAddedActionEvent::class);

    DB::transaction(function () use ($cart, $finished): void {
        $cart->addLines([['purchasable' => product()]]);

        expect($finished)->toHaveCount(0);
    });

    expect($linesAtStart)->toBe(0)
        ->and($finished)->toHaveCount(1)
        ->and($finished[0]->lines->first())->toBeInstanceOf(CartLineModel::class);
});

it('starts a failed action but never finishes it', function (): void {
    $starts = recordEvents(LinesAddingActionEvent::class);
    $stops = recordEvents(LinesAddedActionEvent::class);

    expect(fn (): mixed => Polycart::create('quote')->addLines([['purchasable' => product(price: null)]]))
        ->toThrow(LineRejectedException::class);

    expect($starts)->toHaveCount(1)
        ->and($stops)->toHaveCount(0);
});

it('fires no finished event when the surrounding transaction rolls back', function (): void {
    $finished = recordEvents(CartCreatedActionEvent::class);

    try {
        DB::transaction(function (): void {
            Polycart::create('cart', 'session-1');

            throw new RuntimeException('rolled back');
        });
    } catch (RuntimeException) {
        //
    }

    expect($finished)->toHaveCount(0)
        ->and(CartModel::query()->count())->toBe(0);
});

it('rolls back every write of an action that fails inside its transaction', function (): void {
    $cart = Polycart::create('cart');
    $cart->addLines([['purchasable' => product()], ['purchasable' => product(sku: 'LOCK-2')]]);
    $finished = recordEvents(CartClearedActionEvent::class);
    $deleting = 0;

    Event::listen(CartLineDeletingEvent::class, function () use (&$deleting): void {
        if (++$deleting === 2) {
            throw new RuntimeException('refused');
        }
    });

    expect(fn (): mixed => $cart->clear())->toThrow(RuntimeException::class, 'refused');

    expect($finished)->toHaveCount(0)
        ->and($cart->lines()->count())->toBe(2);
});
