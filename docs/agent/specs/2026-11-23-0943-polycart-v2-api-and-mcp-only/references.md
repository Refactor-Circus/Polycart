# References

- **Inventory of `Polycart/src/Atrium`** (2026-11-23, read-only survey): pages, widgets, search, settings and extensions, with the API equivalent of each and the gaps listed in shape.md. The UI controllers in `src/Atrium/Http` show which actions and resources each page needs; the new endpoints reuse those actions.
- **Atrium v2 spec** `Atrium/docs/agent/specs/2026-11-22-1823-atrium-v2-headless`: the JSON API + prebuilt Vue app (served by a Blade page) model this package's UI moves into, with Polycart shipping its own module in `dist/atrium`, and its `{key, component, data}` contract.
- **Keystone** `loadApiRoutesFrom()` and `JsonResource`: the route group and resource base for the new endpoints.
- **dedoc/scramble**: OpenAPI export; **openapi-typescript** and **openapi-fetch**: the TypeScript client.
