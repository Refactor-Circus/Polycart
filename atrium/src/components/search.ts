import { integrationData, useAtriumIntegration } from '@refactor-circus/atrium'
import type { AtriumSearchResult } from '@refactor-circus/atrium'
import { polycartCartPath, polycartCartTitle } from './polycart'

/**
 * The `polycart` search source (v1's key), run by ⌘K beside Atrium's own
 * search: `polycart.search.index` for `q`, capped at the shell's
 * `search.per_source`, each cart linking to `/polycart/carts/:id` as v1's
 * results linked to the cart's page. Its results fall under the source's
 * label (`Carts`), as v1 grouped them.
 *
 * A failure rejects, so ⌘K drops this source and keeps the others.
 */
export async function searchPolycartCarts(q: string, { limit }: { limit: number }): Promise<AtriumSearchResult[]> {
  const { client } = useAtriumIntegration('polycart')

  if (!client) {
    return []
  }

  const page = integrationData(await client.GET('/search', { params: { query: { q, per_page: limit } } }))

  return page.data.slice(0, limit).map(cart => ({
    title: polycartCartTitle(cart),
    subtitle: cart.status === null ? cart.type : `${cart.type} · ${cart.status}`,
    icon: 'i-lucide-shopping-cart',
    group: null,
    link: { path: polycartCartPath(cart.id), external: false }
  }))
}
