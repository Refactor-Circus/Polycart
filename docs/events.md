# Events

Polycart fires two families of events:

- **Model events:** every Eloquent lifecycle hook of every Polycart model, one class per hook.
- **Action events:** a start event and a finish event for every action. Every operation on a cart is an action, whether it runs through code, the JSON API, MCP, Cortex or the dashboard.

Every event carries the models involved, not just their ids. Every event also uses `Dispatchable` and `SerializesModels`, so it can be dispatched with `::dispatch()` and handled by queued listeners.

This guide covers:

- [Model events](#model-events)
- [Action events](#action-events)
- [Listening to a whole family](#listening-to-a-whole-family)
- [Timing and transactions](#timing-and-transactions)
- [Every action and its events](#every-action-and-its-events)
- [Testing](#testing)

## Model events

Each model fires a class-based event for every Eloquent hook that applies to it:

| Model | Events |
| --- | --- |
| `CartModel` | all 15: `retrieved`, `creating`, `created`, `updating`, `updated`, `saving`, `saved`, `deleting`, `deleted`, `restoring`, `restored`, `trashed`, `forceDeleting`, `forceDeleted`, `replicating` |
| `CartLineModel`, `CartMemberModel`, `CartPathModel` | the 10 that apply to models without soft deletes: `retrieved`, `creating`, `created`, `updating`, `updated`, `saving`, `saved`, `deleting`, `deleted`, `replicating` |

They live in the `Events` namespace of the model's domain, such as `RefactorCircus\Polycart\Domains\CartLine\Events`, and are named `{Entity}{Hook}Event`, the entity being the model's name less its `Model` suffix, for example `CartCreatingEvent` or `CartLineDeletedEvent`. The model is a typed property: `$event->cart`, `$event->line`, `$event->member`, `$event->path`. It is also available as `$event->model()`, alongside `$event->hook()`.

```php
use RefactorCircus\Polycart\Domains\CartLine\Events\CartLineSavingEvent;

Event::listen(CartLineSavingEvent::class, function (CartLineSavingEvent $event) {
    $event->line->meta = [...$event->line->meta, 'checked_at' => now()->toIso8601String()];
});
```

About how they fire:

- **Synchronous:** they fire when Eloquent fires the hook, as Eloquent's own events do.
- **Cancelling:** a `creating`, `updating`, `saving`, `deleting`, `restoring` or `forceDeleting` listener that returns `false` stops the operation.
- **Subclasses:** a cart that hydrates as a subclass, such as your `Quote extends CartModel`, fires the `Cart*` events. A listener sees every cart, whatever its class.
- **Your own mapping:** entries you declare on a subclass's `$dispatchesEvents` win over the derived ones.

The mapping is done by the `DispatchesModelEvents` trait from [refactor-circus/keystone](https://github.com/Refactor-Circus/Keystone) (`RefactorCircus\Keystone\Models\Concerns`), the convention every Refactor Circus package shares.

## Action events

Every action dispatches two events:

1. **A start event** (`…ingActionEvent`, e.g. `LineAddingActionEvent`), before the action does any work. It carries the action's input.
2. **A finish event** (`…edActionEvent`, e.g. `LineAddedActionEvent`), once the action has succeeded. It carries the result.

```php
use RefactorCircus\Polycart\Domains\Cart\Events\CartConvertedActionEvent;
use RefactorCircus\Polycart\Domains\CartLine\Events\LineAddingActionEvent;

Event::listen(LineAddingActionEvent::class, function (LineAddingActionEvent $event) {
    Log::info('Adding to cart', ['cart' => $event->cart->id, 'quantity' => $event->quantity]);
});

Event::listen(CartConvertedActionEvent::class, function (CartConvertedActionEvent $event) {
    if ($event->to === 'order') {
        SendOrderToErp::dispatch($event->cart);
    }
});
```

- **Failure:** an action that throws fires its start event and no finish event.
- **Nesting:** actions that call other actions fire both sets. Adding a batch fires `LinesAddingActionEvent`, then a `LineAdding`/`LineAdded` pair for each line, then `LinesAddedActionEvent`.

Action events live in the `Events` namespace of their domain, such as `RefactorCircus\Polycart\Domains\Cart\Events`.

## Listening to a whole family

Each family implements an interface in `RefactorCircus\Keystone\Contracts`, shared by every Refactor Circus package, and Laravel delivers an event to listeners of the interfaces it implements:

| Interface | Receives |
| --- | --- |
| `ModelLifecycleEvent` | every model event |
| `ActionStartingEvent` | every action start |
| `ActionFinishedEvent` | every action finish |

```php
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

Event::listen(ActionFinishedEvent::class, fn (ActionFinishedEvent $event) => Metrics::increment(class_basename($event)));
```

## Timing and transactions

| Family | Dispatched |
| --- | --- |
| Model events | Immediately, as Eloquent fires the hook |
| Action start events | Immediately, before the action touches the database |
| Action finish events | After the surrounding transaction commits (`ShouldDispatchAfterCommit`), and not at all if it rolls back |

Every action does its writes in one transaction of its own (a savepoint inside yours), so a failure part-way leaves nothing behind. Because finish events wait for the commit, listeners never hear about work that was undone. That covers:

- a batch that was refused part-way, or a cart clear that failed part-way
- a conversion that failed validation
- your own `DB::transaction()` that threw after calling Polycart

Outside a transaction, finish events fire straight away.

## Every action and its events

A start event carries the action's input as its Data object (`$event->data`, or `$event->filters` for a list), the same object the action was called with.

| Action | Start event (carries) | Finish event (carries) |
| --- | --- | --- |
| `ActiveCartAction` | `ActiveCartResolvingActionEvent` (`data`: `ActiveCartData`, `owner`, `scope`) | `ActiveCartResolvedActionEvent` (`cart`) |
| `AddLineAction` | `LineAddingActionEvent` (`cart`, `data`: `AddLineData`) | `LineAddedActionEvent` (`cart`, `line`, `merged`) |
| `AddLinesAction` | `LinesAddingActionEvent` (`cart`, `data`: `AddLinesData`) | `LinesAddedActionEvent` (`cart`, `lines`) |
| `ClearCartAction` | `CartClearingActionEvent` (`cart`) | `CartClearedActionEvent` (`cart`) |
| `ConvertCartAction` | `CartConvertingActionEvent` (`cart`, `data`: `ConvertCartData`) | `CartConvertedActionEvent` (`source`, `cart`, `from`, `to`, `copy`) |
| `CreateCartAction` | `CartCreatingActionEvent` (`data`: `CreateCartData`, `owner`, `scope`) | `CartCreatedActionEvent` (`cart`) |
| `DeleteCartAction` | `CartDeletingActionEvent` (`cart`) | `CartDeletedActionEvent` (`cart`) |
| `ListCartTypesAction` | `CartTypesListingActionEvent` (none) | `CartTypesListedActionEvent` (`types`) |
| `ListCartsAction` | `CartsListingActionEvent` (`filters`: `ListCartsData`, `viewer`) | `CartsListedActionEvent` (`carts`, `viewer`) |
| `ListMembersAction` | `MembersListingActionEvent` (`cart`) | `MembersListedActionEvent` (`cart`, `members`) |
| `MergeCartsAction` | `CartsMergingActionEvent` (`from`, `into`) | `CartsMergedActionEvent` (`from`, `into`) |
| `RemoveLineAction` | `LineRemovingActionEvent` (`cart`, `line`) | `LineRemovedActionEvent` (`cart`, `lineId`) |
| `RemoveLinesAction` | `LinesRemovingActionEvent` (`cart`, `data`: `RemoveLinesData`) | `LinesRemovedActionEvent` (`cart`, `lineIds`) |
| `SetVisibilityAction` | `VisibilityChangingActionEvent` (`cart`, `data`: `SetVisibilityData`) | `VisibilityChangedActionEvent` (`cart`, `from`, `to`) |
| `ShareCartAction` | `CartSharingActionEvent` (`cart`, `member`, `data`: `ShareCartData`) | `CartSharedActionEvent` (`cart`, `member`) |
| `ShowCartAction` | `CartShowingActionEvent` (`cart`) | `CartShownActionEvent` (`cart`) |
| `TransitionCartAction` | `CartTransitioningActionEvent` (`cart`, `data`: `TransitionCartData`) | `CartTransitionedActionEvent` (`cart`, `from`, `to`) |
| `UnshareCartAction` | `CartUnsharingActionEvent` (`cart`, `member`) | `CartUnsharedActionEvent` (`cart`, `memberType`, `memberId`) |
| `UpdateCartAction` | `CartUpdatingActionEvent` (`cart`, `data`: `UpdateCartData`) | `CartUpdatedActionEvent` (`cart`, `changed`) |
| `UpdateLineAction` | `LineUpdatingActionEvent` (`cart`, `line`, `data`: `UpdateLineData`) | `LineUpdatedActionEvent` (`cart`, `line`) |
| `UpdateLinesAction` | `LinesUpdatingActionEvent` (`cart`, `data`: `UpdateLinesData`) | `LinesUpdatedActionEvent` (`cart`) |

Notes on particular events:

- **`LineAddedActionEvent`:** `merged` is true when the add went into a matching line.
- **`LineUpdatedActionEvent`:** `line` is `null` when a quantity of zero removed the line.
- **`LineRemovedActionEvent`, `LinesRemovedActionEvent`, `CartUnsharedActionEvent`:** these carry ids, because the row is gone.
- **`CartConvertedActionEvent`:** `source` is the original cart. When the cart was retyped in place, `source` and `cart` are the same row.

## Testing

```php
use RefactorCircus\Polycart\Domains\CartLine\Events\LineAddedActionEvent;

Event::fake([LineAddedActionEvent::class]);

$cart->add($product, 2);

Event::assertDispatched(LineAddedActionEvent::class, fn ($event) => $event->line->quantity === 2 && ! $event->merged);
```

Fake only the events you assert on. A bare `Event::fake()` also stops the model hooks a cart needs when it is created: its initial status, expiry, place in the tree, and the creator's membership.
