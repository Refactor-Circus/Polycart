# Declare screen extensions (Polycart)

## Goal

Polycart's three Atrium pages declare themselves on Atrium's screen extensions registry (Atrium spec `2026-10-29-2037-screen-extensions-registry`, PR #16), so other packages, Keen's History among them, add tabs and sections without Polycart knowing they exist. Polycart drops its inline audit trails and History nav item, and Atrium stays a suggestion.

## Acceptance criteria

1. **Screens declared.** Each controller view builds `Screen::standard($key, $kind)` (`RefactorCircus\Atrium\Domains\Screens\Data\Screen`), resolves it with `ScreenExtensions::resolve($screen, $request)` after its own `authorizeScreen()` check, and passes the result to the view as `resolved`. None has built-in tabs, so each gets the synthetic `overview` tab as its default.

   | Key | Route name | Kind | Record | Row record (`row.actions`) |
   |---|---|---|---|---|
   | `polycart.carts` | `atrium.polycart.carts.index` | list | none | `CartModel` |
   | `polycart.cart` | `atrium.polycart.carts.show` | detail | `CartModel` | |
   | `polycart.types` | `atrium.polycart.types.index` | list | none | `Atrium\Data\CartTypeRow` (criterion 3) |

   Proven by `tests/Feature/Ui/ScreenExtensionsTest.php`: a dataset over the three screens registers a tab and a section in every declared slot, and asserts each section renders once in its slot (`data-testid="section-{key}"`), a section on an undeclared slot does not render, and the tab opens through `?tab=`. On the two list screens, with two rows, the row section renders once per row and its `authorize` closure receives each row as `$record`.
2. **Access closure.** Every screen gets `access(fn (string $permission, Request $request, ?Model $record): bool => ScreenAccess::allows($permission, $record ?? CartModel::class))`, using Polycart's own `Atrium\ScreenAccess`, operators included. A feature test, with `polycart.authorization` on, asserts that a `permission('view')` section on `polycart.cart` renders for the cart's member and not for a stranger, and that a `permission('acme.extra')` section renders only once the Gate defines `acme.extra` for the user.
3. **Cart type rows.** A cart type is not a model, so each row of `polycart.types` is wrapped in `RefactorCircus\Polycart\Atrium\Data\CartTypeRow`, following Atrium's `SettingsPanelRow` (Atrium D-031, D-033). It is a final, unsaved `Model` with a string primary key `key` (the type's `key()`), no timestamps and no table, and it exposes `public private(set) CartType $type`, built with `CartTypeRow::for(CartType $type): self`. It lives outside `Domains/*/Models`, so it needs no policy and no lifecycle events, and the existing arch tests pass. A unit test asserts `for()` sets the key and the type.
4. **Own authorization first.** A user the page refuses gets 403 and no registered `authorize` closure or resolver runs: a feature test with counting closures on `polycart.cart` asserts zero calls.
5. **Layout.** Each view puts `header.actions` in `x-atrium::page-header`'s `actions` slot, `x-atrium::screen-tabs` under the header, and then either `x-atrium::screen-tab-panel` (registered tab open: nothing else but `header.actions`) or `before`, the page body, `aside` (cart page, in its side column) and `after`. `row.actions` goes in a trailing table cell with an empty header cell. A feature test on `polycart.cart` with a section in every slot asserts all render on the overview tab, and only `header.actions` and the panel render with a registered tab open.
6. **Nothing registered, nothing changes.** With nothing registered, no tab bar and no extra markup render, and the existing suites pass. Existing tests may change only where this spec forces it (criterion 7); each changed or deleted test is listed in the PR by file and test name.
7. **Audit trails and History nav item removed.** `<x-atrium::audit-trail>` leaves `ui/carts/index` and `ui/carts/show`; `CartUiController::index` no longer computes `showHistory` (Keystone `History`); `PolycartPlugin::navigation()` no longer calls `historyNavItem()`. A feature test asserts no file under `resources/views` contains `x-atrium::audit` and no navigation item's URL points at `history`. Forced test changes: `tests/Feature/HistoryNavigationTest.php` is deleted, and in `tests/Keen/HistoryTest.php` `lists a cart's history on its dashboard screen` and `lists Polycart's history on the carts screen` are deleted (Keen's spec covers its History section). The history route, MCP tool and audit listeners stay.
8. **Atrium stays suggested.** `refactor-circus/atrium` stays out of `require`; Keystone is the only `refactor-circus/*` package there. A feature test reads `composer.json` and asserts this. `tests/ArchTest.php` gains `arch('code outside src/Atrium uses no Atrium class')->expect('RefactorCircus\Polycart')->ignoring('RefactorCircus\Polycart\Atrium')->not->toUse('RefactorCircus\Atrium')`, and it passes.
9. **Test isolation.** `tests/TestCase.php` uses `RefactorCircus\Atrium\Testing\ResetsScreenExtensions`.
10. **Docs.** `resources/boost/skills/polycart-development/SKILL.md` (lines 85 and 88), `docs/sources-and-history.md:126`, `CLAUDE.md` (the audit-trail sentence) and the README drop the audit-trail text. They list the three screen keys and their slots, and say that history comes from Keen. Done-step: a grep for `audit-trail` and `historyNavItem` in `src`, `resources`, `docs`, README and CLAUDE.md is empty and listed in the PR.
11. **Gate.** `composer test` passes, including `StylesTest`. Polycart has no browser suite.

## Design

- **`RefactorCircus\Polycart\Atrium\Support\Screens`** (final, static): `detail(string $key, Model $record): Screen` and `list(string $key): Screen`, each `Screen::standard(…)->record(…)->access(…)` with the criterion 2 closure.
- **`Atrium\Data\CartTypeRow`**, per criterion 3. `CartTypeUiController` passes `rows` (`array<int, CartTypeRow>`) next to `types`.
- Views follow Atrium's `settings/index` layout. History comes from Keen's wildcard registration (default slot `after`). Polycart declares no audit subject and registers nothing into other screens (D-001).
- No config, routes, migrations, events or MCP changes.

## Out of scope

- Section B: the audit listeners gated on `Package::audits()`.
- Keen's History section and nav items.
- Making the whole suite run without Atrium (already a suggestion; no new CI lane).

## Risks and open questions

- **Merge grouping.** The removals are forced by Atrium PR #16, so this merges with Atrium and Keen as a group.
- **Q1: `permission()` with `polycart.authorization` off, and operators.** `ScreenAccess::allows()` answers `true` when authorization is off, and for operators on Polycart's own models. So any `permission()` extension shows to them, including another package's ability. Options: (a) keep it: it is Polycart's own rule for every control, documented, and registrants use `authorize()` for an independent check; (b) ask the Gate directly. Recommendation: (a).
- **Q2: the `CartTypeRow` stand-in.** It is settled here by Atrium's precedent (D-033). The alternative is to declare `polycart.types` without rendering `row.actions`. Recommendation: the stand-in, so every list screen offers the standard row slot.
