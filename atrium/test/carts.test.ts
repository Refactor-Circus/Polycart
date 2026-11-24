import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import type { VueWrapper } from '@vue/test-utils'
import CartsPage from '../src/pages/carts/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, mountIntegration, reply, resetPackageApis, type PackageCall, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { cart, childCart, control, cursorPage, guestCart, orderType, quoteType, requests, screenPayload } from './data'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'

/*
 * The `polycart.carts` screen (AC 54, 59): Polycart's carts, from
 * `carts.index`, filtered by v1's type, status, source and search and paged
 * by cursor as the endpoint pages; the type filter offers `types.index`.
 */

const { api } = vi.hoisted(() => ({
  api: vi.fn<(path: string, options?: { query?: Record<string, unknown> }) => Promise<unknown>>()
}))

mockAtriumApi(api)

function polycart(routes: PackageRoutes<'polycart'> = {}): PackageCall[] {
  return mockPackageApi('polycart', {
    'GET /types': { data: [orderType, quoteType] },
    'GET /carts': call => call.query.cursor === 'page-2'
      ? cursorPage([guestCart], null, 'page-1')
      : cursorPage([cart, childCart], 'page-2'),
    ...routes
  })
}

/** Mounts the page at `/polycart/carts`, with `query` (mountSuspended() navigates there itself). */
async function mountPage(query: Record<string, string> = {}): Promise<VueWrapper> {
  const wrapper = await mountIntegration(CartsPage, 'polycart', { route: { path: '/polycart/carts', query } })
  await settle()

  return wrapper as unknown as VueWrapper
}

beforeEach(() => {
  clearAtriumData()
  api.mockReset()
  api.mockResolvedValue({ data: screenPayload('polycart.carts', 'list', '/polycart/carts') })
})

afterEach(async () => {
  resetPackageApis()
  vi.restoreAllMocks()
  await useRouter().replace({ query: {} })
})

describe('loading', () => {
  it('loads the carts and the types through the polycart client, with no filter', async () => {
    const calls = polycart()
    const wrapper = await mountPage()

    expect(requests(calls, 'GET', '/carts')).toHaveLength(1)
    expect(requests(calls, 'GET', '/carts')[0]!.query).toEqual({})
    expect(requests(calls, 'GET', '/types')).toHaveLength(1)
    expect(calls.every(call => call.pkg === 'polycart')).toBe(true)

    const rows = wrapper.findAll('[data-testid="polycart-cart-row"]')
    expect(rows).toHaveLength(2)
    expect(rows[0]!.text()).toContain('Spring order')
    expect(rows[0]!.text()).toContain('order')
    expect(rows[0]!.text()).toContain('User #7')
    expect(rows[0]!.find('[data-testid="polycart-cart-link"]').attributes('href')).toBe(`/polycart/carts/${cart.id}`)
    expect(rows[0]!.find('[data-testid="polycart-cart-status"]').attributes('data-status')).toBe('open')
    expect(rows[0]!.find('[data-testid="polycart-cart-status"]').attributes('data-color')).toBe('success')
  })

  it('asks atrium.screens.show for its extensions, with the open tab', async () => {
    polycart()
    await mountPage({ tab: 'keen.activity' })

    expect(api).toHaveBeenCalledWith('/screens/polycart.carts', { query: { record: undefined, tab: 'keen.activity' } })
  })

  it('shows a guest cart\'s owner and an expired cart\'s status', async () => {
    polycart({ 'GET /carts': cursorPage([{ ...guestCart, status: 'open' }]) })
    const wrapper = await mountPage()

    const row = wrapper.find('[data-testid="polycart-cart-row"]')
    expect(row.text()).toContain('Guest')
    expect(row.text()).toContain(guestCart.id)
    expect(row.find('[data-testid="polycart-cart-status"]').attributes('data-color')).toBe('neutral')
    expect(row.find('[data-testid="polycart-cart-status"]').text()).toContain('expired')
  })

  it('says so when no cart matches', async () => {
    polycart({ 'GET /carts': cursorPage([]) })
    const wrapper = await mountPage()

    expect(wrapper.find('[data-testid="polycart-no-carts"]').exists()).toBe(true)
  })
})

describe('filters', () => {
  it('offers the registered types to filter by', async () => {
    polycart()
    const wrapper = await mountPage()

    const options = control(wrapper, 'polycart-filter-type').findAll('option').map(option => [option.element.value, option.text()])
    expect(options).toEqual([['', 'All types'], ['order', 'order'], ['quote', 'quote']])
  })

  it('sends the type, status, source and search filters as carts.index query parameters', async () => {
    const calls = polycart()
    const wrapper = await mountPage()

    await control(wrapper, 'polycart-filter-type').setValue('order')
    await control(wrapper, 'polycart-filter-status').setValue('open')
    await control(wrapper, 'polycart-filter-source').setValue('api')
    await control(wrapper, 'polycart-filter-search').setValue('spring')
    await wrapper.find('[data-testid="polycart-filters"]').trigger('submit')
    await settle()

    const loads = requests(calls, 'GET', '/carts')
    expect(loads).toHaveLength(2)
    expect(loads[1]!.query).toEqual({ type: 'order', status: 'open', source: 'api', search: 'spring' })
    expect(useRouter().currentRoute.value.query).toMatchObject({ type: 'order', status: 'open', source: 'api', search: 'spring' })
  })

  it('reads its filters from the page query, as the widgets and the types screen link to it', async () => {
    const calls = polycart()
    const wrapper = await mountPage({ type: 'quote', unexpired: '1' })

    expect(requests(calls, 'GET', '/carts')[0]!.query).toEqual({ type: 'quote', unexpired: '1' })
    expect((control(wrapper, 'polycart-filter-type').element as unknown as HTMLSelectElement).value).toBe('quote')
  })

  it('resets every filter', async () => {
    const calls = polycart()
    const wrapper = await mountPage({ type: 'quote', status: 'open' })

    await wrapper.find('[data-testid="polycart-reset"]').trigger('click')
    await settle()

    const loads = requests(calls, 'GET', '/carts')
    expect(loads.at(-1)!.query).toEqual({})
  })
})

describe('paging', () => {
  it('pages by cursor, as carts.index does', async () => {
    const calls = polycart()
    const wrapper = await mountPage()

    expect(wrapper.find('[data-testid="polycart-prev"]').attributes('disabled')).toBeDefined()

    await wrapper.find('[data-testid="polycart-next"]').trigger('click')
    await settle()

    expect(requests(calls, 'GET', '/carts')[1]!.query).toEqual({ cursor: 'page-2' })
    expect(wrapper.findAll('[data-testid="polycart-cart-row"]')).toHaveLength(1)

    await wrapper.find('[data-testid="polycart-prev"]').trigger('click')
    await settle()

    expect(requests(calls, 'GET', '/carts')[2]!.query).toEqual({ cursor: 'page-1' })
  })

  it('keeps the filters while paging', async () => {
    const calls = polycart()
    const wrapper = await mountPage({ type: 'order' })

    await wrapper.find('[data-testid="polycart-next"]').trigger('click')
    await settle()

    expect(requests(calls, 'GET', '/carts')[1]!.query).toEqual({ type: 'order', cursor: 'page-2' })
  })
})

describe('errors', () => {
  it('shows the no-access state on a 403', async () => {
    polycart({ 'GET /carts': reply(403) })
    const wrapper = await mountPage()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="polycart-filters"]').exists()).toBe(false)
  })

  it('shows field errors on a 422', async () => {
    polycart({ 'GET /carts': reply(422, { message: 'The given data was invalid.', errors: { type: ['The selected type is invalid.'] } }) })
    const wrapper = await mountPage()

    expect(wrapper.text()).toContain('The selected type is invalid.')
  })

  it('offers a retry when the list fails otherwise', async () => {
    const calls = polycart({ 'GET /carts': reply(500) })
    const wrapper = await mountPage()

    expect(wrapper.find('[data-testid="polycart-load-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="polycart-retry"]').trigger('click')
    await settle()

    expect(requests(calls, 'GET', '/carts')).toHaveLength(2)
  })
})
