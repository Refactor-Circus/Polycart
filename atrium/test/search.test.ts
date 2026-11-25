import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { searchPolycartCarts } from '../src/components/search'
import type { AtriumSearchSource } from '@refactor-circus/atrium'
import { withSetup } from '@refactor-circus/atrium/testing'
import { integrationEntry, integrationShell, mockPackageApi, reply, resetPackageApis, useShell } from '@refactor-circus/atrium/testing'
import { CART_ID, cart, cursorPage, guestCart, requests } from './data'
import { useAtrium, useAtriumSearch } from '@refactor-circus/atrium'
import { mockAtriumApi } from '@refactor-circus/atrium/testing'

/*
 * The `polycart` search source (AC 62): ⌘K asks `polycart.search.index`
 * for `q`, capped at the shell's `search.per_source`, and links each cart to
 * `/polycart/carts/:id`.
 */

const { api } = vi.hoisted(() => ({
  api: vi.fn<(path: string, options?: unknown) => Promise<unknown>>()
}))

mockAtriumApi(api)

const source: AtriumSearchSource = { key: 'polycart', integration: 'polycart', label: 'Carts', icon: 'i-lucide-shopping-cart' }

beforeEach(async () => {
  api.mockReset()
  api.mockResolvedValue({ data: [] })

  await useShell(integrationShell([integrationEntry('polycart')], { search: { min_length: 2, sources: [source], per_source: 2 } }))
})

afterEach(() => {
  resetPackageApis()
})

describe('the polycart search source', () => {
  it('is registered under its v1 key', async () => {
    const atrium = await withSetup(() => useAtrium())

    expect(atrium.searchSources.get('polycart')).toBe(searchPolycartCarts)
  })

  it('asks polycart.search.index for q, capped at search.per_source, and links each cart to its page', async () => {
    const calls = mockPackageApi('polycart', { 'GET /search': cursorPage([cart, guestCart, cart]) })

    const search = await withSetup(() => useAtriumSearch())
    await search.search('spring')

    expect(requests(calls, 'GET', '/search').map(call => call.query)).toEqual([{ q: 'spring', per_page: '2' }])

    const results = search.results.value
    expect(results).toHaveLength(2)
    expect(results[0]).toEqual({
      title: 'Spring order',
      subtitle: 'order · open',
      icon: 'i-lucide-shopping-cart',
      group: 'Carts',
      link: { path: `/polycart/carts/${CART_ID}`, external: false }
    })
    expect(results[1]).toMatchObject({ title: guestCart.id, subtitle: 'quote', link: { path: `/polycart/carts/${guestCart.id}`, external: false } })

    const items = search.groups.value.flatMap(group => group.items ?? [])
    expect(items.map(item => (item as { to?: string }).to)).toEqual([`/polycart/carts/${CART_ID}`, `/polycart/carts/${guestCart.id}`])
  })

  it('is dropped without failing the search when Polycart answers 403', async () => {
    mockPackageApi('polycart', { 'GET /search': reply(403) })

    const search = await withSetup(() => useAtriumSearch())
    await search.search('spring')

    expect(search.results.value).toEqual([])
  })

  it('asks nothing while Polycart is not active', async () => {
    await useShell(integrationShell([integrationEntry('polycart', { active: false })], { search: { min_length: 2, sources: [source], per_source: 2 } }))
    const calls = mockPackageApi('polycart', { 'GET /search': cursorPage([cart]) })

    const search = await withSetup(() => useAtriumSearch())
    await search.search('spring')

    expect(calls).toEqual([])
  })
})
