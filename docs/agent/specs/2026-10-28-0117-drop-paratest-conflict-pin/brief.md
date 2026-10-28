Risk: low

## What changed
Dependencies only, in `composer.json`: the `pestphp/pest` requirement goes from `^5.3.1` to `^5.3.4`, and the `conflict` block that blocked `brianium/paratest >=7.26.1` is removed. Nothing else changed: no source, tests, config or docs. `composer.lock` is gitignored, so it is not in the diff.

## Why
The conflict pin was temporary. Removing it and requiring Pest `^5.3.4` (which brings `brianium/paratest ^7.27.4`) lines Polycart up with the rest of the suite (campaign #1, ticket #4; Keystone PR #8 did the same). Decisions: D-001 (ticket created as drafted), D-002 (spec approved).

## Look at
- `composer.json:30`: the Pest constraint bump to `^5.3.4`.
- `composer.json:158-162`: the `conflict` block is gone and the `repositories` array now closes the file (valid JSON, no trailing comma).

## Tested
- AC1 and AC2: checked in `composer.json` (no `conflict` key, Pest at `^5.3.4`).
- AC3: `composer show` reports paratest v7.27.4 installed. The lock file is gitignored, so it cannot be checked in the diff.
- AC4: `composer validate` passes.
- AC5: `composer test` passes (246 tests), plus `lint:check` and the ringside check.
- AC6: `composer exec pest -- --parallel` gives 246 passed.
- AC7: the diff touches only `composer.json`.

## Risks
`composer.json` is an architectural path; the factory still finished `approved` (gate green, no findings). Consumers are unaffected, since this only changes dev tooling. The `--with-dependencies` update may have moved other transitive dev packages, but the full suite passes. Nothing left open (nits: 0).
