## Round 1

Changes to `spec.md`, and why:

- Criterion 1: added Slots and undeclared-slot columns to the screen table and stated that `Screen::standard()` supplies the slot sets (scope and testability: the slot set was only in the shape addendum). Spelled out the assertions (`resolved` view data, active overview tab, panel testid with `?tab=`, per-slot sections, per-row sections) and added the unknown-or-refused `?tab=` case (200 on overview, no panel).
- Criterion 2: a `CartTypeRow` record is checked at type level (`ScreenAccess::allows($permission, CartModel::class)`) because it has no policy. Added tests for operators, authorization off, and a `permission('view')` row section on `polycart.types`. All follow Q1 option (a); Q1 and Q2 are untouched.
- Criterion 3: the unit test now checks key name and type, non-incrementing, no timestamps, unsaved, private set, and an arch expectation for final.
- Criterion 4: the zero-calls test runs over all three screens with a named refused user and counters on every slot.
- Criterion 5: placement assertions use `assertSeeInOrder`, an aside container testid and list cell checks, on all three screens.
- Criterion 6: defined the nothing-registered assertions; removed the PR-listing step from the criterion.
- Criterion 7: added a docs test for `audit-trail` and `historyNavItem`, a kept test that the history route and tool still work, and a pointer to Keen's spec for gating its History section.
- Criterion 8: the composer.json assertion is now explicit; `require` unchanged and dev constraints stay `dev-main` until the group merges; the arch rule was checked with a grep (no hits outside `src/Atrium`).
- Criterion 9: two ordered tests prove no registry leakage.
- Criterion 10: README assertion moved into the docs test; added an upgrade note for the minimum Atrium and Keen requirement.
- Out of scope: added three bullets (no registrations into other packages, no further screens, history route/tool/listeners untouched) and a no-`require`-bump line.
- Risks: merge grouping now names the dev constraints and the upgrade impact; added a performance risk with a query-budget test.

Also edited `shape.md`: its original Scope section carries a note that the two addenda and `spec.md` supersede it (audit subjects, `history` slot, `historyNavItem` out of scope and `Screen::make` are stale). No history slot or audit-subject text was added to `spec.md`.

Not changed: the open questions (Q1, Q2, how the minimum Atrium version is enforced, and the dependency handling in `composer.json`) stay open.
