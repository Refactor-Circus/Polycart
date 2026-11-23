# Adding lines

Every line added to a cart goes through a **pipeline**: an ordered list of stages, each doing one step. Each cart type chooses its own stages. That lets a wholesale cart check stock and apply bulk pricing, while a quote skips both.

This guide covers:

- [How it works](#how-it-works)
- [The default stages](#the-default-stages)
- [The pending line](#the-pending-line)
- [Writing a stage](#writing-a-stage)
- [Rejecting a line](#rejecting-a-line)
- [Configuring stages per cart type](#configuring-stages-per-cart-type)
- [Adding several lines at once](#adding-several-lines-at-once)
- [Over the JSON API and MCP](#over-the-json-api-and-mcp)
- [Transactions, events and history](#transactions-events-and-history)
- [What the pipeline does not cover](#what-the-pipeline-does-not-cover)

## How it works

```php
$cart->add($product, 2, options: ['finish' => '626']);
```

This builds a `PendingLine` and sends it through the cart type's `addLineStages()` with Laravel's `Pipeline`, inside one database transaction. Each stage reads and changes the pending line, then passes it on. By the end, the line has been written.

Any stage can refuse the line. When one does, the transaction is rolled back and the cart is left exactly as it was: no line, no quantity change, no event, and so no history entry.

Every way of adding a line uses the same pipeline:

- `$cart->add()`
- `$cart->addLines()`
- the JSON API
- MCP
- merging carts

## The default stages

| # | Stage | Does |
| --- | --- | --- |
| 1 | `PrepareLine` | Refuses a quantity below 1. Runs the type's `prepareOptions()` and `prepareMeta()`. Works out the line's `fingerprint` (its identity). |
| 2 | `CheckAccepted` | Refuses what the type's `accepts()` rejects |
| 3 | `FindMatchingLine` | Finds and locks the line this add merges into, when the type `mergesLines()` |
| 4 | `ResolvePrice` | Prices a new line through the type's `price()`, unless a price was given. A merge keeps the existing line's price unless a price was given. |
| 5 | `BuildLine` | Builds the `CartLineModel` to write, without saving it. For a merge, that is the existing line with its quantity increased. |
| 6 | `ValidateLine` | Runs the type's `validate()`, such as its price requirement |
| 7 | `WriteLine` | Saves the line |

All of them live in `RefactorCircus\Polycart\Domains\CartLine\Support\Stages`.

Where to put your own stages:

| To | Put the stage |
| --- | --- |
| Check the customer or the product, before any work | after `CheckAccepted` |
| Change the price (discounts, surcharges, contract pricing) | after `ResolvePrice` |
| Check the final quantity (stock, limits, credit) | after `BuildLine` |
| Act on the saved line (reserve stock, notify a system) | after `WriteLine`. It still runs inside the transaction, so a rejection there undoes the write. |

## The pending line

`RefactorCircus\Polycart\Domains\CartLine\Support\PendingLine` is what the stages pass along.

**Fixed for the whole add** (readonly):

| Property | Holds |
| --- | --- |
| `cart` | The cart being added to |
| `type` | The cart's `CartType` |
| `actor` | The signed-in user, or `null` |
| `source` | The current surface: `http`, `mcp`, `cortex`, `cli`, `code`, or yours. See [Sources and history](sources-and-history.md). |

**Changed by stages:**

| Property | Holds |
| --- | --- |
| `purchasable` | The model being added, or `null` for a custom line |
| `quantity` | The quantity being added now |
| `options`, `meta` | The line's options and meta |
| `unitPrice` | The unit price in minor units, or `null` |
| `fingerprint` | Set by `PrepareLine` |
| `existing` | The line this add merges into, set by `FindMatchingLine` |
| `line` | The `CartLineModel` being written, set by `BuildLine` and saved by `WriteLine` |
| `context` | A free array for passing notes between your own stages |

**Helpers:**

| Method | Returns |
| --- | --- |
| `merging()` | Whether this add merges into an existing line |
| `resultingQuantity()` | The quantity the line will end up with: the existing quantity plus this add |
| `reject($message, $reason)` | Refuses the line. See below. |

## Writing a stage

A stage is a class with a `handle()` method that does its step and then calls `$next`:

```php
use Closure;
use RefactorCircus\Polycart\Domains\CartLine\Contracts\AddLineStage;
use RefactorCircus\Polycart\Domains\CartLine\Support\PendingLine;

final class CheckStock implements AddLineStage
{
    public function __construct(private readonly Inventory $inventory) {}   // resolved from the container

    public function handle(PendingLine $line, Closure $next): PendingLine
    {
        $available = $this->inventory->available($line->purchasable);

        if ($line->resultingQuantity() > $available) {
            $line->reject("Only {$available} left.", 'out_of_stock');
        }

        return $next($line);
    }
}
```

About the forms a stage can take:

- **Interface:** implementing `AddLineStage` is optional, but it documents the shape.
- **Container:** stages are resolved from the container, so constructor injection works.
- **Arguments:** a stage can take arguments from its entry in the list, `ApplyBulkDiscount::class.':10,20'`:

  ```php
  final class ApplyBulkDiscount
  {
      public function handle(PendingLine $line, Closure $next, string $minimum, string $percent): PendingLine
      {
          if ($line->unitPrice !== null && $line->resultingQuantity() >= (int) $minimum) {
              $line->unitPrice = intdiv($line->unitPrice * (100 - (int) $percent), 100);
          }

          return $next($line);
      }
  }
  ```

- **Closures:** a closure works as a stage too:

  ```php
  function (PendingLine $line, Closure $next): PendingLine {
      $line->meta['added_via'] = $line->source;

      return $next($line);
  }
  ```

A stage must either call `$next($line)` or reject the line. One that returns without doing either would otherwise look like a silent success, so Polycart refuses the line with reason `stopped`.

## Rejecting a line

```php
$line->reject('This customer is on credit hold.', 'credit_hold');
```

This throws a `RefactorCircus\Polycart\Domains\CartLine\Exceptions\LineRejectedException` with:

- `getMessage()`: text for people
- `$e->reason`: a stable code for programs, such as `out_of_stock`, so clients can branch without parsing the message
- `$e->index`: the line's position when it came from a batch, otherwise `null`

You can also throw one yourself with `LineRejectedException::because($message, $reason)`.

```php
try {
    $cart->add($product, 5);
} catch (LineRejectedException $e) {
    return back()->withErrors(['cart' => $e->getMessage()]);
}
```

The package's own refusals have codes too:

| Reason | When |
| --- | --- |
| `invalid_quantity` | The quantity is below 1 |
| `not_accepted` | The type's `accepts()` refused the purchasable |
| `missing_price` | The type requires prices and the line has none |
| `stopped` | A stage returned without passing the line on or rejecting it |

## Configuring stages per cart type

Override `addLineStages()` on the type. You can start from the defaults and slot stages in with two helpers, so your type keeps any stages the package adds later:

```php
use RefactorCircus\Polycart\Domains\CartLine\Support\Stages\BuildLine;
use RefactorCircus\Polycart\Domains\CartLine\Support\Stages\CheckAccepted;
use RefactorCircus\Polycart\Domains\CartLine\Support\Stages\ResolvePrice;
use RefactorCircus\Polycart\Domains\CartType\Support\CartType;

class WholesaleCart extends CartType
{
    public function addLineStages(): array
    {
        $stages = parent::addLineStages();

        $stages = self::insertStagesAfter($stages, CheckAccepted::class, [EnsureCustomerCanBuy::class]);
        $stages = self::insertStagesAfter($stages, ResolvePrice::class, [ApplyBulkDiscount::class.':10,20']);

        return self::insertStagesAfter($stages, BuildLine::class, [CheckStock::class]);
    }
}
```

- **The helpers:** `insertStagesBefore($stages, $existing, [...])` and `insertStagesAfter(...)` place stages next to an existing one, or at the end if it isn't in the list.
- **Replacing the list:** return your own array to reorder, replace, or drop stages. For example, swap `ResolvePrice` for your own ERP pricing stage. Keep `BuildLine` and `WriteLine`, or something that does their job, or no line is written.
- **Sharing stages:** stages common to several types can live on a shared parent type.

## Adding several lines at once

```php
$lines = $cart->addLines([
    ['purchasable' => $lock, 'quantity' => 12, 'options' => ['finish' => '626']],
    ['purchasable' => $closer, 'quantity' => 12],
    ['purchasable' => null, 'meta' => ['description' => 'Delivery'], 'unit_price' => 2500],
]);
```

Each entry takes `purchasable`, `quantity` (default 1), `options`, `meta` and `unit_price`, and goes through the pipeline in order.

The batch is **all or nothing**. If any line is refused, none of the batch is written, and the refusal names the line:

```php
catch (LineRejectedException $e) {
    $e->index;          // 1
    $e->reason;         // 'out_of_stock'
    $e->getMessage();   // 'Line 1: Only 3 left.'
}
```

Later lines in a batch see the earlier ones. Two entries for the same item merge into one line, and a stock check on the second sees the first one's quantity.

A batch can hold up to `AddLinesAction::MAX_LINES` (100) lines.

## Over the JSON API and MCP

The API and MCP only add, change and remove lines in batches, even for a single line. `PATCH .../lines` (`update-lines`) and `DELETE .../lines` (`remove-lines`) work the same way as adding:

- they take a list of up to 100 lines
- they are all or nothing
- a refused entry is reported by position; a line that isn't in the cart is refused with reason `line_not_found`
- they return the whole cart with its totals

Adding works like this:

```http
POST /polycart/carts/{cart}/lines
Content-Type: application/json

{
  "lines": [
    { "purchasable_type": "product", "purchasable_id": "42", "quantity": 2, "options": { "finish": "626" } },
    { "meta": { "description": "Delivery" }, "unit_price": 2500 }
  ]
}
```

- **Success:** `201` with `data` listing every line written.
- **Refusal:** `422`:

  ```json
  { "message": "Line 0: Only 1 left.", "reason": "out_of_stock", "line": 0 }
  ```

- **Invalid input:** Laravel's usual validation errors, keyed by position (`lines.0.quantity`).

The MCP tool is `add-lines`, with the same `lines` array. A refusal reads `Line 0: Only 1 left. (reason: out_of_stock)`.

Both need the `update` ability on the cart when `polycart.authorization` is on. Purchasables are named by aliases from `polycart.purchasables`.

## Transactions, events and history

- **One transaction:** the pipeline runs in a single database transaction, and so does a batch. Nested transactions become savepoints.
- **Events:** adding fires `LineAddingActionEvent` before the pipeline runs and `LineAddedActionEvent` (with `merged`) once it commits, and a batch wraps these in `LinesAdding`/`LinesAddedActionEvent`. Finish events wait for the commit, so listeners never hear about a line that was rolled back. See [Events](events.md).
- **History:** with refactor-circus/keen installed, the audit log records `line.added` from the finish event, so a rolled-back add leaves no entry.
- **Expiry:** the cart's expiry is pushed back once the line is written.

## What the pipeline does not cover

The pipeline runs when lines are **added**. Other changes use the type's `validate()` directly:

- changing a line's quantity (`$cart->updateLine()`, `$cart->updateLines()`, `PATCH .../lines`, `update-lines`)
- lines carried into another type by a conversion

To enforce a rule such as stock on those as well, put it in `validate()` too. Conversions run inside a transaction, so a refusal there also writes nothing.
