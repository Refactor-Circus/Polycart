# Drop the paratest conflict pin

## Problem

`composer.json` carries a temporary `"conflict": {"brianium/paratest": ">=7.26.1"}` entry that holds paratest back. `pestphp/pest ^5.3.4` requires `brianium/paratest ^7.27.4`, so the pin is no longer needed.

## Desired outcome

- Remove the `conflict` entry from `composer.json` (drop the `conflict` key if it is then empty).
- Require `pestphp/pest ^5.3.4`.
- Run `composer update` for pest and paratest.
- The full test suite passes, including `--parallel`.

Keystone did the same in its PR #8. Part of campaign #1, "Drop the paratest conflict pin", across Atrium, Cortex, Impex, PennantPlus, Polycart, Roster and Showroom.

## Acceptance hints

- `composer.json` has no `brianium/paratest` conflict and requires `pestphp/pest ^5.3.4`.
- `composer.lock` / installed paratest is `^7.27.4`.
- `composer test` and the suite with `--parallel` pass.

## Out of scope

Anything else in this package.
