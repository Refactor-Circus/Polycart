Risk: high

## What changed
- Removed the Atrium UI: `src/Atrium/`, `resources/views/`, `lang/`, the `ui` and `atrium` config keys, the `polycart-views`/`polycart-lang` publish tags, and the Atrium and PennantPlus tests, fixtures and CI lane.
- Added `GET {prefix}/stats` and `GET {prefix}/search?q=` on `CartController`, plus the `cart-stats` MCP tool. Stats runs through the new `ShowCartStatsAction` with its events, `CartStats` and `CartStatsResource`. Search reuses `ListCartsAction`.
- `composer.json` drops `refactor-circus/atrium` and `refactor-circus/pennantplus` and adds `dedoc/scramble` (require-dev only) and a `client:generate` script.
- Added a committed `openapi.json`, exported by the workbench command `workbench:openapi`. Added the `client/` TypeScript package (`@refactor-circus/polycart-client` 2.0.0) with a CI `client` job.
- Rewrote the README, CHANGELOG (v2.0.0), CLAUDE.md/AGENTS.md, the Boost skill and `docs/*.md`.

## Why
Polycart v2.0.0 becomes a JSON API plus an MCP server with no UI of its own. Atrium v2 calls it through the OpenAPI document and the generated client. The dashboard data that Blade used to compute (counts by type, recent carts, search) therefore needed API endpoints.

Decisions that shaped it:
- D-001: `routes.enabled` stays off by default.
- D-002 and D-003: the stats payload and the search contract.
- D-005: `cart-stats` gets an MCP tool, so no parity exception is needed.
- D-007 and D-008: the views, lang and PennantPlus go too.
- D-010 and D-011: deterministic export and a hash-based staleness check.
- D-013: the Keystone constraint stays `^0.1`.
- D-016: the workbench uses `web` middleware so the demo user is signed in.

## Look at
- `composer.json`: dependency removals, the new require-dev, the scripts, and the removed repositories. This is a breaking dependency change.
- `config/polycart.php`: removed `ui` and `atrium` keys (a breaking config change), and the rewritten `routes` banner.
- `src/Domains/Cart/Actions/ShowCartStatsAction.php:43`: the grouped count query and the recent query, and whether the access scoping and query count hold.
- `src/Domains/Cart/Http/Requests/SearchCartsRequest.php:25`: the `q` rules derived from `ListCartsData` and the authorization.
- `workbench/app/Providers/WorkbenchServiceProvider.php:58`: how the `RESOURCES` map becomes the OpenAPI component schemas, because the `src/` controllers return `$request->persist()`.

## Tested
Gate round 1: PASS (`composer test`: PHPStan 0 errors, Pint, 100% type coverage, Pest 282/282).
- AC 1-3, 6: `tests/Feature/StatsTest.php`, `AccessTest.php`, `ApiTest.php`.
- AC 4-5: `tests/Feature/ApiTest.php`.
- AC 7: `tests/Feature/Mcp/McpTest.php`, `OutputSchemaTest.php`, and the parity rule in `ArchTest.php`.
- AC 8-10: `ServiceProviderTest.php`, `NoAtriumTest.php`, `DocsTest.php`.
- AC 11-14: `ArchTest.php`, `NoAtriumTest.php`, `ComposerTest.php`.
- AC 15-20: `ComposerTest.php`, `ArchTest.php`, `OpenApiTest.php` (fresh export equals `openapi.json`, stamp matches, the stats shape, the required `q`).
- AC 21-23: `DocsTest.php`.
- AC 24: the gate run.

## Risks
- Breaking for hosts: the Atrium UI, config keys, publish tags and translations are gone, and a host with published views or lang must delete them (the CHANGELOG says so).
- The API stays off by default, so a front end needs `polycart.routes.enabled` on, behind auth middleware.
- Search order changes from most recently updated (Atrium v1) to newest created.
- The client cannot be installed from a git subfolder with npm. Atrium's spec owns that choice.
- The Keystone constraint stays `^0.1`. If Keystone ships as v1.0.0, a follow-up bump is needed.
- Gate findings left: none (nits: 0).
