import createClient, { type Client, type ClientOptions } from 'openapi-fetch';
import type { paths } from './schema';

export type { components, operations, paths } from './schema';

/**
 * openapi-fetch's client options, with a required `baseUrl`: the host's API
 * base URL, its `polycart.routes.prefix` included (e.g. `https://app.test/polycart`).
 * The document's paths (`/carts`, `/stats`, `/search`, ...) are relative to it.
 */
export type PolycartClientOptions = ClientOptions & {
  baseUrl: string;
};

export type PolycartClient = Client<paths>;

/**
 * A typed openapi-fetch client for the Polycart JSON API:
 * `client.GET('/search', { params: { query: { q: 'lobby' } } })`.
 */
export function createPolycartClient(options: PolycartClientOptions): PolycartClient {
  return createClient<paths>(options);
}
