# Drop the paratest conflict pin

Ticket: [#4](https://github.com/Refactor-Circus/Polycart/issues/4)

## Goal

Remove the temporary `brianium/paratest` conflict pin from `composer.json` and require `pestphp/pest ^5.3.4`, which brings in `brianium/paratest ^7.27.4`, so Polycart matches the rest of the suite (campaign #1; Keystone PR #8 did the same).

## Acceptance criteria

1. `composer.json` has no `conflict` entry for `brianium/paratest`; the `conflict` key is removed if it is then empty.
2. `composer.json` requires `pestphp/pest` as `^5.3.4`.
3. `composer.lock` locks `brianium/paratest` at `7.27.4` or newer within `^7.27.4`, and `pestphp/pest` at `5.3.4` or newer.
4. `composer validate` passes.
5. `composer test` passes.
6. The Pest suite passes when run with `--parallel`.
7. The diff touches only `composer.json` and `composer.lock` (plus this spec folder).

## Design

- Edit `composer.json:30` (`^5.3.1` to `^5.3.4`) and delete the `conflict` block at `composer.json:162-164`.
- Run `composer update pestphp/pest brianium/paratest --with-dependencies`.
- No source, test, config or docs changes. No standards apply (see `standards.md`).

## Out of scope

Any other dependency bump, code, tests, or package changes.

## Risks and open questions

- The update may pull in other transitive changes through `--with-dependencies`; if one breaks the suite, stop and raise it rather than widening scope.
- `composer.json` is listed under `escalation.architectural_paths` in the Ring Master config, so the finished build will be escalated for the user's approval rather than opened as a PR unattended. Expected, not a defect.
- `--parallel` needs paratest installed; AC 6 is only checkable after AC 3 holds.
- None open.
