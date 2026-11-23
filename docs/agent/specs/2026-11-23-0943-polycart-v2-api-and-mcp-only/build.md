# Build log

## Group 1

Stats and search API, cart-stats MCP tool (criteria 1-7).

### Files

- Added `src/Domains/Cart/Actions/ShowCartStatsAction.php`: a final read action taking `CartTypeRegistry`. `execute(?Model $viewer = null): CartStats` dispatches `CartStatsShowingActionEvent`, then runs one grouped count query (`ofType(...registered)->unexpired()->toBase()->select('type')->selectRaw('count(*) as aggregate')->groupBy('type')`, zero-filled in registry order) and one recent query (`withCount('lines')->latest('updated_at')->latest('id')->limit(5)`), both through `accessibleBy($viewer)` when a viewer is given, then dispatches `CartStatsShownActionEvent`.
- Added `src/Domains/Cart/Events/CartStatsShowingActionEvent.php` (viewer) and `CartStatsShownActionEvent.php` (stats, viewer).
- Added `src/Domains/Cart/Support/CartStats.php`: `final readonly`, `array<string,int> $byType`, `EloquentCollection<int, CartModel> $recent`.
- Added `src/Domains/Cart/Http/Resources/CartStatsResource.php`: final, `@property CartStats $resource`. `toArray()` gives `by_type` as a list of `{type, count}` and `recent` as `CartResource::collection(...)->resolve($request)`. `schema()` declares both.
- Added `src/Domains/Cart/Http/Requests/ShowCartStatsRequest.php` and `SearchCartsRequest.php`. Search's `q` rules are `required` plus `ListCartsData::rules()['search']` without `sometimes`/`nullable`, plus `ListCartsData::pagination()->rules()`. `persist()` builds `ListCartsData::fromValidated(['search' => q, per_page, cursor])` and calls `ListCartsAction`.
- Changed `src/Domains/Cart/Http/Controllers/CartController.php` (`stats`, `search`) and `src/Domains/Cart/routes.php` (`stats` → `stats.show`, `search` → `search.index`).
- Added `src/Domains/Cart/Mcp/Requests/ShowCartStatsMcpRequest.php` and `src/Domains/Cart/Mcp/Tools/CartStatsTool.php` (`#[Name('cart-stats')]`, `outputSchema` = `CartStatsResource::structuredSchema()`), and registered the tool in `src/Mcp/PolycartServer.php` after `ListCartsTool`.

### Tests

- `tests/Feature/StatsTest.php` (new): registry order with zeros; expired, deleted and unregistered-type carts not counted (expired and unregistered still recent); viewer scoping; five most recent, ties by id descending, with `lines_count` and no `lines`; Showing before the first query, Shown after the last, carrying the result; the same query count for 1 and 20 carts and for 1 and 10 types.
- `tests/Feature/ApiTest.php`: `polycart.stats.show` JSON paths; `polycart.search.index` label and id matches, the same ids and order as `polycart.carts.index?search=`, cursor paging; 422 for a missing, empty or 192-character `q` and `per_page=201`; 200 at 191 characters and `per_page=200`.
- `tests/Feature/AccessTest.php`: 401 for a guest on both endpoints; 403 on both (and `Unauthorized.` over MCP) when `viewAny` is refused; counts, recent, search and `cart-stats` limited to the user's carts.
- `tests/Feature/Mcp/McpTest.php`: `cart-stats` returns `data.by_type`/`data.recent`, checked against the tool's output schema.
- `tests/Cortex/CortexTest.php`: `cart-stats` is offered to Cortex.
- The suite was not run in this step; the gate runs `vendor/bin/pest`. Pint passes on the changed files, and PHPStan reports nothing for the new source files.

### Deviations

- Changed `tests/Feature/Mcp/OutputSchemaTest.php`, which is outside the group's file list: the number of tools with structured content goes from 19 to 20 because of `cart-stats`.
- Changed `tests/ArchTest.php`, which is outside the group's file list. The rule "every MCP request has a tool on the server" matched `{X}McpRequest` to `{X}Tool::class` by name, so the spec's pair `ShowCartStatsMcpRequest`/`CartStatsTool` (AC 7) could never pass it. The rule now checks that some tool in `PolycartServer::TOOLS` takes the request in its `handle()`. That is the same rule with the name coupling removed, and every existing pair still passes. `PARITY_EXCEPTIONS` is unchanged.
