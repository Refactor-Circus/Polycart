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

## Group 2

Remove the Atrium plugin from code, config and tests (criteria 8, 9, 10, 13).

### Files

- Deleted `src/Atrium/` (plugin, screens, controllers, `ScreenAccess`, `PolycartSupportFeature`, `CartTypeRow`, `Badges`, `Format`), `resources/views/` and `lang/`.
- `src/PolycartServiceProvider.php`: `boot()` is now policies, MCP server, Cortex, history routes, not-found responses and the config/migrations publishes. There is no `registerAtriumPlugin()`, `loadViewsFrom()`, `Blade::if('polycartCan')`, `loadTranslationsFrom()`, `polycart-views` or `polycart-lang`.
- `config/polycart.php`: removed the `ui` and `atrium` sections and the `PolycartSupportFeature` import. The `routes` banner now names the stats and search endpoints, says that `openapi.json` and the TypeScript client (`@refactor-circus/polycart-client`) describe the routes, and says that a front end built on the API needs it on, behind the host's auth middleware. `routes.enabled` stays `false`. The `audit` banner names only the history route and MCP tool. The file no longer contains "atrium" or "dashboard".
- `src/Domains/Cart/Mcp/Tools/ListCartsTool.php`: removed `atrium` from the example sources in the `source` description.
- Deleted the Atrium-only tests: `tests/Feature/Ui/`, `tests/Feature/NavigationSectionTest.php`, `tests/Unit/CartTypeRowTest.php`, `tests/PennantPlus/`, `tests/PennantPlusTestCase.php`, `tests/Fixtures/Features/` and `tests/Fixtures/views/`.
- `tests/Pest.php`: removed the `PennantPlusTestCase` `uses()`.
- `tests/TestCase.php`: registers only `McpServiceProvider` and `PolycartServiceProvider`. It no longer uses `ResetsScreenExtensions`, adds the `polycart-tests` view namespace or sets `atrium.disabled`.
- `tests/Feature/SourceTest.php`: removed the case for copies made from the dashboard.
- `tests/Feature/PerformanceTest.php`: removed the two dashboard query-budget cases and the carts-by-type widget case. `StatsTest`'s "same queries however many carts and types" case (Group 1) now holds the counts budget.
- `tests/Keen/HistoryTest.php`: removed the Atrium-only `beforeEach` (CSRF exception, local environment) and the case that opened the Atrium screens. Its history route and tool assertions duplicated "serves a cart's history from the history endpoint and tool", which stays.
- `tests/Feature/DocsTest.php`: removed the two screen-key cases and `DOCS_SCREEN_KEYS`, keeping the audit-trail case. Group 5 adds the new docs promises.

### Tests

- `tests/ArchTest.php`: "the package uses no Atrium class" is `expect('RefactorCircus\Polycart')->not->toUse('RefactorCircus\Atrium')`, with no `ignoring()`.
- `tests/Feature/ServiceProviderTest.php`:
  - The `polycart` tag publishes exactly `config/polycart.php` and `database/migrations`.
  - The `polycart-views` and `polycart-lang` groups are absent and publish nothing.
  - There is no `polycart` view hint or translation namespace.
  - There are no `polycartCan`/`endpolycartCan`/`unlesspolycartCan` Blade directives.
  - The config file has no `ui` or `atrium` key, no `PolycartSupportFeature` and no "atrium" or "dashboard" text.
  - `routes.enabled` defaults to `false`, and the file mentions `openapi.json`, the client and auth middleware.
  - With `polycart.routes.enabled` off, booting `CartServiceProvider` on an emptied router registers neither `polycart.stats.show` nor `polycart.search.index`, and with it on it registers both.
- I did not run the suite in this step; the gate runs `vendor/bin/pest`. `php -l` passes on the changed PHP files.

### Deviations

- Changed `phpunit.xml.dist`, which is outside the group's file list: removed the `PennantPlus` test suite, whose directory this group deletes. The config has `failOnWarning` and `failOnEmptyTestSuite`, so a suite pointing at a missing directory would fail `vendor/bin/pest`.
- I left `tests/WithoutAtrium/`, `phpunit.without-atrium.xml`, the Atrium cases in `tests/Feature/ComposerTest.php`, `composer.json` and the workbench to Group 3, as the plan assigns. Until Group 3 lands, the `WithoutAtrium` suite still refers to deleted code. It runs only through `composer test:without-atrium`, never `vendor/bin/pest`.

## Group 3

Remove Atrium and PennantPlus from composer, workbench and CI (criteria 11, 12, 13, 14).

### Files

- `composer.json`: removed `refactor-circus/atrium` and `refactor-circus/pennantplus` from `require-dev` and `suggest`, the `conflict` section (it held only Atrium), the Atrium and PennantPlus VCS repositories, the `test:without-atrium` script and its entry in `test`. `require` still names only `refactor-circus/keystone` from the suite. There was no `extra.atrium`.
- Deleted `tests/WithoutAtrium/` and `phpunit.without-atrium.xml`.
- `.github/workflows/tests.yml`: removed the `without-atrium` job.
- `.gitattributes`: removed the `/phpunit.without-atrium.xml` line.
- `testbench.yaml`: removed `RefactorCircus\Atrium\AtriumServiceProvider`, the `laravel/pennant` migrations line and the `atrium-assets` asset. `workbench.start` is `/polycart/carts`.
- `workbench/app/Providers/WorkbenchServiceProvider.php`: removed the Pennant store, `polycart.ui.enabled` and `polycart.atrium.show_all`. It sets `polycart.routes.enabled => true`, and the docblock describes the JSON API and MCP server.
- `workbench/database/seeders/DatabaseSeeder.php`: every `'atrium'` source is now `'http'`, and the docblock says "the API" instead of "the dashboard".

### Tests

- `tests/Feature/ComposerTest.php`:
  - Keystone is the only suite package in `require`.
  - Atrium and PennantPlus are absent from `require`, `require-dev`, `suggest` and `conflict`, which is a dataset with one case per section.
  - No repository URL names Atrium or PennantPlus.
  - There is no `extra.atrium`.
  - There is no `test:without-atrium` script, and `test` is exactly analyse, lint:check, test:types and test:unit.
  - Cortex and Keen stay in `require-dev`.
  - The WithoutAtrium cases are gone.
- `tests/Feature/NoAtriumTest.php` (new):
  - "atrium" (case-insensitive) appears in no file name or contents under `src/`, `config/`, `database/`, `workbench/`, `testbench.yaml`, `composer.json`, `phpunit.xml.dist`, `.gitattributes` or `.github/`.
  - `phpunit.without-atrium.xml` and `tests/WithoutAtrium/` are gone, and the CI workflow has no `without-atrium`.
  - Registering `WorkbenchServiceProvider` turns `polycart.routes.enabled` on and sets no `polycart.ui` key and no PennantPlus store.
  - `testbench.yaml`'s `workbench.start` starts with `/polycart/`. Its providers name no Atrium class, its migrations have no Pennant line, and its assets are only `laravel-assets`.
  - The seeder's sources are only `http`, `mcp` and `code`.
- `composer update` resolves. I ran it through the local manifest (`bin/composer-local.php`, then `COMPOSER=composer.local.json composer update refactor-circus/atrium refactor-circus/pennantplus --with-all-dependencies`). It removed `refactor-circus/atrium`, `refactor-circus/pennantplus` and `laravel/pennant` from `vendor/`.
- `ComposerTest` and `NoAtriumTest` pass (14 tests), and Pint passes on the changed PHP files. I did not run the full suite; the gate runs `vendor/bin/pest`.

### Deviations

- Regenerating the gitignored `composer.local.json` with `bin/composer-local.php` wrote the committed `^0.1` constraints for Keystone, Cortex and Keen. The path repositories offer only `dev-main`, so I set those three back to `dev-main` in the local file, as the job's install had them (the lock's stability flags). No committed file is affected.
- The workbench JSON API runs on the `api` middleware group, while `SignInWorkbenchUser` is appended to `web`. With `polycart.authorization` on (the default), opening `start` in a browser answers 401. This is raised as a decision; nothing was changed for it.
