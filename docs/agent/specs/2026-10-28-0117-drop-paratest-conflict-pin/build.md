# Build: Drop the paratest conflict pin

## Group 1

Commit `41419dc` on `ring-master/2026-10-28-0117-drop-paratest-conflict-pin`.

### Files

- `composer.json`: `pestphp/pest` `^5.3.1` to `^5.3.4`; the `conflict` block (`brianium/paratest >=7.26.1`) is deleted, and the key with it since it was then empty.

### Lock

- Before: `brianium/paratest` v7.26.0, `pestphp/pest` v5.3.1.
- After: `brianium/paratest` v7.27.4, `pestphp/pest` v5.3.4. `--with-dependencies` also moved `phpunit/phpunit` 13.4.1 to 13.4.5 and `phpstan/phpdoc-parser` 2.3.6 to 2.3.9. The `refactor-circus/*` entries are path symlinks to the sibling checkouts; their lock refs only record the siblings' current commits ("Source already present"), so no vendor code changed for them.
- `composer.lock`, `composer.local.json` and `composer.local.lock` are all gitignored in this package (`.gitignore` lines 6, 12, 13). The worktree's `vendor` was installed from the local manifest, so the update ran through `composer local:update -- pestphp/pest brianium/paratest --with-dependencies --no-interaction`. That regenerates `composer.local.json` from `composer.json` and runs the update with `COMPOSER=composer.local.json`. The new versions are locked in the worktree's `composer.local.lock` and installed in its `vendor`.

### Tests

- No tests were written: the change is a dependency constraint with no behavior to test. The acceptance criteria are checked by `composer validate` and by the suite.
- `composer validate`: `./composer.json is valid`.
- `composer test` and `vendor/bin/pest --parallel` were not run here; the gate runs them after the build, as the job instructed. There is no baseline `composer test` result for the same reason.
- `git diff --stat main`: `composer.json | 7 ++-----` only.

### Deviations

- AC 3 and AC 7 name `composer.lock`, but it is gitignored, so the commit touches only `composer.json`. The locked versions live in the untracked `composer.local.lock`, which backs the worktree's `vendor`.
- Task 1.3's plain `composer update ...` was run through the package's `local:update` script instead. Without a `composer.lock` in the worktree, the plain command would re-resolve every package from VCS and replace the sibling path symlinks in `vendor`.

### Retry

- Re-checked; nothing new to commit. `41419dc` is still the branch head and the worktree is clean. `git diff --stat main` shows only `composer.json`. `composer.local.lock` and `vendor/composer/installed.json` both have `brianium/paratest` v7.27.4 and `pestphp/pest` v5.3.4. `composer validate` passes. `git check-ignore` confirms `composer.lock`, `composer.local.json` and `composer.local.lock` are gitignored (`.gitignore` lines 6, 12, 13).
