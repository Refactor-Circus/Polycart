# Build

## Group 2

Atrium optional (criteria 8, 9). Commit `7ff40f7` on `ring-master/2026-10-29-2038-declare-screen-extensions--g2`.

### Files

- `composer.json`: `conflict` gains `"refactor-circus/atrium": "<1.0"`; new `test:without-atrium` script (`vendor/bin/pest --configuration=phpunit.without-atrium.xml --test-directory=tests/WithoutAtrium`), called by `composer test` right after `@test:unit`. `require`, `suggest` and the `dev-main` dev constraints are unchanged. The same changes were mirrored by hand into the git-ignored `composer.local.json` (not regenerated).
- `tests/ArchTest.php`: `arch('code outside src/Atrium uses no Atrium class')`.
- `tests/Feature/ComposerTest.php`: reads `composer.json` (Keystone the only `refactor-circus/*` in `require`; Atrium suggested, not required; `conflict` on Atrium with a `<` bound; Atrium and Keen dev constraints `dev-main`; `test:without-atrium` follows `@test:unit` in `test`), `phpunit.without-atrium.xml` (bootstrap and suite directory) and the CI workflow (the `without-atrium` job's remove and run steps).
- `phpunit.without-atrium.xml`: one `WithoutAtrium` suite over `tests/WithoutAtrium`, bootstrap `tests/WithoutAtrium/bootstrap.php`, its own cache directory.
- `tests/WithoutAtrium/bootstrap.php`: requires the Composer autoloader, then sets the `RefactorCircus\Atrium\` PSR-4 prefix to no directories on every registered loader.
- `tests/WithoutAtrium/TestCase.php`: Testbench with `McpServiceProvider` and `PolycartServiceProvider` only; does not extend the main `TestCase` (group 1 adds Atrium's `ResetsScreenExtensions` trait to it).
- `tests/WithoutAtrium/Pest.php`: binds that test case. The script's `--test-directory=tests/WithoutAtrium` makes Pest load this file instead of `tests/Pest.php`, which names test cases that load Atrium.
- `tests/WithoutAtrium/WithoutAtriumTest.php`: no Atrium class can load; the provider boots and the package registers; no `atrium.` route while `polycart.carts.index` exists; the JSON API lists a cart; `tools/list` on `PolycartServer` returns the tools; no `RefactorCircus\Atrium\` class, interface or trait is declared after any of it.
- `.github/workflows/tests.yml`: `without-atrium` job: `composer remove --dev refactor-circus/atrium refactor-circus/cortex --no-update` (Cortex requires Atrium), `composer update --no-scripts` (the workbench providers in `testbench.yaml` name Atrium), then `composer test:without-atrium`.
- `.gitattributes` (outside the group's file list): `phpunit.without-atrium.xml` is `export-ignore`, like `phpunit.xml.dist`.

### Tests

Not run here, as instructed; the gate runs them. `vendor/bin/pint --test` passes on the new and changed test files, and `composer validate` passes. The WithoutAtrium suite does not run under `vendor/bin/pest` (it is not in `phpunit.xml.dist`); it runs through `composer test:without-atrium`, which `composer test` calls.

### Deviations

- The `conflict` bound `<1.0` is a placeholder. Atrium has no tags yet, and the spec's Risks section sets the real bound from the first Atrium release carrying the registry when the group merges. `<1.0` matches no current install (`dev-main` is not compared with `<`).

## Group 1 (salvaged)

Screen declarations (criteria 1-7, 10). Commit `0ce5cdc` on `ring-master/2026-10-29-2038-declare-screen-extensions--g1`.

### What was found

The build agent ended without a result. There was no `index.lock` and no merge or rebase in progress. The worktree held uncommitted work for every group 1 path: the `HistoryNavigationTest.php` deletion was staged and everything else was unstaged or untracked. The work was complete apart from two criterion 2 cases, which the agent left as Pest `todo()`s citing decisions it never wrote down (see below). `AGENTS.md` was also modified (a Ring Master sync line). It is not a group 1 file, so it was left uncommitted.

### Committed

- `src/Atrium/Support/Screens.php`: final, static `detail()`/`list()` on `Screen::standard()` with the criterion 2 access closure (a `CartTypeRow` or no record is asked about as `CartModel::class`, D-042).
- `src/Atrium/Data/CartTypeRow.php`: final unsaved `Model`, string key `key`, no timestamps, `public private(set) CartType $type`, `for()`.
- `src/Atrium/Http/Controllers/CartUiController.php`: `index` and `show` resolve `polycart.carts` / `polycart.cart` after `authorizeScreen()` and pass `resolved`. `showHistory` and its Keystone imports are gone. Listed carts get relationship autoloading, so a `permission()` row section stays within the query budget.
- `src/Atrium/Http/Controllers/CartTypeUiController.php`: resolves `polycart.types` and passes `resolved`, `types` and `rows` (`CartTypeRow::for(...)` per type).
- `src/Atrium/PolycartPlugin.php`: `historyNavItem()` removed from `navigation()`.
- `resources/views/ui/{carts/index,carts/show,types/index}.blade.php`: `header.actions` in the page header's actions slot, then `screen-tabs`. A registered tab, when open, replaces the body with `screen-tab-panel`. Otherwise the order is `before`, body, `aside` (cart page side column, `data-testid="screen-aside"`), `after`. `row.actions` goes in a trailing cell (`row-actions`) under an empty heading (`row-actions-heading`), shown only when some row has a section. Both `x-atrium::audit-trail` tags are removed.
- `tests/TestCase.php`: `use ResetsScreenExtensions`, and registers the `polycart-tests` view namespace for the fixture.
- `tests/Fixtures/views/marker.blade.php` (**outside the group's file list**): the fixture view the screen tests register as tabs and sections. It prints its marker, screen key and record key. It was needed because Atrium requires a view or component on every extension.
- `tests/Unit/CartTypeRowTest.php`, `tests/Feature/Ui/ScreenExtensionsTest.php`, `tests/Feature/Ui/ScreenIsolationTest.php`, and a `permission()` row-section query-budget test in `tests/Feature/PerformanceTest.php`.
- Deleted `tests/Feature/HistoryNavigationTest.php`. In `tests/Keen/HistoryTest.php`, deleted `lists a cart's history on its dashboard screen` and `lists Polycart's history on the carts screen`, and replaced them with `keeps the history route, tool and listeners with Keen installed, beside screens that leave history to Keen`. List these in the PR (criterion 6).

`vendor/bin/pint --test` passes on every changed PHP file. The suite was not run here, as instructed; the gate runs it.

### Left

- 1.1, two criterion 2 cases, are `todo()` in `ScreenExtensionsTest.php`. Both need a decision, because neither can pass as worded:
  - "A `permission('view')` row section on `polycart.types` renders for a member and not a stranger." D-042 asks about a `CartTypeRow` as `CartModel::class`. `CartPolicy::view()` needs a cart instance, so a class-level `view` cannot tell a member from a stranger.
  - "A `permission('acme.extra')` section renders only once the Gate defines it." `CartPolicy::__call()` answers every ability on `CartModel`, and Laravel's Gate prefers a callable policy method over a `Gate::define()`. So the definition is never consulted: the owner always sees the section and anyone else never does.
- 1.6: run `vendor/bin/pest` (the gate).

## Group 1

Screen declarations (criteria 1-7, 10), finishing the salvaged work. The salvaged commit `0ce5cdc` was not on the job branch, so it was brought in (cherry-picked, uncommitted) and committed here together with the two finished cases.

### Files

- Everything listed under "Group 1 (salvaged)" above, unchanged, including `tests/Fixtures/views/marker.blade.php` (**outside the group's file list**: Atrium requires a view on every extension).
- `tests/Feature/Ui/ScreenExtensionsTest.php`: the two `todo()` cases are replaced.
  - `shows a permission('viewAny') row section on polycart.types to a signed-in member and not to a guest` (D-044): signed in as a member, the types page renders the section once per row with each row as its record. A guest's view is resolved directly, because the page refuses a guest before resolving, and gets no row section, while the member's gets one.
  - `decides a type-defined or unknown permission on a cart through CartPolicy` (D-045): a dataset over a type-defined ability (`checkout` on an `order` cart, from the `OrderCart` fixture's roles) and an unknown one (`acme.extra` on a `cart` cart), each without a Gate definition and with a `Gate::define()` that grants everyone. The cart's owner sees the `before` section on the cart page. The stranger's view, resolved directly, has none, even when the Gate definition grants everyone.

### Tests

The suite was not run here, as instructed; the gate runs it. `vendor/bin/pint --test` passes on `ScreenExtensionsTest.php`.

### Deviations

- `tests/Fixtures/views/marker.blade.php` is outside the group's file list (see above).
- `AGENTS.md` carries an uncommitted Ring Master sync line that is not part of this group. It was left as it was.

## Group 3

Docs and gate (criteria 11, 12).

### Files

- `tests/Feature/DocsTest.php` (new): the README names `polycart.carts`, `polycart.cart` and `polycart.types`; a dataset asserts the README, CLAUDE.md, `docs/sources-and-history.md` and the Boost skill each list all three keys; no file under `src`, `resources` or `docs` (excluding `docs/agent/specs`), nor README or CLAUDE.md, contains `audit-trail` or `historyNavItem` (offenders are reported by path and needle).
- `README.md`: Atrium is optional and an Atrium older than the registry is refused; the History bullet says history comes from Keen's History section through the registry and that the cart history UI needs an Atrium with the registry and Keen's History section; the history row is gone from the Permissions table; new "Screen extensions" subsection with the keys, kinds, records and slots table, a registration example, the `permission()`-through-`ScreenAccess` rule (operators, authorization off, `authorize()` for an own check) and the own-authorization-first rule; "read history" points at Keen's History section.
- `CHANGELOG.md` (Unreleased): a Breaking entry with the upgrade note (cart history UI needs an Atrium with the registry and Keen's History section; `conflict` on old Atrium). In Added, the unreleased Audit log nav entry is replaced by the screen extensions entry (keys, slots, `CartTypeRow`, `permission()` rule) and the WithoutAtrium suite; the unreleased inline-history entry now points at Keen's History section.
- `CLAUDE.md`: a symlink to `AGENTS.md`, so `AGENTS.md` was edited. The audit-trail sentence is replaced (screens draw no history, no History nav item, Keen attaches through the registry), and two bullets describe the screens (keys, slots, `Screens`, `resolved`, the access closure, `ResetsScreenExtensions`) and Atrium being optional (arch rule, WithoutAtrium suite). Only these hunks were staged; the uncommitted Ring Master sync line in `AGENTS.md` stays uncommitted.
- `resources/boost/skills/polycart-development/SKILL.md`: audit-trail text removed; history from Keen through the registry; the three keys and slots with how to register; `permission()` follows `ScreenAccess`.
- `docs/sources-and-history.md`: "In the Atrium dashboard" now says history comes from Keen's History section on the registry, the upgrade need, a keys and slots table, and the `permission()` rule.
- `composer.json` and `tests/Feature/ComposerTest.php` (**outside the group's file list**, as the task instructed): the Atrium `conflict` constraint is now exactly `<0.1.0` (group 2 wrote `<1.0`), and the test asserts that exact value. `composer.local.json` was not regenerated; in this worktree it has no `conflict` section, so it was left alone. `composer validate` passes.

### Tests

The suite was not run here, as instructed; the gate runs `composer test` and `vendor/bin/pest` (3.3). `vendor/bin/pint --test` passes on `DocsTest.php` and `ComposerTest.php`, and a grep finds `audit-trail` and `historyNavItem` only under `docs/agent/specs`.

### Deviations

- `composer.json` and `tests/Feature/ComposerTest.php` are outside the group's file list (conflict constraint fix, per the task).
- `CLAUDE.md` is a symlink; the change is in `AGENTS.md`.
