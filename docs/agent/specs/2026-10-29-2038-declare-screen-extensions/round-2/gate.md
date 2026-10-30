VERDICT: PASS

# Gate, round 2

- `composer test`: passed (phpstan 0 errors, pint passed, pest 297/297 with 1437 assertions, coverage 100%, WithoutAtrium 5/5)
- `composer lint:check`: passed
- `composer exec ringside check`: passed

## Criteria

1. tests/Feature/Ui/ScreenExtensionsTest.php: "declares each screen with its key, kind and standard slots", "renders each registered section once in its slot...", "opens the overview without ?tab= ..."
2. ScreenExtensionsTest.php access tests ("shows a permission('view') section on a cart to an operator who is not a member", "shows a permission('viewAny') row section on polycart.types ...", unknown-ability dataset per D-044/D-045)
3. tests/Unit/CartTypeRowTest.php (3 tests); ScreenExtensionsTest "passes the cart types to their screen as CartTypeRow rows"
4. ScreenExtensionsTest "answers 403 before running any extension for a user the page refuses"
5. ScreenExtensionsTest "lays the header actions, tab bar and slots around the page"
6. ScreenExtensionsTest "renders no tab bar or sections while nothing is registered"; tests/Feature/PerformanceTest.php budget
7. ScreenExtensionsTest "leaves no audit trail in any view", "links no history page from its navigation"; tests/Keen/HistoryTest.php "keeps the history route, tool and listeners with Keen installed..."; HistoryNavigationTest.php deleted
8. tests/Feature/ComposerTest.php (7 tests); tests/ArchTest.php Atrium arch rule
9. tests/WithoutAtrium/WithoutAtriumTest.php (5 tests), phpunit.without-atrium.xml, .github/workflows/tests.yml
10. tests/Feature/Ui/ScreenIsolationTest.php
11. tests/Feature/DocsTest.php (3 tests)
12. Full gate passed
