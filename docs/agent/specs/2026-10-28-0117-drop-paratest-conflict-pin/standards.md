# Standards for Drop the paratest conflict pin

No standard in `docs/agent/standards.index.yml` applies: the change edits `composer.json` and `composer.lock` only, with no code, tests or config. `composer test` (which runs `tests/ArchTest.php`) must keep passing.

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
