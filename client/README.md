# @refactor-circus/polycart-client

A typed fetch client for the Polycart JSON API. `src/schema.d.ts` is generated
by [openapi-typescript](https://openapi-ts.dev/) from the package's committed
`openapi.json`, and `createPolycartClient()` wraps
[openapi-fetch](https://openapi-ts.dev/openapi-fetch/)'s `createClient<paths>`.

The package ships TypeScript source only, with no build step: its `exports`
point at `src/index.ts`, so the consumer's bundler (Polycart's Atrium module in
`../atrium`, for example) compiles it.

## Usage

The host must turn the API on (`polycart.routes.enabled`, off by default),
behind its own auth middleware.

```ts
import { createPolycartClient } from '@refactor-circus/polycart-client';

const polycart = createPolycartClient({
  // The host's API base URL, `polycart.routes.prefix` included.
  baseUrl: 'https://app.test/polycart',
  credentials: 'include',
});

const { data: stats } = await polycart.GET('/stats');

const { data: found } = await polycart.GET('/search', {
  params: { query: { q: 'lobby', per_page: 10 } },
});

const { data: cart } = await polycart.GET('/carts/{cart}', {
  params: { path: { cart: cartId } },
});
```

`createPolycartClient(options)` takes `baseUrl` plus any other openapi-fetch
`ClientOptions` (`headers`, `fetch`, `credentials`, ...) and returns a
`Client<paths>`. The `paths`, `components` and `operations` types are
re-exported:

```ts
import type { components } from '@refactor-circus/polycart-client';

type Cart = components['schemas']['CartResource'];
```

## Installing

The client is not published to npm. Pin it as a git dependency on the Polycart
release tag the host runs (`v2.0.0`). It lives in the `client/` folder, and npm
cannot install a subfolder of a git repository, so use a package manager that
can, such as pnpm:

```json
{
  "dependencies": {
    "@refactor-circus/polycart-client": "github:refactor-circus/polycart#v2.0.0&path:/client"
  }
}
```

## Regenerating

Never edit `src/schema.d.ts` or `openapi.sha256` by hand. From the package
root, run `composer client:generate` (needs Node). It exports `openapi.json`
with `vendor/bin/testbench workbench:openapi`, runs `npm --prefix client ci`,
then `npm --prefix client run generate`, which writes `src/schema.d.ts` and
`openapi.sha256` (the SHA-256 of the `openapi.json` it came from). Commit
`openapi.json` and `client/`, and check the client with
`npm --prefix client run typecheck`.

`tests/Feature/OpenApiTest.php` fails when `openapi.json` differs from a fresh
export or the stamp does not match it, and the CI `client` job regenerates
everything and fails on any diff.
