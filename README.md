<div align="center">
    <img src="art/icon.png" width="160" alt="Polycart icon">
    <h1>Polycart</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/refactor-circus/polycart"><img src="https://img.shields.io/packagist/v/refactor-circus/polycart.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/refactor-circus/polycart"><img src="https://img.shields.io/packagist/php-v/refactor-circus/polycart.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/refactor-circus/polycart"><img src="https://badge.laravel.cloud/badge/refactor-circus/polycart?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/refactor-circus/polycart/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/refactor-circus/polycart/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/refactor-circus/polycart"><img src="https://img.shields.io/packagist/dt/refactor-circus/polycart.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Typed carts for Laravel. A shopping cart, a saved cart, a quote, an order, a project with its sets and openings: every one is a row in the same table, told apart by a string **type key**. Each key points at a `CartType` class, and that class holds all of that kind's rules:

- its statuses and the moves allowed between them
- what it may hold, and whether every line needs a price
- which types it can be converted into
- where it may sit in a tree of carts
- how long it lives
- whether a repeat add merges into the existing line

Your code never branches on the key.

## API + MCP

Polycart is a Laravel package with two surfaces on top of its PHP API: a **JSON API** and an **MCP server**. Both call the same action classes, so a front end and an agent see exactly the same carts and rules. Polycart ships no PHP UI; its only front end is its [Atrium module](#the-atrium-module), plain JavaScript.

- The [JSON API](#json-api) is described by a committed OpenAPI document, [`openapi.json`](openapi.json), and called from TypeScript through a generated client, `@refactor-circus/polycart-client` (see [OpenAPI and the TypeScript client](#openapi-and-the-typescript-client)).
- The [MCP server](#mcp-server) offers the same operations as tools, and [Cortex](#cortex) agents can use them.
- [Atrium v2](https://github.com/Refactor-Circus/Atrium), the suite's dashboard, is the optional UI. It is a separate package that loads Polycart's [Atrium module](#the-atrium-module), whose pages read and change carts through this JSON API and the client, so it needs `polycart.routes.enabled` on, behind your auth middleware (see [Enabling the API](#enabling-the-api)).

## Installation

```bash
composer require refactor-circus/polycart

php artisan vendor:publish --tag="polycart-migrations"
php artisan vendor:publish --tag="polycart-config"
php artisan migrate
```

The migrations create `polycart_carts`, `polycart_cart_lines`, `polycart_cart_members` and `polycart_cart_paths`. Prices are stored as integers in minor units (cents).

To use other table names, set `polycart.tables` in the published config before migrating. The migrations, models and queries all read it, and keys left out keep their default:

```php
'tables' => [
    'carts' => 'shop_carts',
    'cart_lines' => 'shop_cart_lines',
],
```

The `polycart` tag publishes the config and the migrations at once.

## Defining types

A type extends `RefactorCircus\Polycart\Domains\CartType\Support\CartType` and overrides only what makes it different. Every method has a permissive default.

```php
use RefactorCircus\Polycart\Domains\Cart\Models\CartModel;
use RefactorCircus\Polycart\Domains\CartType\Support\CartType;

enum QuoteStatus: string
{
    case Draft = 'draft';          // the first case is where a new quote starts
    case Submitted = 'submitted';
    case Accepted = 'accepted';
}

class QuoteCart extends CartType
{
    public function model(): string            { return Quote::class; }   // optional Cart subclass
    public function statuses(): string         { return QuoteStatus::class; }
    public function requiresPrice(): bool      { return true; }
    public function convertsTo(): array        { return ['order']; }

    public function transitions(): array
    {
        return [
            QuoteStatus::Draft->value => [QuoteStatus::Submitted],
            QuoteStatus::Submitted->value => [QuoteStatus::Accepted],
        ];
    }

    // Keep only the header fields a quote cares about.
    public function convertedFrom(CartModel $cart, CartModel $source): void
    {
        $cart->meta = array_intersect_key($source->meta ?? [], array_flip(['po_number']));
    }
}
```

Register types in `config/polycart.php`:

```php
'types' => [
    'cart'  => App\Carts\ShoppingCart::class,
    'saved' => App\Carts\SavedCart::class,
    'quote' => App\Carts\QuoteCart::class,
    'order' => App\Carts\OrderCart::class,
],
```

A package can register its own types from a service provider with `Polycart::types()->register('wishlist', WishlistCart::class)`. The application's config always wins over a package registration. Two packages claiming the same key throw a `CartTypeCollisionException`.

| Method | Default | Purpose |
| --- | --- | --- |
| `model()` | `CartModel::class` | Model class that rows of this type hydrate as |
| `statuses()` / `transitions()` | none / any move | This type's own lifecycle, as a string-backed enum |
| `lifetime()` | never expires | A `CarbonInterval`, pushed back on every change |
| `parents()` / `requiresParent()` | any / false | Where the type may sit in a tree |
| `convertsTo()` / `convertedFrom()` | none / no-op | Allowed conversions, and a hook on the target type |
| `accepts()` | anything | Which purchasables may be added (`null` means a custom line) |
| `requiresPrice()` / `price()` | false / `PriceResolver` | Pricing rules |
| `mergesLines()` / `fingerprint()` | true / model + options | What counts as "the same line" |
| `prepareOptions()` / `prepareMeta()` | unchanged | Filter or normalise line data before it is stored |
| `validate()` | price check | Refuse a line by throwing `LineRejectedException` |

## Typed models

Point a type at a subclass of `CartModel` to give it its own relations and methods. A subclass pins itself to its key with `$cartType`:

```php
class Quote extends CartModel
{
    protected static ?string $cartType = 'quote';
}

Quote::query()->get();     // only quotes
Quote::create();           // type is 'quote'
CartModel::find($id);      // a Quote instance when the row is a quote
```

## Using carts

Add `HasCarts` to anything that owns carts, such as a user, a team or a customer:

```php
use RefactorCircus\Polycart\Domains\Cart\Concerns\HasCarts;

class User extends Authenticatable
{
    use HasCarts;
}

$cart = $user->cart();              // active cart of the default type, created if missing
$quote = $user->cart('quote');
$user->carts;                       // every cart of every type
```

Guests use their session key as the owner:

```php
use RefactorCircus\Polycart\Facades\Polycart;

$cart = Polycart::active('cart', session()->getId());

// At login, move the guest's lines into the user's cart.
Polycart::merge($cart, $user->cart());
```

A cart stays *active* while it has not expired and is still in its type's first status. Once it is submitted or checked out, the next `active()` call starts a new one.

### Lines

```php
$line = $cart->add($product, 2, options: ['finish' => '626', 'keying' => ['system' => 'A1']]);
$cart->add($product, 1, options: ['keying' => ['system' => 'A1'], 'finish' => '626']);  // adds to the same line: quantity 3

$cart->add(null, 1, meta: ['description' => 'Non-catalog hinge'], unitPrice: 900);   // custom line

$cart->remove($line);
$cart->clear();

$cart->subtotal();        // int, in minor units
$cart->quantity();
$cart->isFullyPriced();
```

**Options** say *what* is being bought and are part of the line's identity. **Meta** is anything else the line carries, such as a note or a reference. A custom line has no model, so its meta is part of its identity as well. To change a quantity, use `$cart->updateLine($line, 5)`, or several at once with `$cart->updateLines([['id' => $line, 'quantity' => 5], ...])`. A quantity of `0` removes the line. `$cart->removeLines([$a, $b])` removes several lines at once. Both are all or nothing.

To add several lines at once, all or nothing:

```php
$cart->addLines([
    ['purchasable' => $lock, 'quantity' => 12],
    ['purchasable' => null, 'meta' => ['description' => 'Delivery'], 'unit_price' => 2500],
]);
```

### The add pipeline

Every add goes through a pipeline of stages that the cart type controls:

1. prepare
2. check accepted
3. find matching line
4. resolve price
5. build
6. validate
7. write

Slot in your own stages for stock, customer rules or pricing, and refuse a line with a reason code:

```php
class WholesaleCart extends CartType
{
    public function addLineStages(): array
    {
        return self::insertStagesAfter(parent::addLineStages(), BuildLine::class, [CheckStock::class]);
    }
}

final class CheckStock
{
    public function handle(PendingLine $line, Closure $next): PendingLine
    {
        if ($line->resultingQuantity() > $line->purchasable->stock) {
            $line->reject("Only {$line->purchasable->stock} left.", 'out_of_stock');
        }

        return $next($line);
    }
}
```

A refused line writes nothing. The API answers `422` with `{message, reason, line}`.

**Full guide:** [Adding lines](docs/adding-lines.md). It covers every default stage, the pending line, writing and configuring stages, batches, the API and MCP, and transactions and events.

### Pricing

A line added without `unitPrice` is priced by the type's `price()`, which defers to the bound `RefactorCircus\Polycart\Domains\CartLine\Contracts\PriceResolver`. By default the resolver asks models that implement `Purchasable`:

```php
class Product extends Model implements Purchasable
{
    public function unitPriceFor(CartModel $cart, array $options): ?int
    {
        return $this->price_cents;
    }
}
```

To price from an ERP or a customer price book, bind your own resolver:

```php
$this->app->singleton(PriceResolver::class, ErpPriceResolver::class);
```

### Statuses

```php
$quote->transitionTo(QuoteStatus::Submitted);
$quote->currentStatus();                         // QuoteStatus::Submitted
$quote->hasStatus('submitted');                  // true

CartModel::ofType('quote')->whereStatus(QuoteStatus::Submitted)->get();
```

A status that belongs to another type, or a move that is not in `transitions()`, throws `InvalidTransitionException`.

### Conversions

```php
$quote = $cart->convertTo('quote');                     // copy: the cart is left untouched
$order = Polycart::convert($quote, 'order', copy: false); // retype in place
```

The source type must list the target in `convertsTo()`. Every line is checked against the target's rules inside a transaction. For example, a quote that requires prices refuses a cart with unpriced lines, and nothing is written.

### Trees

```php
$project = Polycart::create('project', $team, ['label' => 'Tower A']);
$set = Polycart::create('set', $team, ['parent_id' => $project->id]);
$opening = Polycart::create('opening', $team, ['parent_id' => $set->id]);

$opening->root;              // $project
$project->children;          // [$set]
$project->descendants;       // [$set, $opening]
```

`parents()` and `requiresParent()` on each type decide which nestings are allowed.

## Teams, organizations and sharing

A cart can live in your application's tree, such as a team inside an organization. It stays there for good, and can be shared with people or whole teams inside the same organization.

```php
class Team extends Model implements CartScope
{
    public function parentCartScope(): ?CartScope { return $this->organization; }
}

class User extends Authenticatable implements CartParticipant
{
    use HasCarts;

    public function cartScopes(): iterable { return $this->teams; }
}

$cart = $user->cart(scope: $salesTeam);          // one active cart per team
Polycart::share($cart, $colleague, 'editor');    // anyone inside the organization
Polycart::share($cart, $opsTeam, 'viewer');      // whoever is on Ops, now and later
$cart->setVisibility(Visibility::Scope);         // the whole team can see it

$user->can('update', $cart);
$user->accessibleCarts()->get();
CartModel::query()->inScope($organization)->get();
```

- New carts are private to their creator.
- Access is worked out each time it is checked.
- Unscoped carts keep working.

**Full guide:** [Scoping and sharing](docs/scoping-and-sharing.md). It covers the tree contracts, the lock, members, visibility, access rules, querying, the API and MCP, events, exceptions and storage.

### Roles belong to the cart type

Each type lists its roles, strongest first, and the abilities each grants. Types with a checkout can have a role that may check out, and types without one simply don't:

```php
public function roles(): array
{
    return [
        'owner' => ['*'],
        'buyer' => ['view', 'update', 'checkout'],
        'editor' => ['view', 'update'],
        'viewer' => ['view'],
    ];
}

public function transitionAbility(?BackedEnum $from, BackedEnum $to): string
{
    return $to === OrderStatus::Processing ? 'checkout' : 'transition';
}

$user->can('checkout', $order);
```

**Full guide:** [Roles and permissions](docs/roles-and-permissions.md). It covers the default roles, custom abilities, guarding status moves and conversions, visibility and creator roles, role mapping on conversion, what the API and MCP check, replacing the policy, and worked examples.

## JSON API

Every cart operation is available over HTTP.

### Enabling the API

The API is **off by default** (`polycart.routes.enabled` is `false`) because it can read and change every cart. While it is off, none of its routes is registered. Turn it on when your own front end, or Atrium v2, needs it, and always put it behind your auth middleware:

```php
// config/polycart.php
'routes' => [
    'enabled' => true,
    'prefix' => 'polycart',
    'middleware' => ['api', 'auth:sanctum', 'can:manage-carts'],
],

// The only models the API and MCP tools may name, by alias.
'owners' => ['user' => App\Models\User::class],
'scopes' => ['team' => App\Models\Team::class, 'organization' => App\Models\Organization::class],
'purchasables' => ['product' => App\Models\Product::class],
```

With `polycart.authorization` on (the default), every call acts as the authenticated user:

- Lists show only the carts that user can access.
- New carts are owned by that user.
- Every call goes through the Gate, using the policies in `polycart.policies`, which has an entry for every Polycart model. By default a cart's owner may do anything with it and everyone else gets what their role grants; lines and members follow their cart, and a cart's tree paths are visible to whoever sees the cart but never changed. Point a model at your own policy class to replace it.

Turn authorization off only for trusted server-to-server use.

| Method | Path | Does |
| --- | --- | --- |
| `GET` | `/types` | Every type and what it allows |
| `GET` | `/stats` | Counts by type and the most recently updated carts. Named `polycart.stats.show`; see [Stats](#stats). |
| `GET` | `/search` | Carts whose label contains `q`, or whose id starts with it. Named `polycart.search.index`; see [Search](#search). |
| `GET` | `/carts` | List, filtered by `type`, `status`, `source`, `owner_type` and `owner_id`, `scope_type` and `scope_id`, `session_key`, `parent`, `root`, `search`, `unexpired`; cursor paginated |
| `POST` | `/carts` | Create a cart (`type`, and optionally `scope_type` and `scope_id`, an owner or `session_key`, `label`, `meta`, `parent_id`) |
| `POST` | `/carts/active` | The owner's current cart of a type, created if missing |
| `GET` | `/carts/{cart}` | The cart with its lines, quantity and subtotal |
| `PATCH` | `/carts/{cart}` | Change `label` or `meta` |
| `DELETE` | `/carts/{cart}` | Soft-delete the cart |
| `POST` | `/carts/{cart}/clear` | Remove every line |
| `POST` | `/carts/{cart}/status` | Move to another `status` |
| `POST` | `/carts/{cart}/convert` | Convert `to` another type, with `copy` true or false |
| `POST` | `/carts/{cart}/merge` | Move every line `into` another cart |
| `POST` | `/carts/{cart}/lines` | Add one or more `lines`, all or nothing. Each has `purchasable_type` and `purchasable_id` (or neither, for a custom line), `quantity`, `options`, `meta`, `unit_price`. |
| `PATCH` | `/carts/{cart}/lines` | Change one or more `lines`, all or nothing. Each has `id`, `quantity` (`0` removes the line) and optionally `unit_price`. Returns the cart with its totals. |
| `DELETE` | `/carts/{cart}/lines` | Remove one or more `lines` by id, all or nothing, from the body or the query string. Returns the cart with its totals. |
| `GET` | `/carts/{cart}/members` | Who the cart is shared with |
| `POST` | `/carts/{cart}/members` | Share with a `member_type` and `member_id` (a person or a scope) as a `role` |
| `DELETE` | `/carts/{cart}/members/{member}` | Remove a member |
| `PUT` | `/carts/{cart}/visibility` | Set `visibility` to `private`, `scope` or `boundary` |
| `GET` | `/history` | Polycart's audit history from [refactor-circus/keen](https://github.com/Refactor-Circus/Keen), filterable by `subject_type` and `subject_id` (one cart's history: its morph class and id), `action`; cursor paginated. Answers `404` until an audit log is installed, or while `polycart.audit.enabled` is `false`. Named `polycart.history.index`. |

When a type refuses a request, the API returns `422` with a message that explains why, such as `A [quote] cart cannot move from [submitted] to [draft].`

### Stats

`GET /polycart/stats` (`polycart.stats.show`) answers what a dashboard's overview needs, in one call:

```json
{
    "data": {
        "by_type": [
            {"type": "cart", "count": 3},
            {"type": "quote", "count": 0}
        ],
        "recent": [
            {"id": "01JD7Q4K2M9V8T6X3N5B1C0A9Z", "type": "cart", "status": null, "source": "http", "label": "Lobby", "lines_count": 2, "meta": {}, "expires_at": "2026-12-08T09:30:00+00:00", "created_at": "2026-11-23T09:30:00+00:00", "updated_at": "2026-11-23T10:05:00+00:00", "...": "..."}
        ]
    }
}
```

- `by_type` has one entry for every registered type, in the order `Polycart::types()->all()` lists them, with `0` for a type that has no carts. It counts the carts the caller can access that are neither expired nor deleted. Carts of a type that is no longer registered are left out.
- `recent` holds up to 5 carts the caller can access, of any type and expired or not, most recently updated first. Each is the cart payload of `GET /carts/{cart}` with `lines_count` and without `lines`.

The same answer comes from `RefactorCircus\Polycart\Domains\Cart\Actions\ShowCartStatsAction::execute($viewer)` in code, and from the `cart-stats` MCP tool.

### Search

`GET /polycart/search?q=lobby` (`polycart.search.index`) finds the carts the caller can access whose label contains `q` or whose id starts with it. It answers exactly as `GET /carts?search=lobby` does: a page of carts with `lines_count`, newest first, paged by cursor with `per_page` (default 25, at most 200) and `cursor`:

```json
{
    "data": [
        {"id": "01JD7Q4K2M9V8T6X3N5B1C0A9Z", "type": "cart", "label": "Lobby", "lines_count": 2, "...": "..."}
    ],
    "links": {"first": null, "last": null, "prev": null, "next": "https://app.test/polycart/search?q=lobby&cursor=eyJpZCI6..."},
    "meta": {"path": "https://app.test/polycart/search", "per_page": 25, "next_cursor": "eyJpZCI6...", "prev_cursor": null}
}
```

A missing or empty `q`, a `q` longer than 191 characters, or a `per_page` out of range answers `422` with an error on that field. Agents search through `list-carts` with its `search` argument.

### Who sees what

With `polycart.authorization` on, both endpoints answer `401` to a guest and `403` to a user whom the cart policy refuses `viewAny`. A signed-in user's counts, recent carts and search results hold only the carts that user can access.

## OpenAPI and the TypeScript client

The JSON API is described by [`openapi.json`](openapi.json) at the package root, an OpenAPI document with one operation for every `polycart.*` route, `polycart.stats.show` and `polycart.search.index` included. Its paths are relative to the routes' prefix (`/polycart` by default).

The [`client/`](client) folder holds `@refactor-circus/polycart-client`, a typed fetch client generated from that document with [openapi-typescript](https://openapi-ts.dev/) and [openapi-fetch](https://openapi-ts.dev/openapi-fetch/). It ships as TypeScript source with no build step, so your bundler compiles it (Polycart's own Atrium module does). It is not published to npm: install it as a git dependency on the release tag, with a package manager that installs a subfolder of a repository, such as pnpm:

```json
{
    "dependencies": {
        "@refactor-circus/polycart-client": "github:refactor-circus/polycart#v2.0.0&path:/client"
    }
}
```

`createPolycartClient(options)` takes the API's base URL, prefix included, plus any openapi-fetch option (`headers`, `credentials`, `fetch`, ...), and returns a client whose paths, parameters and responses are typed:

```ts
import { createPolycartClient, type components } from '@refactor-circus/polycart-client';

const polycart = createPolycartClient({ baseUrl: 'https://app.test/polycart', credentials: 'include' });

const { data: stats } = await polycart.GET('/stats');
const { data: found } = await polycart.GET('/search', { params: { query: { q: 'lobby', per_page: 10 } } });

type Cart = components['schemas']['CartResource'];
```

The client only works while the host has `polycart.routes.enabled` on, and it is subject to the same auth middleware and policies as any other caller.

### Regenerating the client

Never edit `openapi.json`, `client/src/schema.d.ts` or `client/openapi.sha256` by hand. After changing a route, request or resource, run (with Node installed):

```bash
composer client:generate
```

It exports `openapi.json` with `php vendor/bin/testbench workbench:openapi openapi.json` (against an in-memory database, so the output is the same on every machine), runs `npm --prefix client ci`, then `npm --prefix client run generate`, which writes `client/src/schema.d.ts` and `client/openapi.sha256` (the SHA-256 of `openapi.json`). Commit all three. `tests/Feature/OpenApiTest.php` fails while `openapi.json` differs from a fresh export or the client's stamp does not match it, and the CI `client` job regenerates everything, fails on any diff and type-checks the client.

## The Atrium module

Polycart ships its front end for [Atrium v2](https://github.com/Refactor-Circus/Atrium) as an Atrium module: the carts, cart and cart types pages, the *Carts by type* and *Recent carts* dashboard widgets and the carts search source. It is JavaScript only, with no PHP: Polycart neither requires nor suggests Atrium, and nothing under `src/` names it.

- `composer.json` declares the module under `extra.atrium` (key `polycart`, integration manifest `dist/atrium/integration.json`, script `dist/atrium/atrium.js`, stylesheet `dist/atrium/atrium.css`). When Atrium is installed, it discovers the module and injects the script into its page while the Polycart integration is active; without Atrium nothing reads it.
- `atrium/src/integration.json` is the module's integration manifest: what Polycart adds to Atrium's shell, as static JSON with no PHP. It declares the Pennant feature the integration needs, the *Carts* and *Cart types* navigation items, the two dashboard widgets, the carts search source and the carts, cart and cart types screens. The build validates it against Atrium's schema and writes it to `dist/atrium/integration.json`, which `extra.atrium.integration` points at; Atrium reads it there when it registers the integration. To change what Polycart declares, edit `atrium/src/integration.json` and rebuild (below).
- `dist/atrium/` holds the built module and is committed, so hosts never run Node. Its sources, in `atrium/` (Vue, typed by `client/src/schema.d.ts`), are left out of the Composer dist.
- The module calls the JSON API, so it needs `polycart.routes.enabled` on, behind your auth middleware.
- The module's components compose Nuxt UI (Atrium's `@refactor-circus/atrium/ui`) and the SDK's `Atrium*` components, with no raw buttons, fields, tables or links; `atrium/test/nuxt-ui-guard.test.ts` enforces it.

To rebuild it after changing `atrium/` (the manifest included) or the client, with pnpm 10 and Node 20 or later:

```bash
cd atrium
pnpm install
pnpm typecheck && pnpm test && pnpm build
```

Commit `dist/atrium/`. `tests/Feature/AtriumModuleTest.php` fails when the committed build is older than its sources, and the CI `ui` job rebuilds the module and fails on any difference.

## MCP server

The same operations are available as MCP tools, so an agent can manage carts:

- `list-cart-types`
- `cart-stats`, the counts by type and recent carts of `GET /stats`, under `data`
- `list-carts` (pass `search` to search, as `GET /search` does), `show-cart`, `create-cart`, `active-cart`, `update-cart`, `delete-cart`, `clear-cart`
- `transition-cart`, `convert-cart`, `merge-carts`
- `add-lines`, `update-lines`, `remove-lines`
- `list-members`, `share-cart`, `unshare-cart`, `set-visibility`
- `list-polycart-history-tool`, the audit history, for Polycart or one cart (offered only while an audit log such as refactor-circus/keen is installed and `polycart.audit.enabled` is on)

```php
'mcp' => [
    'web' => ['enabled' => true, 'route' => 'mcp/polycart', 'middleware' => ['auth:sanctum']],
    'local' => ['enabled' => true, 'handle' => 'polycart'],
],
```

Each tool answers with structured content shaped like the JSON API's response: a cart, line or member under `data`, and a list under `data` with its page under `meta` (`list-carts` pages by cursor: pass `meta.next_cursor` back as `cursor`). Every tool declares an output schema for it.

The JSON API and the MCP tools call the same action classes, and each action takes its input as a Data object (`CreateCartData`, `AddLinesData`, ...) that holds the validation rules, so every surface checks a call exactly the same way. An architecture test fails the build if any action is missing from the JSON API or the MCP server.

Calling an action yourself, build its Data with `new` (or `XData::from($input)` to validate untrusted input first):

```php
use RefactorCircus\Polycart\Domains\Cart\Actions\ConvertCartAction;
use RefactorCircus\Polycart\Domains\Cart\Data\ConvertCartData;

app(ConvertCartAction::class)->execute($cart, new ConvertCartData(to: 'order', copy: false));
```

The facade and the cart's own methods (`Polycart::create()`, `$cart->add()`, `$cart->addLines()`, `$cart->convertTo()`, ...) build the Data for you.

## Cortex

When [`refactor-circus/cortex`](https://github.com/Refactor-Circus/cortex) is installed, Polycart connects to it:

- Every MCP tool joins Cortex's tool registry, tagged `polycart`, so **Cortex agents can manage carts**. They go through the same validation, authorization and add pipeline as MCP clients.
- The server is registered as `polycart`, so its **instructions and each tool's description can be overridden** with Cortex's versioned, publishable content.
- Changes an agent makes record **`cortex` as their surface**, on the cart's `source` and in the audit log.

```php
'cortex' => ['enabled' => true, 'server' => 'polycart', 'tools' => null, 'tags' => ['polycart']],   // tools: or a list of names
```

**Full guide:** [Cortex](docs/cortex.md).

## Sources and history

Every cart records the **`source`** it was created (or last converted) through, as refactor-circus/keystone's `Surface` names it: `http` (the JSON API), `mcp`, `cortex` (a Cortex agent), `cli` or `code`, or a name of your own. A front end on the JSON API, Atrium v2 among them, records `http`.

Polycart keeps no history of its own. Install [refactor-circus/keen](https://github.com/Refactor-Circus/Keen), the suite-wide audit log, and every change to a cart is recorded with who made it, through which surface and what changed. Line, sharing, merge and conversion entries are about the cart; a merge names the merged cart in `merged_from`, and a copy made by a conversion names the original in `converted_from`. Without Keen, carts keep no history.

```php
Polycart::usingSource('import', fn () => $importer->run());      // name your own surface

CartModel::query()->fromSource('mcp')->get();

Keen::record('cart.exported')->on($cart)->with(['reference' => 'SO-1042'])->save();   // your own events
```

Read a cart's history from `GET /polycart/history?subject_type=...&subject_id=...` or the `list-polycart-history-tool` MCP tool.

**Full guide:** [Sources and history](docs/sources-and-history.md). It covers sources, every recorded action, where a cart came from, reading history, and upgrading from the old activity log.

## Events

Every event carries the models involved. Events for a removed line or member carry ids, because the row is gone:

- **Model events:** every Eloquent hook of every model, one class per hook, such as `CartCreatingEvent`, `CartLineDeletedEvent` or `CartMemberSavedEvent`. Cart subclasses fire the `Cart*` events.
- **Action events:** a start and a finish event for every action, such as `LineAddingActionEvent` and `LineAddedActionEvent`, or `CartConvertingActionEvent` and `CartConvertedActionEvent`. Start fires before the work. Finish fires after the transaction commits, and only on success.
- **Listening to a whole family:** listen to `ModelLifecycleEvent`, `ActionStartingEvent` or `ActionFinishedEvent` to receive every event of that family.

**Full guide:** [Events](docs/events.md). It lists every action with its two events and what they carry.

## Pruning

A cart whose type has a `lifetime()` gets an `expires_at`. It is pruned `polycart.prune_after_days` days after it expires. A deleted cart, of any type and whether it expires or not, is pruned for good `polycart.prune_deleted_after_days` days (default 30) after it was deleted:

```php
Schedule::command('model:prune', ['--model' => [\RefactorCircus\Polycart\Domains\Cart\Models\CartModel::class]])->daily();
```

## Package layout

The code is organised into domain modules under `src/Domains/{Domain}` (`RefactorCircus\Polycart\Domains\{Domain}`), each with its own service provider, routes and only the folders it uses (`Models/`, `Policies/`, `Resources/`, `Actions/`, `Events/`, `Http/`, `Mcp/`, `Contracts/`, `Services/`, `Support/`, ...):

| Domain | Holds |
| --- | --- |
| `Cart` | `CartModel`, its lifecycle actions (create, update, transition, convert, merge, clear, delete), `HasCarts` |
| `CartLine` | `CartLineModel`, the add pipeline and its stages, pricing (`PriceResolver`, `Purchasable`) |
| `CartType` | `CartType`, `ShoppingCart` and the `CartTypeRegistry` |
| `Sharing` | `CartMemberModel`, sharing and visibility, `CartAccess` |
| `Scope` | `CartPathModel`, the `ScopeTree`, `CartScope` and `CartParticipant` |

Polycart stands on [refactor-circus/keystone](https://github.com/Refactor-Circus/Keystone), the runtime the Refactor Circus packages share: its HTTP and MCP request bases, MCP tool and server bases, authorizer, Cortex integration, event contracts and model-event trait are used as they are. Cross-domain code stays outside the domains: the `Polycart` entry point and facade, `Mcp\Request` (Keystone's MCP request plus the `cortex` surface for agent calls and line reason codes), the MCP server and its history tool, and `Support/` (morph lookups, table names and the base policy). Polycart ships no views, translations or PHP UI; the OpenAPI document, the TypeScript client (`client/`) and the Atrium module (`atrium/` sources, `dist/atrium/` build) sit beside the PHP code. Domain providers extend Keystone's `ServiceProvider` directly. Each change's history is refactor-circus/keen's, read through Keystone's `AuditTrail`.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Polycart! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Refactor Circus](https://github.com/Refactor-Circus)
- [All Contributors](../../contributors)

## License

Polycart is open-sourced software licensed under the [MIT license](LICENSE.md).
