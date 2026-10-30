# Sources and history

Polycart keeps two kinds of record about a cart:

- **`source`** on the cart: the surface it was created through, or last converted through.
- **Its history**, kept by [refactor-circus/keen](https://github.com/Refactor-Circus/Keen), the audit log every Refactor Circus package shares: one entry per change, with who made it, through which surface, what changed and why. Polycart keeps no history of its own; without Keen installed, carts keep none.

This guide covers:

- [Sources](#sources)
- [Setting the source](#setting-the-source)
- [History](#history)
- [What gets recorded](#what-gets-recorded)
- [Where a cart came from](#where-a-cart-came-from)
- [Recording your own events](#recording-your-own-events)
- [Reading history](#reading-history)
- [In the Atrium dashboard](#in-the-atrium-dashboard)
- [Upgrading from the activity log](#upgrading-from-the-activity-log)

## Sources

A cart's `source` is the surface it was created through, as refactor-circus/keystone's `Surface` names it, the same name the audit log records with every entry:

| Source | When |
| --- | --- |
| `http` | The JSON API (any `polycart.*` route) |
| `mcp` | The MCP server |
| `cortex` | A Cortex agent calling a cart tool. See [Cortex](cortex.md). |
| `atrium` | The Atrium dashboard |
| `cli` | A console command or queued job, outside any of the above |
| `code` | Anything else |

A source is stored as a plain string, so you can name your own (`web`, `import`, `pos`). A cart converted in place, or copied by a conversion, takes the source of the conversion.

## Setting the source

**Around a block of code**, with `Polycart::usingSource()`, a shortcut for Keystone's `Surface::using()`:

```php
use RefactorCircus\Polycart\Facades\Polycart;

Polycart::usingSource('import', function () use ($rows) {
    foreach ($rows as $row) {
        Polycart::create('cart', $row['customer'])->add(Product::find($row['id']), $row['qty']);
    }
});
```

The surface is restored afterwards, even when the callback throws, and blocks can be nested. Audit entries recorded inside the block carry the same surface.

**On your own routes**, name the surface for a route-name prefix once, from a service provider:

```php
use RefactorCircus\Keystone\Support\Surface;

app(Surface::class)->route('checkout.', 'web');
```

API and MCP callers cannot choose a source; it always names the surface they used.

## History

With refactor-circus/keen installed, every change made through Polycart's actions is recorded as an entry with source `polycart`:

| Field | Meaning |
| --- | --- |
| `action` | What happened, as `noun.verb`: `line.added`, `cart.transitioned`, `carts.merged` |
| `subject` | The cart the entry is about, labelled with its `label` or id |
| `actor` | The signed-in user, when there is one |
| `surface` | `http`, `mcp`, `cortex`, `atrium`, `cli`, `code` or yours |
| `changes` | The cart's fields that changed, as `[old, new]` |
| `context` | Details of the change, such as the line and quantity |

Entries are append-only and hash-chained; see Keen's README for retention, verification and authorization.

## What gets recorded

Every action Polycart takes announces itself through its action events, and Keen records the finished ones. Reads (`*Listed`, `*Shown`) are not recorded.

| Action | When | Subject | Context |
| --- | --- | --- | --- |
| `cart.created` | A cart is created | the cart | |
| `cart.updated` | The label or meta changes | the cart | |
| `cart.deleted` | The cart is deleted | the cart | |
| `cart.cleared` | Every line is removed at once | the cart | |
| `cart.transitioned` | The status moves | the cart | `from`, `to` |
| `cart.converted` | The cart is retyped in place, or copied into another type | the converted cart (the copy, or the retyped cart) | `from`, `to`, `copy`, and on a copy `converted_from` (the original's id) |
| `carts.merged` | Another cart's lines were merged into this one | the cart merged into | `merged_from` (the merged cart's id) |
| `line.added` / `lines.added` | Lines are added | the cart | `line`, `purchasable_type`, `purchasable_id`, `quantity`, `merged` / `lines` |
| `line.updated` / `lines.updated` | A line's quantity or price changes | the cart | `line`, `quantity`, `unit_price` |
| `line.removed` / `lines.removed` | Lines are removed | the cart | `line` / `lines` |
| `cart.shared` | A member is added, or their role changes | the cart | `member_type`, `member_id`, `role` |
| `cart.unshared` | A member is removed | the cart | `member_type`, `member_id` |
| `visibility.changed` | Visibility changes | the cart | `from`, `to` |

The line, sharing, merge and conversion events implement `RefactorCircus\Keystone\Audit\Contracts\Auditable` to name the cart as their subject. Carts and lines are labelled through Keystone's `AuditHooks`.

## Where a cart came from

A cart's history answers where it came from. A cart that received another's lines has a `carts.merged` entry naming the merged cart in `merged_from`; a copy made by a conversion has a `cart.converted` entry naming the original in `converted_from`. Follow the id to read the other cart's own history. Histories are no longer copied from cart to cart.

## Recording your own events

Record your application's own events with Keen's facade. They are stored with source `app`:

```php
use RefactorCircus\Keen\Facades\Keen;

Keen::record('cart.exported')->source('polycart')->on($cart)->with(['reference' => $salesOrder->number])->save();
```

## Reading history

| Surface | Read |
| --- | --- |
| JSON API | `GET /polycart/history?subject_type=...&subject_id=...` (named `polycart.history.index`), filterable by `action`, cursor paginated |
| MCP | `list-polycart-history-tool` with the same arguments |
| Keen | Its own API, MCP tools and Atrium screens, across every package |

`subject_type` is the cart's morph class (`$cart->getMorphClass()`). Reading one cart's history needs `view` on that cart; reading the whole of Polycart's needs the `viewAuditLog` Gate ability when your application defines it. Both answer "not installed" until an audit log is.

Carts can still be listed by source: `CartModel::query()->fromSource('mcp')`, `GET /polycart/carts?source=mcp`, or `list-carts {source}`.

## In the Atrium dashboard

The cart page shows the cart's source. Its history comes from Keen, not Polycart: Polycart's pages draw no history and its navigation has no History item. Instead, Polycart declares its pages as screens on Atrium's screen extensions registry, and Keen's History section and nav items attach to them. So the cart history UI needs an Atrium with the registry and a Keen with its History section; until both are installed, the dashboard shows no history (the JSON API and MCP tool above still answer while Polycart audits).

| Screen key | Page | Kind | Record | Slots |
| --- | --- | --- | --- | --- |
| `polycart.carts` | Carts | list | none; each row is its `CartModel` | `header.actions`, `before`, `after`, `row.actions` |
| `polycart.cart` | A cart | detail | the `CartModel` | `header.actions`, `before`, `aside`, `after` |
| `polycart.types` | Cart types | list | none; each row is a `CartTypeRow` | `header.actions`, `before`, `after`, `row.actions` |

Any package can add tabs and sections to these screens the same way Keen does. A `permission()` on an extension is decided by Polycart's `RefactorCircus\Polycart\Atrium\ScreenAccess`, the rule the pages' own controls follow, so it is shown to dashboard operators and to everyone while `polycart.authorization` is off. An extension that needs a check of its own uses `authorize()`.

## Upgrading from the activity log

Polycart used to keep its own activity log. It is replaced by Keen:

- `polycart_cart_activities` is no longer created, and the `sources` column on `polycart_carts` is gone from new installs. Existing installs keep both, untouched and unused; drop them when you no longer need the old entries.
- `$cart->activities`, `CartActivityModel`, the `Activity` and `CartSource` enums, `SourceContext`, the `CartSource` middleware, `CartModel::scopeTouchedBy()` and the `touched_by` filter are removed.
- `GET /polycart/carts/{cart}/activity` and the `list-cart-activity` tool are removed in favour of `GET /polycart/history?subject_type=...&subject_id=...` and `list-polycart-history-tool`.
- The JSON API's source is now `http` (was `api`), and work outside every surface is `cli` or `code` (was `polycart.default_source`, which is removed).
