# Decisions

Earlier D-ids are recorded in shape.md.

## D-034: Suite: proving the package runs without Atrium, and refusing an Atrium too old for the screen registry
- Choice: Prove it both ways: a WithoutAtrium suite inside `composer test`, and a CI lane with Atrium uninstalled. Every package that renders Atrium screens adds a composer `conflict` on refactor-circus/atrium versions older than the screen-extensions registry (Atrium PR #16).
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user, cross-spec decision (2026-10-29)

## D-035: Suite: malformed integration config entries (screen maps, slots) at boot
- Choice: Throw InvalidArgumentException in local and testing. In production, report once, skip the entry and keep booting (same rule as Atrium D-022).
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user, cross-spec decision (2026-10-29)

## D-036: How should `permission()` extensions behave when `polycart.authorization` is off, and for operators on Polycart's own models (Q1)?
- Choice: Keep Polycart's `ScreenAccess::allows()` as the single rule, so any `permission()` extension shows to operators and when authorization is off. Document this, and registrants use `authorize()` for an independent check.
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-037: Should `polycart.types` offer the `row.actions` slot through a `CartTypeRow` stand-in model, or declare the screen without that slot (Q2)? The stand-in adds a new class in `src/Atrium/Data`, a module-boundary choice.
- Choice: Add the `CartTypeRow` stand-in, following Atrium's `SettingsPanelRow` precedent (D-031, D-033), so every list screen offers the standard row slot.
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-038: How is Polycart's dependency on the unmerged Atrium and Keen changes handled in `composer.json` until the group merges?
- Choice: Pin the Atrium and Keen dev constraints to the PR branches or dev-main, and keep `require` unchanged.
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-039: Q1: When `polycart.authorization` is off, and for operators, should `permission()` extensions go through Polycart's `ScreenAccess::allows()` (which answers true) or ask the Gate directly?
- Choice: Keep `ScreenAccess::allows()` for every `permission()`, document it, and tell registrants to use `authorize()` for an independent check
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-040: Q2: How should `polycart.types` offer the `row.actions` slot, given that a cart type is not a model?
- Choice: Wrap each row in the `CartTypeRow` stand-in, as Atrium's `SettingsPanelRow` does (D-033)
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-041: How should the minimum Atrium version for the screen-extensions registry be enforced, given that Atrium stays suggested?
- Choice: A `conflict` entry in `composer.json` for `refactor-circus/atrium` versions below the registry release
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-042: What should permission() on a polycart.types row section do, given that the access closure falls back to CartModel::class only when there is no record and CartTypeRow has no policy?
- Choice: Treat a CartTypeRow record as the CartModel class check, i.e. map it to CartModel::class in the closure
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-043: When ?tab= names an unknown or hidden tab, what must the pages do, so the test has one expected result?
- Choice: Leave it to Atrium's behaviour and do not test it in Polycart
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-044: permission() on a polycart.types row
- Choice: The criterion 2 example uses `permission('viewAny')`, and the test asserts it renders for a signed-in member and not for a guest.
- Rationale: Accepted the recommendation. `CartPolicy::view()` needs a cart instance, so a class-level 'view' check can't tell users apart.
- Date: 2026-10-30
- Raised by: build (group 1)

## D-045: The 'acme.extra' Gate case
- Choice: Reword the case to match policy behaviour. A type-defined or unknown ability goes through `CartPolicy`: the cart's owner sees the section and a stranger doesn't, with or without a Gate definition. Test that.
- Rationale: Accepted the recommendation. `CartPolicy::__call()` answers before `Gate::define()`.
- Date: 2026-10-30
- Raised by: build (group 1)
