Risk: high

## What changed
- Screens: new `src/Atrium/Support/Screens.php` builds the three Atrium pages (`polycart.carts`, `polycart.cart`, `polycart.types`) on Atrium's screen extensions registry, with an `access` closure that sends `permission()` through Polycart's `ScreenAccess`. Controllers resolve the screen after their own `authorizeScreen()` and pass `resolved` to the views.
- Types: new `Atrium/Data/CartTypeRow`, an unsaved stand-in model so cart type rows can take `row.actions` sections.
- Views: carts index, cart show and types index get header actions, a tab bar, tab panels and the standard slots. The inline audit trails and the History nav item (`PolycartPlugin`) are removed.
- Composer and CI: `conflict` on `refactor-circus/atrium` below `0.1.0`, a `test:without-atrium` script called from `composer test`, a `WithoutAtrium` suite and a `without-atrium` CI job. A new arch rule keeps Atrium classes inside `src/Atrium`.
- Docs: README, CHANGELOG, Boost skill, AGENTS.md and `docs/sources-and-history.md` updated.

## Why
Atrium PR #16 adds a screen extensions registry, so other packages (Keen's History among them) add tabs and sections without Polycart knowing them. That forces Polycart to declare its screens and drop its own audit trails and History nav item. Decisions that shaped it: D-034 (Atrium stays optional, proven by the WithoutAtrium suite and `conflict`), D-036/D-042 (`permission()` through Polycart's `ScreenAccess`, operators included), D-037 (`CartTypeRow`), D-038/D-041 (`require` unchanged, `conflict` added), D-044/D-045 (ability behaviour in tests).

## Look at
- `composer.json:47`: the `conflict` constraint is `<0.1.0`, a placeholder (Atrium has no tags yet).
- `src/Atrium/Support/Screens.php:38`: the access closure. Check how `CartTypeRow` and a null record map to `CartModel::class`.
- `src/Atrium/Data/CartTypeRow.php:1`: the final, unsaved model with a string key.
- `resources/views/ui/carts/show.blade.php:1`: the largest rewrite. Check header actions, tab panel replacing the body, and the `aside` slot.
- `.github/workflows/tests.yml:1`: the `without-atrium` job and `tests/WithoutAtrium/bootstrap.php`.

## Tested
Round 2 gate passed: phpstan 0 errors, pint clean, Pest 297/297, coverage 100%, WithoutAtrium 5/5.
- Criteria 1-6: `tests/Feature/Ui/ScreenExtensionsTest.php` (declaration, slots, access, 403 before extensions run, layout, nothing registered), `tests/Unit/CartTypeRowTest.php` and the budget in `tests/Feature/PerformanceTest.php`.
- Criterion 7: `ScreenExtensionsTest` ("leaves no audit trail in any view", "links no history page from its navigation") and `tests/Keen/HistoryTest.php`. `HistoryNavigationTest.php` and two Keen history tests were deleted as the spec lists.
- Criterion 8: `tests/Feature/ComposerTest.php` and the `tests/ArchTest.php` rule.
- Criterion 9: `tests/WithoutAtrium/WithoutAtriumTest.php`.
- Criterion 10: `tests/Feature/Ui/ScreenIsolationTest.php`.
- Criterion 11: `tests/Feature/DocsTest.php`.

## Risks
- Merge grouping: this must merge with the Atrium and Keen PRs. Alone, it loses the cart history UI, and the dev constraints stay `dev-main`.
- The `conflict` version is a placeholder until Atrium's first registry release. Update it when the group merges.
- A composer `conflict` and a new CI lane are dependency and CI changes. Check the lane's `composer remove --dev` step on a clean run.
- `row.actions` runs the access closure once per row. The query budget test covers it, but it is the first place to look if pages slow down.
- Nothing else is open (nits: 0).
