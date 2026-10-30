# Tasks: Declare screen extensions (Polycart)

## Group 1: Screen declarations
package: Polycart
depends on: none
files: src/Atrium/Support/Screens.php, src/Atrium/Data/CartTypeRow.php, src/Atrium/Http/Controllers/*, src/Atrium/PolycartPlugin.php, resources/views/ui/**, tests/TestCase.php, tests/Unit/CartTypeRowTest.php, tests/Feature/Ui/ScreenExtensionsTest.php, tests/Feature/Ui/ScreenIsolationTest.php, tests/Feature/PerformanceTest.php, tests/Feature/HistoryNavigationTest.php, tests/Keen/HistoryTest.php
criteria: 1, 2, 3, 4, 5, 6, 7, 10

- [ ] 1.1 Write the tests: `CartTypeRow` unit test, screen declaration and layout dataset, access closure cases, refused-user zero-call dataset, nothing-registered and query-budget tests, audit-trail and nav removal, history route kept, and the ordered isolation pair.
- [ ] 1.2 Add `ResetsScreenExtensions` to `tests/TestCase.php`, the `CartTypeRow` stand-in and the `Atrium\Support\Screens` helper with the criterion 2 access closure.
- [ ] 1.3 Resolve the screen in each controller after `authorizeScreen()`, pass `resolved` (and `rows` on types), and drop `showHistory`.
- [ ] 1.4 Lay out tabs, panel and slots in the three views, remove both `x-atrium::audit-trail` tags, and drop `historyNavItem()` from `PolycartPlugin::navigation()`.
- [ ] 1.5 Delete `HistoryNavigationTest` and the two Keen dashboard history tests, and list them in the PR.
- [ ] 1.6 Run vendor/bin/pest; all green.

## Group 2: Atrium optional
package: Polycart
depends on: none
files: composer.json, tests/ArchTest.php, tests/Feature/ComposerTest.php, phpunit.without-atrium.xml, tests/WithoutAtrium/*, .github/workflows/tests.yml
criteria: 8, 9

- [ ] 2.1 Write the composer.json test, the arch rule and the WithoutAtrium suite (bootstrap, test case, boot, routes, JSON API, MCP assertions).
- [ ] 2.2 Add the `conflict` entry and the `test:without-atrium` script to `composer test`, and add the `without-atrium` CI job.
- [ ] 2.3 Run vendor/bin/pest; all green.

## Group 3: Docs and gate
package: Polycart
depends on: 1, 2
files: README.md, CHANGELOG.md, CLAUDE.md, resources/boost/skills/polycart-development/SKILL.md, docs/sources-and-history.md, tests/Feature/DocsTest.php
criteria: 11, 12

- [ ] 3.1 Write the docs test for the three keys and the absence of `audit-trail` and `historyNavItem`.
- [ ] 3.2 Update the README, changelog, CLAUDE.md, Boost skill and `docs/sources-and-history.md`.
- [ ] 3.3 Run composer test and vendor/bin/pest; all green.
