# Decisions

## D-001: Does polycart.routes.enabled stay off by default?
- Choice: Yes, it stays `false`. The config banner and README tell hosts to turn it on behind their own auth middleware; the README says Atrium v2 needs it on.
- Rationale: The API reads and changes every cart, and architecture/configuration asks for safe security defaults. Nothing in the code needs it on: Keystone's `loadApiRoutesFrom()` already gates on it. Only the workbench turns it on.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-002: What exactly is the stats payload?
- Choice: `{"data": {"by_type": [{"type", "count"}...], "recent": [CartResource...]}}`. by_type has one entry per registered type in registry order, zero-filled. It counts the caller's accessible carts that are neither expired nor deleted, and leaves out unregistered types. recent holds the 5 most recently updated accessible carts (ties by id descending), any type and expired or not, as `CartResource` with `lines_count`. The limit of 5 is fixed and takes no input.
- Rationale: It reproduces exactly what the v1 widgets computed (`PolycartPlugin::countsByType()` and the recent-carts resolver), so Atrium v2 shows the same numbers. A list of objects, rather than a map keyed by type, gives OpenAPI and the TypeScript client a stable, typed shape.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-003: What contract does search have?
- Choice: `GET {prefix}/search?q=`: `q` is required, with ListCartsData's `search` rules (string, max 191), plus the list's `per_page` (default 25, max 200) and `cursor`. It runs through `ListCartsAction` with the `search` filter: label contains q, or id starts with q, newest created first, scoped to the actor. Its body is the same as `polycart.carts.index`.
- Rationale: It reuses the existing, tested action and payload, adds no new action (so no new parity gap), and matches v1 search's matching rule. The ordering changes from updated_at to created_at; that is listed as a risk.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-004: What are the new routes called, and where do they live?
- Choice: `GET {prefix}/stats` is `polycart.stats.show` and `GET {prefix}/search` is `polycart.search.index`. Both are in `src/Domains/Cart/routes.php`, on `CartController::stats`/`search`.
- Rationale: They follow http/routes (resource plus operation, like Keystone's `polycart.history.index`), and both endpoints read carts.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-005: Does stats get an MCP tool?
- Choice: Yes: `cart-stats` (`CartStatsTool` through `ShowCartStatsMcpRequest`). Search needs no tool, because `list-carts` already takes `search`.
- Rationale: The ArchTest parity rule requires every action to be reachable from HTTP and MCP. Following it is cheaper and more consistent than adding a PARITY_EXCEPTIONS entry, and the package is "API + MCP".
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-006: Is there a settings-summary endpoint?
- Choice: No.
- Rationale: The shape note asks for one only "where the package had a settings panel". Polycart's Atrium plugin had none: its Types screen is already served by `GET polycart/types`.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-007: Do the Atrium-only translations and the views/lang publish tags go too?
- Choice: Yes. Delete `lang/` and `resources/views/`, and drop `loadTranslationsFrom`, `loadViewsFrom`, `Blade::if('polycartCan')` and the `polycart-views`/`polycart-lang` tags. The CHANGELOG tells hosts to delete their published copies.
- Rationale: Every `polycart::` string and view was used only by src/Atrium. Keeping them would ship dead UI code.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-008: What happens to refactor-circus/pennantplus?
- Choice: Remove it from require-dev, suggest and repositories, along with its tests, test case, fixture feature, workbench Pennant store and testbench migrations line.
- Rationale: It existed only for `PolycartSupportFeature`, which moves to Atrium per the shape.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-009: What happens to the WithoutAtrium suite and its CI lane?
- Choice: Delete `tests/WithoutAtrium`, `phpunit.without-atrium.xml`, the `test:without-atrium` script and the `without-atrium` CI job. The stricter ArchTest rule (no `ignoring()`), the "no atrium" text scan and the composer.json assertions replace them.
- Rationale: Once Atrium is out of require-dev, it is no longer installed (neither Cortex nor Keen requires it), so every test already runs without Atrium.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-010: How is openapi.json exported deterministically?
- Choice: A workbench-only command `workbench:openapi {path}` points the default connection at in-memory SQLite, runs the package and workbench migrations, and calls `scramble:export`. The Scramble config (`api_path` polycart, server `/polycart`, version 2.0.0) lives in the workbench, and `dedoc/scramble` is require-dev only.
- Rationale: Scramble may read model columns from the database. A fresh in-memory database, plus a fixed server URL and version, makes the export independent of the developer's workbench database and APP_URL, so the staleness test is reliable.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-011: How is the client's staleness checked, and how is the client shipped?
- Choice: The `generate` script writes `client/openapi.sha256`. A Pest test compares it with the SHA-256 of openapi.json, and a Node CI job regenerates and runs `git diff --exit-code` plus `tsc --noEmit`. The client ships as TypeScript source with no build step, and `/client` is export-ignored from the Composer dist.
- Rationale: The PHP suite and CI's PHP jobs have no Node. The hash catches "openapi.json changed but the client wasn't regenerated" without Node, and the CI job catches hand edits. Atrium's Nuxt layer compiles TS itself, and PHP installs don't need the Node folder.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-012: Do the workbench seeder's 'atrium' sources stay?
- Choice: No. They become 'http'.
- Rationale: AC 11 scans workbench/ for "atrium", and the demo data should name a surface that still exists.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-013: Does the Keystone constraint change?
- Choice: It stays `refactor-circus/keystone: ^0.1`.
- Rationale: Polycart simply stops calling `registerAtriumPlugin()`, so it works against Keystone with or without the hook. If Keystone's spec releases as v1.0.0, the bump is a follow-up.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-014: Do any package-to-package references become their own specs?
- Choice: No new specs. Everything Atrium-side (pages, widgets, search source, operators, `PolycartSupportFeature`, `atrium.integrations.polycart.*`) is already owned by Atrium's v2 spec, and the Keystone hook removal by Keystone's spec.
- Rationale: The inventory found nothing outside those two specs.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-015: Was spec review run?
- Choice: No. The spec was set to `approved` straight after write-spec.
- Rationale: The user asked to skip spec review and proceed without stopping.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-016: How does the workbench sign its demo user in on the JSON API?
- Choice: WorkbenchServiceProvider sets polycart.routes.middleware to ['web'] (workbench only), so SignInWorkbenchUser signs the demo user in on API requests; package default stays ['api'].
- Rationale: Recommended option.
- Date: 2026-11-23
- Raised by: factory build; default chosen because the user asked to proceed without stopping

## D-017: Which version does the suite release under?
- Choice: Every suite package releases as v2.0.0 to match: Keystone, Atrium, Cortex, Impex, Keen, PennantPlus, Polycart, Roster and Showroom. Every `refactor-circus/*` constraint (require and require-dev) is `^2.0`, and every repo aliases `dev-main` to `2.0.x-dev`. Foundry and Guardian stay on Keystone `^0.1` until they migrate.
- Rationale: The user said "i'd like everything release as v2 to match". This supersedes any earlier choice to keep `^0.1` constraints or release Keystone as v0.2.0.
- Date: 2026-11-23
- Raised by: user

## D-018: Who owns Polycart's Atrium front end?
- Choice: Polycart does. Its Atrium module (the Vue pages, widgets and search source Atrium phase 1 staged in `ui/modules/polycart`) moves into `atrium/`, built with the Atrium SDK's Vite preset into the committed `dist/atrium/` and declared in `composer.json`'s `extra.atrium`; Atrium injects the script into its Blade page. The module types its calls with the package's own `client/src/schema.d.ts` (no vendored copy). Polycart still has no PHP dependency on Atrium: no `RefactorCircus\Atrium` class, no require/suggest; `NoAtriumTest` is narrowed to allow only the `extra.atrium` block, the `/atrium` export-ignore and the `ui` workflow. `tests/Feature/AtriumModuleTest.php` checks the declaration and the build's source hash; the CI `ui` job rebuilds and diffs. Polycart still ships no PHP UI.
- Rationale: The user said "move the vue files for each module back into their respective modules. modules need to register their js. js should be injected into the blade file. modules are responsible for their own frontend and cjs clients from now on".
- Date: 2026-11-24
- Raised by: user

## D-019: Who declares Polycart's Atrium integration?
- Choice: Polycart does, in the static manifest `atrium/src/integration.json` (Atrium's D-090, commit 009b5bc): the `polycart.atrium` feature, the Carts and Cart types navigation items, the two widgets, the carts search source and the three screens, with no settings panel, hooks or `$config` values. The SDK preset validates it and writes `dist/atrium/integration.json`, named by `extra.atrium.integration`; the SDK pin moves to 009b5bc. `atrium/test/integration.test.ts` checks the manifest with `expectIntegrationManifest()` (replacing Atrium's PHP-written fixture) and that the module registers every screen, widget and search source it declares; `AtriumModuleTest` checks the declaration and the built copy. Still no PHP names Atrium.
- Rationale: The user said "move the php declarations": each package owns its Atrium integration declaration, which Atrium used to hold in PHP.
- Date: 2026-11-24
- Raised by: user

## D-020: Is Polycart's Atrium module built from Nuxt UI?
- Choice: Yes, as Atrium's own components are (Atrium D-091). Every page and widget under `atrium/src` composes Nuxt UI and the SDK: the carts, cart types and cart lines tables are `UTable`s (TanStack column defs, cell slots, numeric columns right-aligned through `meta.class`, the empty state a `UEmpty` in the `empty` slot), the filter bar and every cart action a `UForm` with `UFormField`s, the type, move, convert and visibility pickers `USelect`s (the type filter's "All types" is the item `*`, since a select item cannot be empty), a line's quantity a `UInputNumber`, "keep the original" a `UCheckbox`, the cart's heading a `UPageHeader`, its figures `AtriumStat`s, not-found and load failures `AtriumState`s, the widgets' links `ULink` / `UButton to`. Behaviour, routes, API calls and strings are unchanged; the row test ids (`polycart-cart-row`, `polycart-type`, `polycart-line`) give way to the table's (`polycart-carts-table`, `polycart-types-table`, `polycart-lines`), whose data rows the tests read. `atrium/test/nuxt-ui-guard.test.ts` fails on a raw `<button>`, `<input>`, `<select>`, `<textarea>`, `<table>`, `<details>` or `<a href>` in any `atrium/src` `.vue` file, as Atrium's guard does. The SDK pin moves to f6d6f83.
- Rationale: The user asked to "convert raw html": every Atrium UI is built on Nuxt UI 4, and the modules were the last to render raw HTML.
- Date: 2026-11-24
- Raised by: user
