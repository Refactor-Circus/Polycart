---
name: polycart-development
description: >
  Model shopping carts, saved carts, quotes, orders, and nested project carts with the
  refactor-circus/polycart package: define CartType classes, add lines, price them, move statuses,
  convert between types, and merge guest carts in Laravel applications.
license: MIT
metadata:
  author: Jay Fletcher
---

# Polycart

Use this skill when a Laravel application needs carts, or anything shaped like a cart (a quote, an order, a wishlist, a project with sets and openings), through `refactor-circus/polycart`.

## Primary Goal

- give every kind of cart a `CartType` class that holds its rules, so application code never branches on the type key

## Workflow

### 1. Install

```bash
composer require refactor-circus/polycart
php artisan vendor:publish --tag="polycart-migrations"
php artisan vendor:publish --tag="polycart-config"
php artisan migrate
```

To rename tables, set `polycart.tables` (`carts`, `cart_paths`, `cart_members`, `cart_lines`) in the published config before migrating; keys left out keep their default.

### 2. Define one type per kind of cart

- Extend `RefactorCircus\Polycart\Domains\CartType\Support\CartType` and override only what differs. The overridable methods are:
  - `statuses()` and `transitions()`
  - `requiresPrice()`, `accepts()` and `price()`
  - `convertsTo()` and `convertedFrom()`
  - `parents()` and `requiresParent()`
  - `lifetime()`
  - `mergesLines()` and `fingerprint()`
  - `prepareOptions()` and `prepareMeta()`
  - `validate()`
- Statuses are a string-backed enum. The first case is the initial status.
- Register the type in `config/polycart.php` under `types` as `'key' => Class::class`. From a package, use `Polycart::types()->register()`.
- Optionally return a `CartModel` subclass from `model()`, and set `protected static ?string $cartType = 'key';` on that subclass.

### 3. Give owners carts

- Add `RefactorCircus\Polycart\Domains\Cart\Concerns\HasCarts` to the owner model. `$user->cart('quote')` returns the active cart and creates one when there is none.
- Guests: call `Polycart::active('cart', session()->getId())`. At login, call `Polycart::merge($guestCart, $user->cart())`.

### 4. Teams, organizations and sharing (optional)

- Implement `RefactorCircus\Polycart\Domains\Scope\Contracts\CartScope::parentCartScope()` on each tree level (team → organization → null).
- Implement `RefactorCircus\Polycart\Domains\Scope\Contracts\CartParticipant::cartScopes()` on users, returning the teams they are on directly.
- Start carts in a scope: `$user->cart(scope: $team)` or `Polycart::create('quote', $user, scope: $team)`. The cart is locked to that tree.
- Share: `Polycart::share($cart, $userOrTeam, 'editor')`. Only members inside the cart's top-level scope can be added. Remove with `Polycart::unshare($cart, $member)`.
- Visibility: `$cart->setVisibility(Visibility::Scope|Boundary)`.
- Check access with `$user->can('update', $cart)`, `$cart->allows($user, 'checkout')`, and `$user->accessibleCarts()`.
- Each type owns its roles in `roles()`. Guard a single status move with `transitionAbility()`.

### 5. Work with lines

- Add: `$cart->add($model, $qty, options: [...], meta: [...], unitPrice: null)`. Pass `null` as the model for a custom line.
- Add several lines, all or nothing: `$cart->addLines([['purchasable' => $model, 'quantity' => 2], ...])`. The API (`POST .../lines` with `lines: [...]`) and MCP (`add-lines`) only add in batches.
- Custom add rules (stock, customer checks, discounts) are pipeline stages. Override `CartType::addLineStages()` and use `self::insertStagesAfter(parent::addLineStages(), BuildLine::class, [CheckStock::class])`.
- A stage takes `handle(PendingLine $line, Closure $next)` and refuses with `$line->reject($message, $reason)`. Catch `LineRejectedException`, which has `reason` and `index`.
- Remove: `$cart->remove($line)` or `$cart->clear()`. Change a quantity with `$cart->updateLine($line, $qty)`. Change or remove several lines at once, all or nothing, with `$cart->updateLines([...])` and `$cart->removeLines([...])`. The API and MCP only change lines in batches (`PATCH`/`DELETE .../lines`, `update-lines`, `remove-lines`).
- Totals: `$cart->subtotal()` returns integer minor units.
- Calling an Action directly, pass its Data object, never an array: `app(AddLinesAction::class)->execute($cart, new AddLinesData([new AddLineData(purchasable: $lock, quantity: 12)]))`, `app(UpdateCartAction::class)->execute($cart, new UpdateCartData(label: 'Lobby'))`. Each Data (`RefactorCircus\Polycart\Domains\{Domain}\Data\*`) holds its validation `rules()`; build one from untrusted input with `XData::from($input)`, which validates. The facade and `CartModel` helpers build the Data for you.
- Pricing: implement `RefactorCircus\Polycart\Domains\CartLine\Contracts\Purchasable::unitPriceFor()` on the model, or bind `RefactorCircus\Polycart\Domains\CartLine\Contracts\PriceResolver`.

### 6. Move and convert

- Change status with `$cart->transitionTo(Enum::Case)`. Read it with `$cart->currentStatus()`.
- Convert with `$cart->convertTo('quote')`, which copies the cart, or `Polycart::convert($cart, 'order', copy: false)`, which retypes it in place.

### 7. Expose carts over the JSON API and MCP (optional)

Polycart is API plus MCP, with no UI of its own. Atrium v2 is the optional UI: a separate package that calls the JSON API, so it needs the API on.

- JSON API: it is off by default. Set `polycart.routes.enabled` to `true` and put your auth middleware in `polycart.routes.middleware` (e.g. `['api', 'auth:sanctum']`). With it off, no route is registered.
- List the models callers may name in `polycart.owners`, `polycart.scopes` and `polycart.purchasables`, keyed by alias.
- With `polycart.authorization` on (the default), every call acts as the signed-in user and is checked against the policies in `polycart.policies`. By default the cart's owner may do anything and everyone else gets what their role grants. Swap a policy by pointing its model at your own class there; every model (`CartModel`, `CartLineModel`, `CartMemberModel`, `CartPathModel`) has an entry.
- Stats: `GET {prefix}/stats` (`polycart.stats.show`) answers `{"data": {"by_type": [{"type": "cart", "count": 3}, ...], "recent": [...]}}`: a count of the caller's accessible, unexpired carts for every registered type (registry order, zeros included) and the 5 most recently updated accessible carts with `lines_count`. In code: `app(ShowCartStatsAction::class)->execute($user)`.
- Search: `GET {prefix}/search?q=lobby` (`polycart.search.index`) finds accessible carts whose label contains `q` or whose id starts with it, answered like `GET {prefix}/carts` (newest first, cursor paged with `per_page` up to 200 and `cursor`). `q` is required, at most 191 characters.
- MCP: enable `polycart.mcp.web` or `polycart.mcp.local`. The tools match the API one-to-one: `list-cart-types`, `cart-stats`, `list-carts` (its `search` argument is the search), `show-cart`, `create-cart`, `active-cart`, `update-cart`, `delete-cart`, `clear-cart`, `transition-cart`, `convert-cart`, `merge-carts`, `add-lines`, `update-lines`, `remove-lines`, `list-members`, `share-cart`, `unshare-cart`, `set-visibility`, plus `list-polycart-history-tool` for the audit history, Polycart's or one cart's (`GET {prefix}/history?subject_type=...&subject_id=...` over HTTP; the tool is offered, and the route answers, only while Polycart audits: refactor-circus/keen installed and `polycart.audit.enabled` on). Each tool answers structured content shaped like the JSON API: a cart, line or member under `data`, a list under `data` with its page under `meta` (`list-carts` pages by cursor), and declares an output schema for it.
- OpenAPI and the client: the package root's `openapi.json` describes every `polycart.*` route. `client/` is the TypeScript client `@refactor-circus/polycart-client`, installed as a git dependency on the release tag (`github:refactor-circus/polycart#v2.0.0&path:/client` with pnpm): `createPolycartClient({ baseUrl: 'https://app.test/polycart', credentials: 'include' })`, then `client.GET('/stats')` or `client.GET('/search', { params: { query: { q: 'lobby' } } })`.
- Changing a route, request or resource in the package: run `composer client:generate` (needs Node), which regenerates `openapi.json`, `client/src/schema.d.ts` and `client/openapi.sha256`, and commit them. `tests/Feature/OpenApiTest.php` and the CI `client` job fail while they are stale. Never edit them by hand.

### 8. Record where carts come from and what happened to them

- Every cart records a `source`: the refactor-circus/keystone `Surface` it was created (or last converted) through: `http` (the JSON API, and any front end on it such as Atrium v2), `mcp`, `cortex`, `cli` or `code`.
- Name your own surface with `Polycart::usingSource('import', fn () => ...)` (Keystone's `Surface::using()`), or for your routes with `app(Surface::class)->route('checkout.', 'web')`.
- Polycart keeps no history of its own. Install `refactor-circus/keen` and every change made through the package is recorded in the suite-wide audit log with source `polycart`; line, sharing, merge and conversion entries are about the cart, a merge names `merged_from` and a conversion copy `converted_from`. Without Keen, carts keep no history.
- Record your own events with `Keen::record('cart.exported')->source('polycart')->on($cart)->with([...])->save()`.
- Query carts with `CartModel::query()->fromSource('mcp')`; read history from `GET {prefix}/history?subject_type=...&subject_id=...` or `list-polycart-history-tool`.
- Write changes through the package's methods, not raw Eloquent updates, or they are not recorded.
- Set `polycart.audit.enabled` to `false` to keep Polycart out of the audit log while Keen is installed; Polycart then offers no history.

### 9. Let Cortex agents manage carts (optional)

- Install `refactor-circus/cortex`. Polycart registers its MCP server as `polycart` and every MCP tool in Cortex's tool registry automatically.
- Limit the tools with `polycart.cortex.tools` (a list of names), or turn it off with `polycart.cortex.enabled`.
- The tools are tagged `polycart` in Cortex's tool pickers; change the tags with `polycart.cortex.tags`.
- Override the server instructions and tool descriptions in Cortex; the published versions are served to MCP clients and agents.
- Agent changes record `cortex` as their source and audit surface.

## Rules, References, and Templates

- no additional resource files for this skill

## Examples

- A B2B shop has four types: `cart` (45-day lifetime, converts to `saved`, `quote` and `order`), `saved`, `quote` (requires prices, Draft → Submitted → Accepted, converts to `order`), and `order`. Checkout calls `$cart->convertTo('order', copy: false)`, then `->transitionTo(OrderStatus::Processing)`.
- A project carts tree has three types. `project` returns `[]` from `parents()`. `set` returns `['project']` from `parents()` and requires a parent. `opening` returns `['set']` from `parents()`, requires a parent, and returns false from `mergesLines()`.
- Events: every model fires one event class per Eloquent hook (`CartCreatingEvent`, `CartLineSavedEvent`, ...), and every action fires a start and a finish event (`LineAddingActionEvent` → `LineAddedActionEvent`). Each action's writes run in one transaction, and finish events fire after commit and only on success. Listen to `ActionFinishedEvent` for all of them. In tests, fake only the events you assert on, e.g. `Event::fake([LineAddedActionEvent::class])`. Assert refusals with `LineRejectedException`, `InvalidTransitionException`, `InvalidConversionException` and `InvalidParentException`.

## Anti-patterns

- Do not branch on `$cart->type === 'quote'` in application code. Put that behaviour in the type class.
- Do not store prices as floats. Pass and read integer minor units.
- Do not share one status enum across types. Each type owns its lifecycle.
- Do not enable `polycart.routes` or `polycart.mcp.web` without auth middleware. They can read and change every cart.
- Do not write `type` or `status` columns directly. Use `convertTo()` and `transitionTo()`, which enforce the type's rules and fire events.
