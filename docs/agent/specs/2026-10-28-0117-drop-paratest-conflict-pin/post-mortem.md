# Post-mortem: Drop the paratest conflict pin

## Summary

Ticket #4: remove the `brianium/paratest` conflict pin and require `pestphp/pest ^5.3.4`. The job was approved in round 1 with no red gate rounds and no review findings. One build commit (`41419dc`) changed `composer.json` only. The spec assumed `composer.lock` would be in the diff, but it is gitignored in this package. The build and gate both worked around that, and the spec was never corrected.

## Metrics

- Rounds: 1. Gate red rounds: 0. Findings: 0. Escalations: 0.
- Decisions: 2 (D-001 ticket draft, D-002 spec approval), both answered in RingMaster with no rationale given.
- Deviations: 2, both recorded in build.md.
- Gate result: `composer test` 246 passed, `lint:check` passed, `ringside check` passed, `pest --parallel` 246 passed.
- Human PR review comments: none.

## What went well

- The change was small and the scope was pinned by the out-of-scope section and AC 7.
- The implementer used `composer local:update` instead of plain `composer update`. The plain command would have replaced the sibling path symlinks in `vendor` (build.md, Deviations).
- The gate checked every AC by command, including `--parallel`, and not just by reading the diff (round-1/gate.md).
- The `--with-dependencies` risk was named in the spec. The side effects, `phpunit` 13.4.1 to 13.4.5 and `phpdoc-parser` 2.3.6 to 2.3.9, were recorded in build.md.

## What cost rounds

Nothing. The only cost was the spec being wrong in places, which the build absorbed as deviations.

- AC 3 and AC 7 name `composer.lock`, but `.gitignore:6` ignores it, so the lock change cannot be in the diff or the PR.
- Design step 2 and task 1.3 prescribed plain `composer update`. The worktree has no lock and its `vendor` is a local install, so that command was unsafe.
- build.md says `composer test` and `--parallel` were not run during the build and were left to the gate. There was no baseline result, so a pre-existing failure could not have been told apart from one the update caused.

## Root causes

- **spec_gap:** The spec never checked `.gitignore` or how this package installs dependencies. The result was an AC that cannot be met as written and a design command that would have broken the symlinked siblings. Evidence: spec.md AC 3 and AC 7, Design; build.md Deviations.
- **spec_gap:** The spec review did no repo-fact check. spec-review.md has no findings, and it says the review was done inline with no workflow because the change was small.
- **standards_gap:** Nothing tells specs that `composer.lock` and `composer.local.*` are gitignored in the Refactor Circus packages, or that dependency bumps must go through `composer local:update`. The package's CLAUDE.md does not mention it. The same trap is likely in sibling packages, since Keystone PR #8 made the same change.
- **tooling:** The gate checked the installed version (`composer show`) and not a tracked lock. The lock is local-only, so the PR does not record the resolved versions. CI resolves its own versions, and "7.27.4 or newer" is not enforced anywhere tracked. `^5.3.4` in `composer.json` does force paratest `^7.27.4` through pest, which limits the exposure.

## What human review caught

No human review comments were recorded. The PR stays open for approval because `composer.json` is an architectural path.

## Proposed follow-ups

- Add a spec-writing check: before naming `composer.lock` in an AC, run `git check-ignore composer.lock`. If the lock is ignored, write the AC against `composer.json` and the installed versions.
- Document the `composer local:update` dependency-bump flow, and the gitignored lock files, in Polycart's CLAUDE.md or in the shared standards.
- Have the build run `composer test` before and after for dependency changes, so a baseline exists.
- Have the gate fail when an AC names a file that is gitignored.
