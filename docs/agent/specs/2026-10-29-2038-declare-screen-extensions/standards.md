# Standards for Declare screen extensions (Polycart)

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| architecture/dependencies | vendor/refactor-circus/standards/standards/architecture/dependencies.md | refactor-circus/standards@dev-main | Atrium stays suggested; no inline checks for Keen in views |
| architecture/public-api | vendor/refactor-circus/standards/standards/architecture/public-api.md | refactor-circus/standards@dev-main | Screen keys and slots become a public extension surface |
| http/authorization | vendor/refactor-circus/standards/standards/http/authorization.md | refactor-circus/standards@dev-main | The access closure asks Polycart's policies; pages authorize before resolving |
| models/models | vendor/refactor-circus/standards/standards/models/models.md | refactor-circus/standards@dev-main | `CartTypeRow` is an unsaved stand-in outside `Models/`, so the model rules do not apply |
| data/value-objects | vendor/refactor-circus/standards/standards/data/value-objects.md | refactor-circus/standards@dev-main | `CartTypeRow::$type` as `public private(set)` |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | New arch rule: no Atrium class outside src/Atrium |
| testing/test-layers | vendor/refactor-circus/standards/standards/testing/test-layers.md | refactor-circus/standards@dev-main | Per-screen feature dataset |
