import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import type { VueWrapper } from '@vue/test-utils'
import TypesPage from '../src/pages/types.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, mountIntegration, reply, resetPackageApis, type PackageCall, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { orderType, quoteType, requests, screenPayload } from './data'
import { tableRows } from '@refactor-circus/atrium/testing'
import { clearAtriumData, mockAtriumApi } from '@refactor-circus/atrium/testing'

/*
 * The `polycart.types` screen (AC 54, 59): every registered cart type from
 * `types.index`, with its statuses, conversions, parents, rules and
 * lifetime, each linking to its carts.
 */

const { api } = vi.hoisted(() => ({
  api: vi.fn<(path: string, options?: { query?: Record<string, unknown> }) => Promise<unknown>>()
}))

mockAtriumApi(api)

function polycart(routes: PackageRoutes<'polycart'> = {}): PackageCall[] {
  return mockPackageApi('polycart', {
    'GET /types': { data: [orderType, quoteType] },
    ...routes
  })
}

async function mountPage(): Promise<VueWrapper> {
  const wrapper = await mountIntegration(TypesPage, 'polycart')
  await settle()

  return wrapper as unknown as VueWrapper
}

beforeEach(() => {
  clearAtriumData()
  api.mockReset()
  api.mockResolvedValue({ data: screenPayload('polycart.types', 'list', '/polycart/types') })
})

afterEach(() => {
  resetPackageApis()
})

describe('polycart.types', () => {
  it('lists types.index through the polycart client, and only that', async () => {
    const calls = polycart()
    const wrapper = await mountPage()

    expect(calls.map(call => `${call.method} ${call.route}`)).toEqual(['GET /types'])

    const rows = tableRows(wrapper, 'polycart-types-table')
    expect(rows).toHaveLength(2)

    const [order, quote] = rows
    expect(order!.find('[data-testid="polycart-type-link"]').text()).toBe('order')
    expect(order!.find('[data-testid="polycart-type-link"]').attributes('href')).toBe('/polycart/carts?type=order')
    expect(order!.findAll('[data-testid="polycart-type-status"]').map(status => status.attributes('data-status'))).toEqual(['open', 'submitted', 'paid', 'cancelled'])
    expect(order!.text()).toContain('quote')
    expect(order!.text()).toContain('Any')
    expect(order!.text()).toContain('Priced')
    expect(order!.text()).toContain('30 days')

    expect(quote!.text()).toContain('Root only')
    expect(quote!.text()).toContain('Nested')
    expect(quote!.text()).toContain('Separate lines')
    expect(quote!.text()).toContain('Never')
  })

  it('asks atrium.screens.show for its extensions', async () => {
    polycart()
    await mountPage()

    expect(api).toHaveBeenCalledWith('/screens/polycart.types', { query: { record: undefined, tab: undefined } })
  })

  it('shows the no-access state on a 403', async () => {
    polycart({ 'GET /types': reply(403) })
    const wrapper = await mountPage()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="polycart-types-table"]').exists()).toBe(false)
  })

  it('offers a retry when the list fails otherwise', async () => {
    const calls = polycart({ 'GET /types': reply(500) })
    const wrapper = await mountPage()

    await wrapper.find('[data-testid="polycart-retry"]').trigger('click')
    await settle()

    expect(requests(calls, 'GET', '/types')).toHaveLength(2)
  })
})
