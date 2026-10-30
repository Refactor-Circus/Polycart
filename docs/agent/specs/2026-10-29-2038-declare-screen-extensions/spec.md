# Declare screen extensions (Polycart)

## Goal

Polycart's three Atrium pages declare themselves on Atrium's screen extensions registry (Atrium spec `2026-10-29-2037-screen-extensions-registry`, PR #16), so other packages, Keen's History among them, add tabs and sections without Polycart knowing they exist. Polycart drops its inline audit trails and History nav item, refuses an Atrium older than the registry, and proves it runs without Atrium.

## Acceptance criteria

1. **Screens declared.** Each controller view builds its screen through `Screens` (Design), resolves it with `ScreenExtensions::resolve($screen, $request)` after its own `authorizeScreen()` check, and passes the result to the view as `resolved`. None has built-in tabs, so each gets the synthetic `overview` tab as its default.

   | Key | Route name | Kind | Record | Row record (`row.actions`) |
   |---|---|---|---|---|
   | `polycart.carts` | `atrium.polycart.carts.index` | list | none | `CartModel` |
   | `polycart.cart` | `atrium.polycart.carts.show` | detail | `CartModel` | |
   | `polycart.types` | `atrium.polycart.types.index` | list | none | `CartTypeRow` (criterion 3) |

   Slots come from `Screen::standard()`: list = `header.actions`, `before`, `after`, `row.actions`; detail = `header.actions`, `before`, `aside`, `after`. Proven by `tests/Feature/Ui/ScreenExtensionsTest.php`, a dataset over the three screens: it registers a tab and a section in every declared slot and asserts `assertViewHas('resolved')`, `overview` active without `?tab=`, the registered tab's panel with `?tab={key}`, each section once in its slot (`data-testid="section-{key}"`), a section on an undeclared slot absent, and on list screens with two rows, the row section once per row with each row as `$record`. Unknown or hidden `?tab=` follows Atrium and is not tested here (D-043).
2. **Access closure.** Every screen gets `access(fn (string $permission, Request $request, ?Model $record): bool => ScreenAccess::allows($permission, $record instanceof CartTypeRow || $record === null ? CartModel::class : $record))`, using Polycart's own `Atrium\ScreenAccess`, operators included (D-036, D-042). Feature tests with `polycart.authorization` on: a `permission('view')` section on `polycart.cart` renders for the cart's member and not a stranger; a `permission('acme.extra')` section renders only once the Gate defines it; a `permission('view')` row section on `polycart.types` renders for a member and not a stranger. Further: an operator who is not a member sees the `polycart.cart` section, and with authorization off it renders for anyone.
3. **Cart type rows (D-037).** Each `polycart.types` row is wrapped in `RefactorCircus\Polycart\Atrium\Data\CartTypeRow`, following Atrium's `SettingsPanelRow`: a final, unsaved `Model` with string primary key `key` (the type's `key()`), no timestamps, no table, `public private(set) CartType $type`, built with `CartTypeRow::for(CartType $type)`. It lives outside `Domains/*/Models`, so the existing arch tests pass. A unit test asserts `for()` sets key and type, `getKeyType() === 'string'`, `getIncrementing() === false`, `timestamps === false`, `exists === false`, the class is final, and assigning `->type` from outside throws.
4. **Own authorization first.** A dataset over the three screens, with a user `authorizeScreen()` refuses and counting closures on every slot, asserts 403 and zero calls.
5. **Layout.** `header.actions` in `x-atrium::page-header`'s `actions` slot; `x-atrium::screen-tabs` under the header; with a registered tab open, `x-atrium::screen-tab-panel` replaces the body and only `header.actions` stays; otherwise `before`, the body, `aside` (cart page, in its side column) and `after`. `row.actions` sits in a trailing table cell under an empty header cell. Proven by `assertSeeInOrder` on testids over the three screens, plus the side-column and trailing-cell checks.
6. **Nothing registered, nothing changes.** With nothing registered, a dataset over the three screens asserts no tab-bar or `section-` testid and the core content still shows. A query-budget test in `tests/Feature/PerformanceTest.php` renders the carts index with N rows and a `permission()` row section and asserts the budget. Existing tests change only where criterion 7 forces it; each is listed in the PR by file and test name.
7. **Audit trails and History nav item removed.** `<x-atrium::audit-trail>` leaves `ui/carts/index` and `ui/carts/show`; `CartUiController::index` no longer computes `showHistory`; `PolycartPlugin::navigation()` no longer calls `historyNavItem()`. A feature test asserts no file under `resources/views` contains `x-atrium::audit` and no navigation item's URL points at `history`. Deleted: `tests/Feature/HistoryNavigationTest.php`, and in `tests/Keen/HistoryTest.php` the tests `lists a cart's history on its dashboard screen` and `lists Polycart's history on the carts screen`. The history route, MCP tool and audit listeners stay, with a test that they still work with Keen installed.
8. **Composer (D-034, D-038, D-041).** `refactor-circus/atrium` stays out of `require` (Keystone is the only `refactor-circus/*` there) and stays in `suggest`; `conflict` gains `refactor-circus/atrium` below the first Atrium release with the screen registry; the Atrium and Keen dev constraints stay `dev-main` until the group merges. A feature test reads `composer.json` and asserts all of this. `tests/ArchTest.php` gains `arch('code outside src/Atrium uses no Atrium class')->expect('RefactorCircus\Polycart')->ignoring('RefactorCircus\Polycart\Atrium')->not->toUse('RefactorCircus\Atrium')`, and it passes.
9. **Without-Atrium proof (D-034).** A `WithoutAtrium` suite (`phpunit.without-atrium.xml`, bootstrap `tests/WithoutAtrium/bootstrap.php` removing the `RefactorCircus\Atrium\` PSR-4 prefix, a test case registering no Atrium provider) asserts: no Atrium class loads; the provider boots; no `atrium.` route exists; `polycart.carts.index` answers; the MCP server's `tools/list` succeeds. A `test:without-atrium` composer script runs it, and `composer test` calls it after `@test:unit`. A `without-atrium` job in `.github/workflows/tests.yml` runs `composer remove --dev refactor-circus/atrium --no-update` (plus any dev package that requires Atrium), `composer update`, then `composer test:without-atrium`.
10. **Test isolation.** `tests/TestCase.php` uses `RefactorCircus\Atrium\Testing\ResetsScreenExtensions`. Two ordered tests assert it: the first registers an extension, the second finds the registry empty.
11. **Docs.** The Boost skill (`resources/boost/skills/polycart-development/SKILL.md`), `docs/sources-and-history.md`, `CLAUDE.md` and the README drop the audit-trail text, list the three screen keys and slots, say history comes from Keen, and say `permission()` extensions follow Polycart's `ScreenAccess` (shown to operators and when authorization is off; registrants use `authorize()` for their own check). The README and changelog note that the cart history UI needs an Atrium with the registry and Keen's History section. A docs feature test asserts the README names the three keys and that no file in `src`, `resources`, `docs`, README or CLAUDE.md contains `audit-trail` or `historyNavItem` (excluding `docs/agent/specs`).
12. **Gate.** `composer test` passes, including `StylesTest` and the WithoutAtrium suite. Polycart has no browser suite.

## Design

- **`RefactorCircus\Polycart\Atrium\Support\Screens`** (final, static): `detail(string $key, Model $record): Screen` and `list(string $key): Screen`, each `Screen::standard(...)->record(...)->access(...)` with the criterion 2 closure.
- **`Atrium\Data\CartTypeRow`**, per criterion 3. `CartTypeUiController` passes `rows` (`array<int, CartTypeRow>`) next to `types`.
- Views follow Atrium's `settings/index` layout. History comes from Keen's wildcard registration (default slot `after`). Polycart registers nothing into other screens (D-001).
- No config, routes, migrations, events or MCP changes, so the malformed-config rule (D-035) has nothing to apply to here.

## Out of scope

- Section B: the audit listeners gated on `Package::audits()`.
- Registering extensions into other packages' screens; screens beyond the three.
- Keen's History section and nav items, including hiding History from users without the history ability.

## Risks

- **Merge grouping.** The removals are forced by Atrium PR #16, so this merges with Atrium and Keen as a group. A consumer without Keen's History section loses the cart history UI; the upgrade note says so.
- **Conflict version.** Atrium has no tags yet; the `conflict` constraint is set from the first Atrium release carrying the registry when the group merges.
- **Performance.** `row.actions` runs the access closure once per row; if the budget in criterion 6 fails, load memberships once or reuse the `ScopeTree` scoped binding.

## Changelog

- Applied D-034 (WithoutAtrium suite plus CI lane, `conflict`), D-035 (noted: no integration config), D-036/D-039 (`permission()` through `ScreenAccess`, documented), D-037/D-040 (`CartTypeRow`), D-038 (`require` unchanged, dev constraints `dev-main`), D-041 (`conflict` entry), D-042 (`CartTypeRow` checks as `CartModel::class`), D-043 (unknown `?tab=` not tested).
- D-044: the types-row example is `permission('viewAny')` (member sees it, guest doesn't).
- D-045: unknown and type-defined abilities follow CartPolicy (owner sees, stranger doesn't), regardless of Gate::define.
