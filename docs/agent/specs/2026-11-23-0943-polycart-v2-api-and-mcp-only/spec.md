# Polycart v2: API and MCP only

## Goal

Polycart v2.0.0 ships as a JSON API and an MCP server, with no Atrium plugin. Hosts and Atrium v2 get the data the dashboard used to compute in Blade through two new endpoints, and they call the API through a committed OpenAPI document and a generated TypeScript client.

## Acceptance criteria

**New API**

1. `GET {prefix}/stats`, named `polycart.stats.show`, answers 200 with `{"data": {"by_type": [...], "recent": [...]}}`. `by_type` has one `{"type": string, "count": int}` entry per type in `CartTypeRegistry::all()`, in registry order. Each count is the caller's accessible carts of that type that are neither expired nor deleted (every cart when `polycart.authorization` is off). A type with no carts counts 0, and carts of unregistered types are left out.
2. `recent` in the stats payload holds up to 5 carts the caller can access, of any type, expired or not, most recently updated first (ties by `id` descending). Each one is the `CartResource` payload with `lines_count` and without `lines`.
3. `ShowCartStatsAction::execute(?Model $viewer = null): CartStats` dispatches `CartStatsShowingActionEvent` before the read and `CartStatsShownActionEvent` (carrying the result) after it. Its query count stays the same whether there are 1 or 20 carts and 1 or 10 types.
4. `GET {prefix}/search?q=`, named `polycart.search.index`, answers 200 with the carts the caller can access whose `label` contains `q` or whose `id` starts with `q`. The body has the same shape and order as `polycart.carts.index`: a `CartResource` collection with `lines_count`, newest first, cursor paged through `per_page` (default 25, max 200) and `cursor`. It runs through `ListCartsAction` with the `search` filter, so it adds no action.
5. Search refuses a missing or empty `q`, a `q` over 191 characters and an out-of-range `per_page` with 422 and an error on that field.
6. With `polycart.authorization` on, both endpoints answer 401 to a guest and 403 to a signed-in user whom the `CartModel` policy refuses `viewAny`. A signed-in user's counts, recent carts and search results include only carts that user can access.
7. The MCP tool `cart-stats` (`CartStatsTool`, through `ShowCartStatsMcpRequest`) returns the stats payload as structured content under `data`. Its output schema is built from `CartStatsResource::schema()`. It is registered on `PolycartServer` and offered to Cortex. The arch rule "every use case is reachable from the JSON API and MCP" passes without a new `PARITY_EXCEPTIONS` entry.
8. `polycart.routes.enabled` still defaults to `false`. With it off, neither new route is registered. The `routes` banner in `config/polycart.php` says that the OpenAPI document and client describe these routes, and that a front end built on them needs the API on, behind the host's auth middleware.

**Removal**

9. `src/Atrium/`, `resources/views/` and `lang/` are gone. `PolycartServiceProvider` no longer calls `registerAtriumPlugin()`, `loadViewsFrom()`, `loadTranslationsFrom()` or `Blade::if('polycartCan', ...)`, and it no longer publishes the `polycart-views` or `polycart-lang` tags. The `polycart` tag publishes only the config and the migrations.
10. `config/polycart.php` has no `ui` key, no `atrium` key and no `PolycartSupportFeature` import, and the word "atrium" appears nowhere in it (case-insensitive). The `audit` banner no longer mentions dashboard pages.
11. `tests/ArchTest.php` asserts that `RefactorCircus\Polycart` uses no `RefactorCircus\Atrium` class, with no `ignoring()`. A test asserts that "atrium" (case-insensitive) appears in none of `src/`, `config/`, `database/`, `workbench/`, `testbench.yaml`, `composer.json`, `phpunit.xml.dist`, `.gitattributes` or `.github/`.
12. In `composer.json`, `refactor-circus/atrium` and `refactor-circus/pennantplus` are absent from `require`, `require-dev`, `suggest` and `conflict`, and their VCS repositories are removed. There is no `extra.atrium` and no `test:without-atrium` script. `refactor-circus/keystone` is the only suite package in `require`.
13. The Atrium-only tests are gone: `tests/Feature/Ui/`, `tests/Feature/NavigationSectionTest.php`, `tests/Unit/CartTypeRowTest.php`, `tests/PennantPlus/`, `tests/PennantPlusTestCase.php`, `tests/Fixtures/Features/`, `tests/Fixtures/views/`, `tests/WithoutAtrium/` and `phpunit.without-atrium.xml`. The Atrium cases in `SourceTest`, `PerformanceTest` and `Keen/HistoryTest` are gone too. `tests/TestCase.php` registers neither `AtriumServiceProvider` nor `ResetsScreenExtensions`. The CI workflow has no `without-atrium` job.
14. The workbench registers no Atrium provider or assets and no Pennant store. It turns `polycart.routes.enabled` on, and its `workbench.start` is a JSON API path. Its seeder records no `atrium` source.

**OpenAPI and client**

15. `dedoc/scramble` is in `require-dev` only, and an arch rule asserts that `RefactorCircus\Polycart` uses no `Dedoc\Scramble` class.
16. The workbench command `php vendor/bin/testbench workbench:openapi {path}` writes the OpenAPI document of the `polycart/*` routes. The output is the same on every run: it uses an in-memory SQLite database with the migrations run, a fixed server URL (`/polycart`) and `info.version` `2.0.0`. The committed root `openapi.json` is its output, with one operation for every `polycart.*` API route the workbench registers, `polycart.stats.show` and `polycart.search.index` included.
17. In `openapi.json`, the stats operation's 200 response schema has `data.by_type` and `data.recent`, and the search operation has a required `q` query parameter.
18. `client/` holds a `package.json` and a committed `package-lock.json`. The `package.json` has `name` `@refactor-circus/polycart-client`, `version` `2.0.0` and `type` `module`, and its `exports`/`types` point at `src/index.ts`. `openapi-fetch` is a dependency; `openapi-typescript` and `typescript` are devDependencies. It has a `generate` script (which writes `src/schema.d.ts` and `openapi.sha256`) and a `typecheck` script (`tsc --noEmit`). `src/index.ts` exports `createPolycartClient(options)`, which returns `openapi-fetch`'s `createClient<paths>(options)`, and re-exports the `paths`, `components` and `operations` types.
19. `composer client:generate` regenerates `openapi.json` through `workbench:openapi`, runs `npm --prefix client ci` and runs `npm --prefix client run generate`. A CI `client` job runs it, then `git diff --exit-code -- openapi.json client`, then `npm --prefix client run typecheck`.
20. `tests/Feature/OpenApiTest.php` fails when the committed `openapi.json` differs (as decoded JSON) from a fresh `workbench:openapi` export, and when `client/openapi.sha256` is not the SHA-256 of `openapi.json`. `.gitattributes` export-ignores `/client` and no longer names `phpunit.without-atrium.xml`.

**Docs and gate**

21. The README describes Polycart as a JSON API plus MCP, with Atrium v2 as the optional UI. It says the API is off by default and that Atrium v2 needs `polycart.routes.enabled` on, behind auth middleware. It documents both new endpoints with example payloads, the `cart-stats` tool, `openapi.json`, installing and using the client (`createPolycartClient`), and `composer client:generate`. It has no Atrium screens, operators, feature-flag or views/lang publishing sections, and `tests/Feature/DocsTest.php` asserts these promises in place of the screen-key ones.
22. `CHANGELOG.md` has a `v2.0.0` section. Its Breaking entries cover the removed Atrium UI, the removed config keys, the `polycart-views`/`polycart-lang` tags and the translations. Its upgrade note points to Atrium v2 and to `polycart.routes.enabled`. Its Added entries list stats, search, `cart-stats`, `openapi.json` and the client.
23. `CLAUDE.md` and `AGENTS.md` (kept identical), the Boost skill `resources/boost/skills/polycart-development/SKILL.md` and `docs/*.md` describe the package as API plus MCP. They cover the new endpoints and how to regenerate the client, and none mentions `src/Atrium`, screen keys, `ScreenAccess`, `@polycartCan`, `PolycartSupportFeature` or `polycart.atrium`.
24. `vendor/bin/pest` passes, and so does `composer test`: PHPStan, Pint, 100% type coverage and the unit suite.

## Design

**Stats (Cart domain).**
- `Actions/ShowCartStatsAction` is a final read action with no Data, like `ListCartTypesAction`. Its constructor takes `CartTypeRegistry`.
- `perform(?Model $viewer)` builds a base query: `CartModel::query()`, plus `accessibleBy($viewer)` when a viewer is given.
- Counts take one grouped query, lifted from `PolycartPlugin::countsByType()`: `ofType(...registered)->unexpired()->toBase()->select('type')->selectRaw('count(*) as aggregate')->groupBy('type')`, zero-filled in registry order.
- Recent takes one query: `withCount('lines')->latest('updated_at')->latest('id')->limit(5)->get()`.
- The result is `Support/CartStats`, a `final readonly` class with `public array $byType` (`array<string,int>`) and `public EloquentCollection $recent`.
- Events: `Events/CartStatsShowingActionEvent` (viewer) and `Events/CartStatsShownActionEvent` (result, viewer), shaped like `CartsListing`/`CartsListedActionEvent`.
- `Http/Resources/CartStatsResource` is final, `@property CartStats $resource`, and extends Keystone's `JsonResource`. Its `toArray()` returns `by_type` as a list of `{type, count}` and `recent` as `CartResource::collection(...)->resolve($request)`. `schema()` declares `by_type` (array of objects) and `recent` (array of `CartResource::schema()` objects).
- `Http/Requests/ShowCartStatsRequest` authorizes `parent::authorize() && allows('viewAny', CartModel::class)`. Its `persist()` returns `(new CartStatsResource(app(ShowCartStatsAction::class)->execute($this->actor())))->response()`.
- MCP: `Mcp/Requests/ShowCartStatsMcpRequest` makes the same check and returns `->toStructuredResponse()`. `Mcp/Tools/CartStatsTool` is `#[Name('cart-stats')]` and is added to `PolycartServer`'s tool list.

**Search.** `Http/Requests/SearchCartsRequest`:
- Authorizes `viewAny` on `CartModel`, like `IndexCartsRequest`.
- `rules()` returns `['q' => ['required', ...ListCartsData::rules()['search'] without 'sometimes'/'nullable']] + ListCartsData::pagination()->rules()`.
- `persist()` calls `ListCartsAction::execute(ListCartsData::fromValidated(['search' => q, 'per_page' => ..., 'cursor' => ...]), $this->actor())` and returns `CartResource::collection(...)->response()`.
- MCP already searches through `list-carts`'s `search` argument, so no new tool is needed.

**HTTP wiring.** `CartController` gains `stats(ShowCartStatsRequest)` and `search(SearchCartsRequest)`, each `return $request->persist();`. `Domains/Cart/routes.php` adds these routes inside the shared group from `loadApiRoutesFrom()`:
- `Route::get('stats', ...)->name('stats.show')`
- `Route::get('search', ...)->name('search.index')`

The resulting endpoints:

| Method | URI | Name |
| --- | --- | --- |
| GET | `{prefix}/stats` (default `polycart/stats`) | `polycart.stats.show` |
| GET | `{prefix}/search?q=` (default `polycart/search`) | `polycart.search.index` |

Example stats payload:

```json
{"data": {"by_type": [{"type": "cart", "count": 3}, {"type": "quote", "count": 0}],
          "recent": [{"id": "01J...", "type": "cart", "label": "Lobby", "lines_count": 2, "...": "..."}]}}
```

**Removal.**
- Delete `src/Atrium/**`, `resources/views/**` and `lang/**`.
- Trim `PolycartServiceProvider::boot()` to policies, MCP, Cortex, history routes, not-found responses and the config/migrations publishes.
- Remove the `ui` and `atrium` sections from `config/polycart.php`, and rewrite the `routes` and `audit` banners.
- In `ListCartsTool`'s `source` description, drop `atrium` from the example sources.
- composer.json: drop Atrium and PennantPlus from `require-dev`, `suggest`, `conflict` and `repositories`; drop the `test:without-atrium` script and its entry in `test`; add `dedoc/scramble` to `require-dev`; add the `client:generate` script.
- Workbench:
  - `testbench.yaml`: remove the Atrium provider, the `atrium-assets` and pennant migrations lines, and set `start: /polycart/carts`.
  - `WorkbenchServiceProvider`: drop `polycart.ui.enabled`, `polycart.atrium.show_all` and the pennant store; set `polycart.routes.enabled => true`, the Scramble config (`scramble.api_path => 'polycart'`, fixed server and version), and register `Workbench\App\Console\Commands\ExportOpenApiCommand` (`workbench:openapi`).
  - `DatabaseSeeder`: record `http` where it recorded `atrium`.
- CI: remove the `without-atrium` job and add a `client` job (setup-php plus setup-node).

**OpenAPI and client.**
- `ExportOpenApiCommand` points the default connection at an in-memory SQLite database, runs the package and workbench migrations, then calls `scramble:export --path={path}`.
- `client/package.json`'s `generate` script runs `openapi-typescript ../openapi.json -o src/schema.d.ts && node scripts/stamp.mjs`. `stamp.mjs` writes the SHA-256 of `../openapi.json` to `openapi.sha256`.
- The client ships as TypeScript source with no build step: Atrium's Nuxt layer compiles it.

**Config.** The only changes to `config/polycart.php` are the removed `ui`/`atrium` keys and the rewritten banners. `routes.enabled` stays `false`.

**Versioning.** The release is tagged v2.0.0. `refactor-circus/keystone` stays `^0.1` (D-013).

## Out of scope

- Atrium-side work: the pages, widgets, search source, operator mode and `PolycartSupportFeature` in `atrium.integrations.polycart.*`. That belongs to Atrium's v2 spec.
- A `settings-summary` endpoint: Polycart had no settings panel (D-006).
- Publishing the client to npm. It is consumed as a git dependency on the v2.0.0 tag.
- New MCP tools beyond `cart-stats`. Search is already `list-carts` with `search`.
- Changing `polycart.routes.enabled`'s default, or any existing endpoint's payload.
- Keystone's removal of `registerAtriumPlugin()`, which is its own spec, waited for here.

## Risks and open questions

- **Scramble's inference.** Controllers return `$request->persist()`, so Scramble may infer only `JsonResponse`, which would give `openapi.json` weak response schemas. AC 17 forces at least the new operations to carry their shape. If inference falls short, the implementer adds `@response` PHPDoc types on the controller methods, or a workbench-only Scramble operation extension that reads each resource's `schema()`. Either one stays out of `src/`.
- **Scramble and Laravel 13.** The Scramble release must support Laravel 13 and PHP 8.5. If none does, stop and record it in `standards-feedback.md` rather than vendoring a fork.
- **Git dependency on a subfolder.** Atrium installs `client/` from the tag. pnpm supports `#v2.0.0&path:/client`, but npm does not install a subfolder from git. Atrium's spec owns the package manager choice; this spec only guarantees that `client/` is self-contained.
- **Hosts upgrading** lose any published `polycart` views and translations. The CHANGELOG says to delete `resources/views/vendor/polycart` and `lang/vendor/polycart`.
- **Ordering.** This waits for Keystone `drop-the-atrium-plugin-hook`, but Polycart works against Keystone with or without the hook because it no longer calls it. If Keystone ships as v1.0.0, a later constraint bump is needed (D-013).
- **Search ordering** is now newest-created first (the list's order), where Atrium v1's search ordered by most recently updated.
