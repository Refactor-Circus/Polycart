# Drop the paratest conflict pin — Shaping Notes

## Scope

Dependency housekeeping in `composer.json` only (ticket #4):

- Remove `"conflict": {"brianium/paratest": ">=7.26.1"}` (line 162); drop the `conflict` key if it ends up empty.
- Raise `pestphp/pest` from `^5.3.1` to `^5.3.4` (line 30), which itself requires `brianium/paratest ^7.27.4`.
- `composer update pestphp/pest brianium/paratest --with-dependencies`, then check the installed paratest is `^7.27.4`.
- `composer test` passes, and so does the Pest suite with `--parallel`.

Out of scope: anything else in the package, other dependency bumps, source or test changes.

## Decisions

- D-001: ticket created as drafted (GitHub #4).

## Context

- Part of campaign #1 "Drop the paratest conflict pin" across Atrium, Cortex, Impex, PennantPlus, Polycart, Roster and Showroom. Keystone did the same in its PR #8.
- No product docs apply: this is not a feature.
- No visuals: no UI surface.
- If the update pulls other packages that break the suite, stop and raise it rather than widening scope.

## Standards

None apply. The standards index covers code (actions, models, HTTP, testing layout); this change touches no code or tests. `testing/arch-tests` still runs inside `composer test` but is not changed.
