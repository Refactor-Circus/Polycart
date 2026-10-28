# Standards proposals (awaiting review)

From the post-mortem workflow run `wf_650f1617-099`, 2026-10-28. Not applied: each needs accept, edit or reject in `/ring-master-post-mortem 2026-10-28-0117-drop-paratest-conflict-pin`.

## 1. `architecture/dependencies` (global, architectural, amend)

Adds a **Lock files** section to the shared dependencies standard:

- A package is a library: `composer.lock` is gitignored and never committed. The constraints in `composer.json` are the contract; consumers and CI resolve their own versions.
- The local-development manifest (`composer.local.json` / `composer.local.lock`, generated from `composer.json`) is gitignored too. It may only change where suite packages come from, never their constraints.
- So a dependency change shows up in the diff as `composer.json` only. Specs and acceptance criteria name the `composer.json` constraint and the version that gets installed (`composer show {vendor}/{name}`), never a `composer.lock` entry or a lock-file diff.

```md
3. `composer.json` requires `pestphp/pest ^5.3.4`; after the update `composer show brianium/paratest` reports `7.27.4` or newer.
7. The diff touches only `composer.json` (plus the spec folder).
```

The reference-implementation paragraph also gets: "Every suite package gitignores `composer.lock`, `composer.local.json` and `composer.local.lock`; `bin/composer-local.php` generates the local manifest by putting a path repository for each sibling checkout ahead of the VCS repositories."

**Rationale:** AC 3 and AC 7 named the gitignored `composer.lock`, the spec review passed them, and the build had to deviate. Every suite package has the same lock-file policy.

**Evidence:** spec.md AC 3 and AC 7; build.md Deviations; spec-review.md Pass 1; `.gitignore` lines 6, 12 and 13; `bin/composer-local.php` docblock; Keystone, Keen, Atrium, Cortex, PennantPlus, Impex, Roster and Showroom all gitignore `composer.lock`.

## 2. `tooling/composer-updates` (project, tooling, add)

```md
# Composer updates

Change Polycart's dependencies through `composer local:update`, never a plain `composer update`, and record the versions you resolved in the build notes and the PR body, because no tracked file records them.

## Why

- `composer.lock`, `composer.local.json` and `composer.local.lock` are gitignored (`.gitignore` lines 6, 12, 13). A worktree's `vendor` is installed from `composer.local.json`, which `bin/composer-local.php` generates with path repositories for the sibling checkouts.
- A plain `composer update` has no `composer.lock` to start from, so it re-resolves every package from the VCS repositories and replaces the sibling symlinks in `vendor`.

## How

- Edit the constraint in `composer.json` first. Then update only the packages involved:

    composer local:update -- pestphp/pest brianium/paratest --with-dependencies --no-interaction

- This regenerates `composer.local.json` from `composer.json` and runs the update with `COMPOSER=composer.local.json`. The new versions land in the untracked `composer.local.lock` and in `vendor`.
- Use `composer local:install` (not `composer install`) to set up a fresh worktree.

## Verify and record

- Run `composer test` once before the change so a failure afterwards can be told apart from a failure that was already there.
- After the update, run `composer validate`, `composer test`, and `composer show {vendor}/{name}` for each package you meant to move.
- `--with-dependencies` can move transitive packages too. List each package that changed, with before and after versions, in `build.md` and in the PR body. The diff only shows `composer.json`, so this list is the reviewer's only record.
```

**Rationale:** The build worked out mid-task that a plain `composer update` would break the sibling symlinks. The PR body didn't list the transitive moves (phpunit 13.4.1 → 13.4.5, phpdoc-parser 2.3.6 → 2.3.9), and no baseline test run was done before the change.

**Evidence:** build.md Deviations, Lock and Tests; round-1/gate.md item 3; pr-body.md AC 3 row; spec.md Design; composer.json scripts `local:install` / `local:update`.
