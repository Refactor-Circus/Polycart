# Tasks: Drop the paratest conflict pin

## Group 1: Bump Pest and drop the paratest conflict
depends on: none
files: composer.json, composer.lock
criteria: 1, 2, 3, 4, 5, 6, 7

- [x] 1.1 Check the current state: `composer.json` still has the `brianium/paratest` conflict and `pestphp/pest ^5.3.1`; note the baseline `composer test` result
- [x] 1.2 Edit `composer.json`: require `pestphp/pest` `^5.3.4`, delete the `conflict` block (and the key if it is then empty)
- [x] 1.3 Run `composer update pestphp/pest brianium/paratest --with-dependencies`; confirm the lock has paratest `>=7.27.4 <8` and pest `>=5.3.4`
- [x] 1.4 Run `composer validate`; then `composer test`; then `vendor/bin/pest --parallel`; all green
- [x] 1.5 Confirm `git diff --stat` touches only `composer.json` and `composer.lock`
