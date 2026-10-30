# Release Notes

## [Unreleased](https://github.com/Refactor-Circus/Polycart/compare/v0.1.0...main)

## [v0.1.0](https://github.com/Refactor-Circus/Polycart/commits/v0.1.0) - 2026-10-30

### Breaking

- **Polycart's Atrium pages are screens on Atrium's screen extensions registry, and their history comes from Keen.** The cart page and the carts page draw no history of their own, and Polycart's sidebar group has no History (Audit log) item; Keen's History section and navigation attach to the screens instead. **Upgrading:** the cart history UI needs an Atrium with the screen extensions registry and a refactor-circus/keen with its History section; with an older Keen, the dashboard shows no cart history (the `GET /polycart/history` route and `list-polycart-history-tool` are unchanged). `composer.json` now conflicts with `refactor-circus/atrium` versions older than the registry; Atrium stays optional.
- **Actions take Data objects, not arrays or loose arguments.** Each Action's input is a `final readonly` Data class (on refactor-circus/keystone's `Data`) in its domain's `Data/` namespace, which also holds its validation `rules()` (the Actions' static `rules()` are removed): `CreateCartAction::execute(CreateCartData $data, $owner = null, ?Model $scope = null)`, `ActiveCartAction::execute(ActiveCartData $data, $owner, ?Model $scope = null)`, `ListCartsAction::execute(ListCartsData $filters = new ListCartsData, ?Model $viewer = null)`, `UpdateCartAction::execute($cart, UpdateCartData $data)` (fields left out are kept), `ConvertCartAction::execute($cart, ConvertCartData $data)`, `TransitionCartAction::execute($cart, TransitionCartData $data)`, `AddLineAction::execute($cart, AddLineData $data)`, `AddLinesAction::execute($cart, AddLinesData $data)` (a list of `AddLineData`), `UpdateLineAction::execute($line, UpdateLineData $data)`, `UpdateLinesAction::execute($cart, UpdateLinesData $data)` (a list of `LineChangeData`), `RemoveLinesAction::execute($cart, RemoveLinesData $data)`, `ShareCartAction::execute($cart, Model $member, ShareCartData $data)` and `SetVisibilityAction::execute($cart, SetVisibilityData $data)`. `MergeCartsAction` and the Actions without input are unchanged. The facade and `CartModel`'s helpers (`add()`, `addLines()`, `updateLines()`, `convertTo()`, ...) keep their signatures and build the Data.
- Starting action events carry the Data in place of the input they carried: `CartCreatingActionEvent` (`data`, `owner`, `scope`), `ActiveCartResolvingActionEvent` (`data`, `owner`, `scope`), `CartsListingActionEvent` (`filters` is a `ListCartsData`), `CartUpdatingActionEvent`, `CartConvertingActionEvent`, `CartTransitioningActionEvent`, `LineAddingActionEvent`, `LinesAddingActionEvent`, `LineUpdatingActionEvent`, `LinesUpdatingActionEvent`, `LinesRemovingActionEvent`, `CartSharingActionEvent` and `VisibilityChangingActionEvent` (each `data`). Finished events are unchanged.
- `RefactorCircus\Polycart\Domains\CartLine\Support\LineInput` is removed: `AddLineData::fromValidated()` finds a line's purchasable through `polycart.purchasables`.
- MCP tools answer structured content shaped like the JSON API: a single cart, line or member under `data` (it was the resource's fields at the top level), and lists under `data` with their page under `meta`; `list-carts` gives `meta.next_cursor` (was a top-level `next_cursor`) plus `meta.per_page` and `meta.prev_cursor`.
- A guest calling the JSON API now gets `401` (was `403`); a signed-in user without the ability still gets `403`.
- A missing cart, line or member on the JSON API answers `{"message": "Not found."}` instead of Laravel's message naming the model class.

### Fixed

- Deleted carts whose type never expires (quotes, orders) are now pruned: `model:prune` removes every deleted cart `polycart.prune_deleted_after_days` (new, default `30`) days after it was deleted, as `DeleteCartAction` and the `delete-cart` tool promise. Carts past their expiry are still pruned after `polycart.prune_after_days`.
- After `$cart->updateLines()`, `removeLines()` or `clear()`, the `$cart` you hold now has the extended `expires_at` (each line extended its own copy of the cart before).

### Changed

- Every Action's writes now run in one database transaction, the starting event dispatched before it and the finished event after: creating, updating, transitioning and deleting a cart, setting its visibility, sharing and unsharing (the last-owner check and the write together), removing or updating a line (with the cart's lifetime extension), and adding a line (its lifetime extension joins the pipeline's transaction). `ClearCartAction` removes every line in one transaction, so a failed removal puts every line back and no `CartClearedActionEvent` fires. Actions that write to the database take `Illuminate\Database\ConnectionInterface` in their constructor; resolve them from the container. `tests/ArchTest.php` now checks that Actions write inside a transaction and that every model fires an event for every Eloquent hook.
- `Polycart::create()` builds its `CreateCartData` with `new` from the `label`, `meta` and `parent_id` attributes (typed as an array shape), like `CartModel`'s helpers.
- Every MCP tool declares an output schema, built from its resource's new `schema()` (`CartResource`, `CartLineResource`, `CartMemberResource` and `CartTypeResource` now extend Keystone's `JsonResource`), and `list-carts` states its paging input from `ListCartsData::pagination()`.
- New `polycart.audit.enabled` (default `true`): with refactor-circus/keen installed, set it to `false` to keep Polycart out of the audit log. Polycart's audit labels are registered, its history route answers and its history tool is offered only while Polycart audits; without Keen the history tool is no longer listed at all.
- `active-cart` and `POST /carts/active` now honour `scope_type`/`scope_id`, and resolve the signed-in user's cart when authorization is on (they failed instead).
- The dashboard's convert form retypes the cart in place when "keep the original" is unticked (it always copied).

- Moved to the Refactor Circus organisation: the package is now `refactor-circus/polycart` with the PHP namespace `RefactorCircus\Polycart` (it was `jayi/polycart` and `JayI\Polycart`). Update `composer.json` requirements and `use` statements. Old class names are not kept as aliases, so stored values written under them - polymorphic `*_type` columns, audit subjects, Pennant feature names - need updating to the new names.

- Faster access checks, line batches and listings. What an actor reaches (`cartScopes()` and the levels above) is read once per request or job: `ScopeTree` is now a scoped binding, and code that changes someone's teams and checks their access again in the same request calls `app(ScopeTree::class)->forget($user)` (or `flush()`); team changes otherwise apply from the next request (they applied at the next check). `CartModel::lines()` and `members()` are chaperoned, so each line and member holds its cart without a query. Batch line changes (`addLines()`, `updateLines()`, `removeLines()`, `clear()`, merges) read their lines in one locking query, number new lines in memory and extend the cart's lifetime once per batch; a batch's purchasables are found with one query per kind. `accessibleBy()`, `inScope()` and the `scope_type`/`scope_id` list filter use `whereIn` subqueries on the member and path indexes, the dashboard's carts-by-type widget counts in one grouped query, a copy conversion reads the new cart's members once, and `CartTypeRegistry` keeps its merged catalogue until a type is registered or `polycart.types` changes.

### Added

- A migration, `2026_10_12_000001_add_polycart_performance_indexes`, indexing carts by `deleted_at`, `(created_at, id)`, `updated_at` and `parent_id` (not on MySQL/MariaDB, which index the foreign key already), and widening the tree paths' `(scope_type, scope_id)` index to `(scope_type, scope_id, cart_id)`. Run `php artisan migrate` after updating.
- `polycart.tables`: every table's name is configurable (`carts`, `cart_paths`, `cart_members`, `cart_lines`). Set it before migrating; the migration, models and the paths and memberships a cart's hooks write all read it through `RefactorCircus\Polycart\Support\Tables`, and a published config that lists only some keeps the defaults for the rest.
- `CartPathPolicy` and a `polycart.policies` entry for `CartPathModel`, so every model has a policy an application can swap: a cart's tree paths can be read by whoever may view the cart and are never created, changed or removed through the Gate. The child policies (`CartLinePolicy`, `CartMemberPolicy`, `CartPathPolicy`) now type their user `Model&Authenticatable`, as Keystone's `allowsOn()` expects; subclasses typed `Model` keep working.
- The package's section in Atrium's sidebar rail has its own icon (`shopping-cart`) and a fixed place in the rail.
- The three Atrium pages declare screens on Atrium's screen extensions registry, which other packages extend with tabs and sections: `polycart.carts` (list; rows are `CartModel`s), `polycart.cart` (detail; the `CartModel`) and `polycart.types` (list; rows are `RefactorCircus\Polycart\Atrium\Data\CartTypeRow`, an unsaved stand-in model for a cart type), each with Atrium's standard slots (lists: `header.actions`, `before`, `after`, `row.actions`; detail: `header.actions`, `before`, `aside`, `after`). A `permission()` on an extension is decided by Polycart's `ScreenAccess`, like the page's own controls, so it is shown to dashboard operators and to everyone while `polycart.authorization` is off; use `authorize()` for a check of your own. The cart history UI needs an Atrium with the registry and Keen's History section.
- A `WithoutAtrium` test suite (`composer test:without-atrium`, run by `composer test`) and CI job prove Polycart boots, routes and serves its JSON API and MCP server with Atrium uninstalled.

### Breaking

- **The cart activity log is replaced by refactor-circus/keen history.** Polycart keeps no history of its own: install [refactor-circus/keen](https://github.com/Refactor-Circus/Keen), the suite-wide audit log (now in `suggest`), and every cart change is recorded there with source `polycart`; without it carts keep no history. The `Activity` domain is removed: `CartActivityModel` and its policy and model events, `ActivityRecorder`, the `Activity` enum, `ListActivityAction` and its events, `$cart->activities`, the `polycart.policies` entry for `CartActivityModel`, and the `RefactorCircus\Polycart\Models\CartActivity` morph alias.
- `GET /polycart/carts/{cart}/activity` and the `list-cart-activity` MCP tool are removed in favour of `GET /polycart/history?subject_type=...&subject_id=...` and `list-polycart-history-tool`.
- The create migration no longer creates `polycart_cart_activities` or the `polycart_carts.sources` column. No migration drops them: existing installs keep the orphaned table and column, which are harmless; drop them when you no longer need the old entries.
- `polycart_carts.source` stays, and is now refactor-circus/keystone's `Surface::current()`: the JSON API records `http` (was `api`), and work outside every surface records `cli` or `code` (was `polycart.default_source`, which is removed). `CartSource` (the enum and the middleware), `SourceContext` and `Cortex\RecordsAgentCartSource` are removed; `Polycart::usingSource()` now wraps `Surface::using()`, and `Surface::route()` replaces the middleware for your own routes.
- `$cart->sources`, `CartModel::scopeTouchedBy()`, the `touched_by` list filter and the `sources` field of cart responses are removed: which surfaces touched a cart is in its history.
- Histories are no longer copied when carts merge or convert. The cart merged into records `carts.merged` with `merged_from`, and a conversion's copy records `cart.converted` with `converted_from`, so a cart's history shows where it came from.
- `$cart->recordActivity()` is removed: Polycart keeps no audit trail of its own. Record your own events about a cart with refactor-circus/keen, `Keen::record('cart.exported')->source('polycart')->on($cart)->with([...])->save()`.
- `RefactorCircus\Polycart\Support\ServiceProvider` is removed; domain providers extend Keystone's `ServiceProvider`.
- Polycart ships no stylesheet: `resources/css/atrium.css` and its `Atrium::css()` registration are removed, and every screen uses only Atrium's components and compiled utilities.

- Polycart now stands on [refactor-circus/keystone](https://github.com/Refactor-Circus/Keystone), the shared runtime of the Refactor Circus packages, which it requires. Its own copies are removed in favour of Keystone's: `Contracts\ActionStartingEvent`, `Contracts\ActionFinishedEvent` and `Contracts\ModelLifecycleEvent` (now `RefactorCircus\Keystone\Contracts\*`), `Support\Models\Concerns\DispatchesModelEvents` (now `RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents`), `Support\Authorizer` (now `RefactorCircus\Keystone\Auth\Authorizer::for($package)`), `Http\Request` (now `RefactorCircus\Keystone\Http\Requests\Request`), `Mcp\Tool` (now `RefactorCircus\Keystone\Mcp\Tool`) and `Cortex\CortexIntegration` (now `RefactorCircus\Keystone\Cortex\CortexIntegration::for($package)`). Listen to the Keystone contracts to hear every action and model event of every Refactor Circus package. Config keys, route names, MCP tool names and behaviour are unchanged.
- `PolycartServiceProvider` extends Keystone's `PackageServiceProvider`, `PolycartServer` extends `RefactorCircus\Keystone\Mcp\Server`, `PolycartException` extends `RefactorCircus\Keystone\Exceptions\PackageException` (still 422), and the base policy extends `RefactorCircus\Keystone\Policies\Policy`: `allowsOnCart()` is now `allowsOn()`.
- MCP requests extending `RefactorCircus\Polycart\Mcp\Request` implement `respond(array $validated)` instead of `handle()`.
- A `PolycartException` now always renders as JSON with its status; previously a request that did not expect JSON fell through to Laravel's own handling.

- The source is reorganised into domain modules under `src/Domains/{Domain}` (`RefactorCircus\Polycart\Domains\{Domain}`): **Cart**, **CartLine**, **CartType**, **Sharing** (members, visibility and access), **Scope** (the scope tree and cart paths) and **Activity** (sources and the activity log). Each domain has its own service provider, registered by `RefactorCircus\Polycart\Domains\DomainServiceProvider`; `PolycartServiceProvider`, the `Polycart` facade, the config file and its keys, route names and URLs, MCP tool names, view and translation namespaces, publish tags and event class names are unchanged. There are no aliases for the old class names: update your imports.
- The Eloquent models are renamed to end in `Model`. Their old class names are kept as morph aliases, so polymorphic `*_type` values written under them still resolve and new rows keep writing the same values:
  - `RefactorCircus\Polycart\Models\Cart` → `RefactorCircus\Polycart\Domains\Cart\Models\CartModel`
  - `RefactorCircus\Polycart\Models\CartActivity` → `RefactorCircus\Polycart\Domains\Activity\Models\CartActivityModel`
  - `RefactorCircus\Polycart\Models\CartLine` → `RefactorCircus\Polycart\Domains\CartLine\Models\CartLineModel`
  - `RefactorCircus\Polycart\Models\CartMember` → `RefactorCircus\Polycart\Domains\Sharing\Models\CartMemberModel`
  - `RefactorCircus\Polycart\Models\CartPath` → `RefactorCircus\Polycart\Domains\Scope\Models\CartPathModel`
- `PolycartSupportFeature` moved to `RefactorCircus\Polycart\Atrium\Features` and keeps its stored Pennant name, `RefactorCircus\Polycart\Features\PolycartSupportFeature`, through Pennant's `#[Name]` attribute.
- `routes/polycart.php` is replaced by a `routes.php` per domain; the routes, their names and middleware are unchanged. `GET carts/{cart}/activity` is served by the new `Domains\Activity\Http\Controllers\CartActivityController` instead of `CartController::activity()`.
- Model events (formerly `Events\Model`) and action events (formerly `Events\Action`) now live in their domain's `Events` namespace, keeping their class names.
- Every other moved class, by its new namespace (old names relative to `RefactorCircus\Polycart`):
  - `RefactorCircus\Polycart\Support`: `Access\Authorizer`
  - `RefactorCircus\Polycart\Domains\Sharing\Services`: `Access\CartAccess`
  - `RefactorCircus\Polycart\Domains\Scope\Services`: `Access\ScopeTree`
  - `RefactorCircus\Polycart\Domains\Cart\Actions`: `Actions\ActiveCartAction`, `Actions\ClearCartAction`, `Actions\ConvertCartAction`, `Actions\CreateCartAction`, `Actions\DeleteCartAction`, `Actions\ListCartsAction`, `Actions\MergeCartsAction`, `Actions\ShowCartAction`, `Actions\TransitionCartAction`, `Actions\UpdateCartAction`
  - `RefactorCircus\Polycart\Domains\CartLine\Actions`: `Actions\AddLineAction`, `Actions\AddLinesAction`, `Actions\RemoveLineAction`, `Actions\RemoveLinesAction`, `Actions\UpdateLineAction`, `Actions\UpdateLinesAction`
  - `RefactorCircus\Polycart\Domains\Activity\Actions`: `Actions\ListActivityAction`
  - `RefactorCircus\Polycart\Domains\CartType\Actions`: `Actions\ListCartTypesAction`
  - `RefactorCircus\Polycart\Domains\Sharing\Actions`: `Actions\ListMembersAction`, `Actions\SetVisibilityAction`, `Actions\ShareCartAction`, `Actions\UnshareCartAction`
  - `RefactorCircus\Polycart\Domains\Cart\Concerns`: `Concerns\HasCarts`
  - `RefactorCircus\Polycart\Domains\CartLine\Contracts`: `Contracts\AddLineStage`, `Contracts\PriceResolver`, `Contracts\Purchasable`
  - `RefactorCircus\Polycart\Domains\Scope\Contracts`: `Contracts\CartParticipant`, `Contracts\CartScope`
  - `RefactorCircus\Polycart\Domains\Activity\Enums`: `Enums\Activity`, `Enums\CartSource`
  - `RefactorCircus\Polycart\Domains\Sharing\Enums`: `Enums\Visibility`
  - `RefactorCircus\Polycart\Domains\Cart\Events`: every model and action event for the domain (35 classes)
  - `RefactorCircus\Polycart\Domains\Activity\Events`: every model and action event for the domain (12 classes)
  - `RefactorCircus\Polycart\Domains\Sharing\Events`: every model and action event for the domain (18 classes)
  - `RefactorCircus\Polycart\Domains\CartType\Events`: every model and action event for the domain (2 classes)
  - `RefactorCircus\Polycart\Domains\CartLine\Events`: every model and action event for the domain (22 classes)
  - `RefactorCircus\Polycart\Domains\Scope\Events`: every model and action event for the domain (10 classes)
  - `RefactorCircus\Polycart\Domains\CartType\Exceptions`: `Exceptions\CartTypeCollisionException`, `Exceptions\UnknownCartTypeException`
  - `RefactorCircus\Polycart\Domains\Cart\Exceptions`: `Exceptions\InvalidConversionException`, `Exceptions\InvalidParentException`, `Exceptions\InvalidTransitionException`
  - `RefactorCircus\Polycart\Domains\Scope\Exceptions`: `Exceptions\InvalidScopeException`
  - `RefactorCircus\Polycart\Domains\CartLine\Exceptions`: `Exceptions\LineRejectedException`
  - `RefactorCircus\Polycart\Domains\Sharing\Exceptions`: `Exceptions\SharingException`
  - `RefactorCircus\Polycart\Atrium\Features`: `Features\PolycartSupportFeature`
  - `RefactorCircus\Polycart\Domains\Cart\Http\Controllers`: `Http\Controllers\CartController`
  - `RefactorCircus\Polycart\Domains\CartLine\Http\Controllers`: `Http\Controllers\CartLineController`
  - `RefactorCircus\Polycart\Domains\Sharing\Http\Controllers`: `Http\Controllers\CartMemberController`
  - `RefactorCircus\Polycart\Domains\CartType\Http\Controllers`: `Http\Controllers\CartTypeController`
  - `RefactorCircus\Polycart\Domains\Activity\Http\Middleware`: `Http\Middleware\CartSource`
  - `RefactorCircus\Polycart\Domains\Cart\Http\Requests`: `Http\Requests\ActiveCartRequest`, `Http\Requests\CartRequest`, `Http\Requests\ClearCartRequest`, `Http\Requests\ConvertCartRequest`, `Http\Requests\DestroyCartRequest`, `Http\Requests\IndexCartsRequest`, `Http\Requests\MergeCartsRequest`, `Http\Requests\ShowCartRequest`, `Http\Requests\StoreCartRequest`, `Http\Requests\TransitionCartRequest`, `Http\Requests\UpdateCartRequest`
  - `RefactorCircus\Polycart\Domains\CartLine\Http\Requests`: `Http\Requests\DestroyLinesRequest`, `Http\Requests\StoreLinesRequest`, `Http\Requests\UpdateLinesRequest`
  - `RefactorCircus\Polycart\Domains\Sharing\Http\Requests`: `Http\Requests\DestroyMemberRequest`, `Http\Requests\IndexMembersRequest`, `Http\Requests\StoreMemberRequest`, `Http\Requests\UpdateVisibilityRequest`
  - `RefactorCircus\Polycart\Domains\Activity\Http\Requests`: `Http\Requests\IndexActivityRequest`
  - `RefactorCircus\Polycart\Domains\CartType\Http\Requests`: `Http\Requests\IndexCartTypesRequest`
  - `RefactorCircus\Polycart\Domains\Activity\Resources`: `Http\Resources\CartActivityResource`
  - `RefactorCircus\Polycart\Domains\CartLine\Resources`: `Http\Resources\CartLineResource`
  - `RefactorCircus\Polycart\Domains\Sharing\Resources`: `Http\Resources\CartMemberResource`
  - `RefactorCircus\Polycart\Domains\Cart\Resources`: `Http\Resources\CartResource`
  - `RefactorCircus\Polycart\Domains\CartType\Http\Resources`: `Http\Resources\CartTypeResource`
  - `RefactorCircus\Polycart\Atrium\Http\Controllers`: `Http\Ui\CartTypeUiController`, `Http\Ui\CartUiController`
  - `RefactorCircus\Polycart\Atrium\Http\Controllers\Concerns`: `Http\Ui\Concerns\AuthorizesScreens`
  - `RefactorCircus\Polycart\Atrium`: `Http\Ui\ScreenAccess`
  - `RefactorCircus\Polycart\Domains\Cart\Mcp\Requests`: `Mcp\Requests\ActiveCartMcpRequest`, `Mcp\Requests\CartRequest`, `Mcp\Requests\ClearCartMcpRequest`, `Mcp\Requests\ConvertCartMcpRequest`, `Mcp\Requests\CreateCartMcpRequest`, `Mcp\Requests\DeleteCartMcpRequest`, `Mcp\Requests\ListCartsMcpRequest`, `Mcp\Requests\MergeCartsMcpRequest`, `Mcp\Requests\ShowCartMcpRequest`, `Mcp\Requests\TransitionCartMcpRequest`, `Mcp\Requests\UpdateCartMcpRequest`
  - `RefactorCircus\Polycart\Domains\CartLine\Mcp\Requests`: `Mcp\Requests\AddLinesMcpRequest`, `Mcp\Requests\RemoveLinesMcpRequest`, `Mcp\Requests\UpdateLinesMcpRequest`
  - `RefactorCircus\Polycart\Domains\Activity\Mcp\Requests`: `Mcp\Requests\ListActivityMcpRequest`
  - `RefactorCircus\Polycart\Domains\CartType\Mcp\Requests`: `Mcp\Requests\ListCartTypesMcpRequest`
  - `RefactorCircus\Polycart\Domains\Sharing\Mcp\Requests`: `Mcp\Requests\ListMembersMcpRequest`, `Mcp\Requests\SetVisibilityMcpRequest`, `Mcp\Requests\ShareCartMcpRequest`, `Mcp\Requests\UnshareCartMcpRequest`
  - `RefactorCircus\Polycart\Domains\Cart\Mcp\Tools`: `Mcp\Tools\ActiveCartTool`, `Mcp\Tools\ClearCartTool`, `Mcp\Tools\ConvertCartTool`, `Mcp\Tools\CreateCartTool`, `Mcp\Tools\DeleteCartTool`, `Mcp\Tools\ListCartsTool`, `Mcp\Tools\MergeCartsTool`, `Mcp\Tools\ShowCartTool`, `Mcp\Tools\TransitionCartTool`, `Mcp\Tools\UpdateCartTool`
  - `RefactorCircus\Polycart\Domains\CartLine\Mcp\Tools`: `Mcp\Tools\AddLinesTool`, `Mcp\Tools\RemoveLinesTool`, `Mcp\Tools\UpdateLinesTool`
  - `RefactorCircus\Polycart\Domains\Activity\Mcp\Tools`: `Mcp\Tools\ListActivityTool`
  - `RefactorCircus\Polycart\Domains\CartType\Mcp\Tools`: `Mcp\Tools\ListCartTypesTool`
  - `RefactorCircus\Polycart\Domains\Sharing\Mcp\Tools`: `Mcp\Tools\ListMembersTool`, `Mcp\Tools\SetVisibilityTool`, `Mcp\Tools\ShareCartTool`, `Mcp\Tools\UnshareCartTool`
  - `RefactorCircus\Polycart\Support\Models\Concerns`: `Models\Concerns\DispatchesModelEvents`
  - `RefactorCircus\Polycart\Domains\CartLine\Support`: `Pipeline\PendingLine`, `Support\LineInput`, `Support\LineLookup`
  - `RefactorCircus\Polycart\Domains\CartLine\Support\Stages`: `Pipeline\Stages\BuildLine`, `Pipeline\Stages\CheckAccepted`, `Pipeline\Stages\FindMatchingLine`, `Pipeline\Stages\PrepareLine`, `Pipeline\Stages\ResolvePrice`, `Pipeline\Stages\ValidateLine`, `Pipeline\Stages\WriteLine`
  - `RefactorCircus\Polycart\Domains\Activity\Policies`: `Policies\CartActivityPolicy`
  - `RefactorCircus\Polycart\Domains\CartLine\Policies`: `Policies\CartLinePolicy`
  - `RefactorCircus\Polycart\Domains\Sharing\Policies`: `Policies\CartMemberPolicy`
  - `RefactorCircus\Polycart\Domains\Cart\Policies`: `Policies\CartPolicy`
  - `RefactorCircus\Polycart\Support\Policies`: `Policies\Policy`
  - `RefactorCircus\Polycart\Domains\CartLine\Services`: `Pricing\PurchasablePriceResolver`
  - `RefactorCircus\Polycart\Domains\Activity\Services`: `Support\ActivityRecorder`, `Support\SourceContext`
  - `RefactorCircus\Polycart\Domains\CartType\Support`: `Types\CartType`, `Types\ShoppingCart`
  - `RefactorCircus\Polycart\Domains\CartType\Services`: `Types\CartTypeRegistry`

### Added

- The line, sharing, merge and conversion action events implement `RefactorCircus\Keystone\Audit\Contracts\Auditable`, naming the cart as the subject of their audit entry with the line, member, `merged_from` or `converted_from` details as context. Carts (by label, else id) and lines are labelled through Keystone's `AuditHooks`.
- With refactor-circus/keen installed, a cart's history shows in Atrium through Keen's History section on Polycart's screens (see the screen extensions entry above).
- `GET {prefix}/history` (`polycart.history.index`) and the `list-polycart-history-tool` MCP tool serve Polycart's audit history from an installed audit log such as refactor-circus/keen, and answer "not installed" (404 over HTTP) until one is.
- Polycart's Atrium navigation items have icons, and the screens follow Atrium's screen conventions: actions are icon buttons with their label as a tooltip, and cart statuses are status dots (`data-status`) coloured by `RefactorCircus\Polycart\Atrium\Badges`, which keeps `info` for pending and awaiting states.
- The `@polycartCan` Blade conditional and `RefactorCircus\Polycart\Atrium\ScreenAccess`, which ask the cart policies exactly as the JSON API does.
- `RefactorCircus\Polycart\Atrium\Features\PolycartSupportFeature` and the `polycart.atrium.features` config: with refactor-circus/pennantplus installed, a global Pennant switch for Polycart in Atrium. Feature classes that cannot be loaded are skipped.
- `polycart.atrium.show_all` (default `false`): `true`, or the name of a Gate ability, makes users dashboard operators who see every cart in Atrium's lists, widgets and search and may take every action on it. The JSON API and MCP tools are unaffected.

### Fixed

- Tailwind utilities that only Polycart's Atrium screens use (`w-48`, `gap-6`, `sm:grid-cols-3`, ...) are now compiled into Atrium's stylesheet, so the **Move to** and **Convert to** selects and the cart grids are sized as intended. The **Keep the original** checkbox no longer squeezes the **Convert to** select.

### Changed

- Requires PHP 8.5 (was PHP 8.4), with dependency lower bounds raised to the latest releases (`laravel/framework` ^13.35, `orchestra/testbench` ^11.3, `pestphp/pest` ^5.3.1, `larastan/larastan` ^3.13).
- The cart page's details use `x-atrium::description-list`, and every screen shows its flash status and errors with `x-atrium::flash`; the `ui/partials/status` view is removed.
- `PolycartPlugin::features()` uses Atrium's `featuresFromConfig()`, and `ScreenAccess::allows()` delegates to Atrium's `ScreenAccess` for abilities without arguments.
- With `polycart.authorization` on, the Atrium dashboard now applies the same per-user policies as the JSON API and MCP tools. Navigation, widgets and search need `viewAny` on `Cart`; each page action checks the ability its API request checks and answers 403 otherwise; controls the user may not use are hidden; and lists, widgets and search hold only the carts the user can access. Previously the dashboard relied on Atrium's gate alone.
- `RefactorCircus\Polycart\Atrium\Format::variant()` is replaced by `Badges::forCart()`.
