# Standards for Polycart v2: API and MCP only

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| actions/actions-events | vendor/refactor-circus/standards/standards/actions/actions-events.md | refactor-circus/standards@dev-main | New read action `ShowCartStatsAction` with its `CartStatsShowing`/`CartStatsShown` event pair |
| architecture/configuration | vendor/refactor-circus/standards/standards/architecture/configuration.md | refactor-circus/standards@dev-main | `polycart.ui` and `polycart.atrium.*` removed; `routes` banner rewritten; `routes.enabled` keeps its safe default |
| architecture/dependencies | vendor/refactor-circus/standards/standards/architecture/dependencies.md | refactor-circus/standards@dev-main | No Atrium (or PennantPlus) reference left; Keystone stays the only suite `require`; Scramble stays dev-only |
| architecture/domain-modules | vendor/refactor-circus/standards/standards/architecture/domain-modules.md | refactor-circus/standards@dev-main | New code lands in `src/Domains/Cart` (`Support/`, `Http/Resources/`); top-level `src/Atrium` goes |
| architecture/naming | vendor/refactor-circus/standards/standards/architecture/naming.md | refactor-circus/standards@dev-main | `ShowCartStatsAction`, `CartStats*ActionEvent`, `ShowCartStatsRequest`, `SearchCartsRequest`, `CartStatsResource`, `CartStatsTool` |
| architecture/service-providers | vendor/refactor-circus/standards/standards/architecture/service-providers.md | refactor-circus/standards@dev-main | Provider drops the Atrium plugin, views, translations and the `polycart-views`/`polycart-lang` publish tags |
| data/data-objects | vendor/refactor-circus/standards/standards/data/data-objects.md | refactor-circus/standards@dev-main | Search builds `ListCartsData::fromValidated()`; its `q` rules derive from `ListCartsData::rules()` |
| data/value-objects | vendor/refactor-circus/standards/standards/data/value-objects.md | refactor-circus/standards@dev-main | New `CartStats` result object |
| http/authorization | vendor/refactor-circus/standards/standards/http/authorization.md | refactor-circus/standards@dev-main | Stats and search authorize `viewAny` on `CartModel` and scope to the actor; operator mode (`show_all`) goes |
| http/request-persist | vendor/refactor-circus/standards/standards/http/request-persist.md | refactor-circus/standards@dev-main | Both endpoints are a Request with `persist()` behind a one-line controller method |
| http/resources | vendor/refactor-circus/standards/standards/http/resources.md | refactor-circus/standards@dev-main | Model-less `CartStatsResource` (with `schema()`), reused by HTTP and MCP; recent carts through `CartResource` |
| http/routes | vendor/refactor-circus/standards/standards/http/routes.md | refactor-circus/standards@dev-main | `polycart.stats.show` and `polycart.search.index` in the Cart domain's `routes.php`, inside the shared group |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | Atrium arch rule tightened (no `ignoring`); parity rule must stay empty; Scramble kept out of `src/` |
| testing/test-layers | vendor/refactor-circus/standards/standards/testing/test-layers.md | refactor-circus/standards@dev-main | Stats behavior tested at the action; HTTP/MCP tests cover wiring only; routes by name |
