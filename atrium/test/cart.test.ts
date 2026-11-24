import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import type { VueWrapper } from '@vue/test-utils'
import CartPage from '../src/pages/carts/[cart].vue'
import PolycartCart from '../src/components/PolycartCart.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { integrationEntry, integrationShell, mockPackageApi, mountIntegration, reply, resetPackageApis, useShell, type PackageCall, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { CART_ID, CHILD_ID, PARENT_ID, cart, childCart, control, cursorPage, member, orderType, productLine, quoteType, requests, screenPayload, select, selected, selectValues, tableRows } from './data'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'

/*
 * The `polycart.cart` screen (AC 54, 59): one cart from `carts.show`, with
 * every v1 action on its v2 route: update, delete, clear, convert, status
 * transition and visibility; lines update and remove; members share and
 * unshare. Each sends the old screen's fields and then reloads the cart;
 * delete and clear keep a confirmation; a 422 shows its field errors or the
 * cart type's refusal, and a 403 the no-access state.
 */

const { api } = vi.hoisted(() => ({
  api: vi.fn<(path: string, options?: { query?: Record<string, unknown> }) => Promise<unknown>>()
}))

mockAtriumApi(api)

const converted = { ...cart, id: '01jcart00000000000000000009', type: 'quote', status: null }

function polycart(routes: PackageRoutes<'polycart'> = {}): PackageCall[] {
  return mockPackageApi('polycart', {
    'GET /carts/{cart}': { data: cart },
    'GET /types': { data: [orderType, quoteType] },
    'GET /carts': cursorPage([childCart]),
    'PATCH /carts/{cart}': { data: cart },
    'DELETE /carts/{cart}': reply(204),
    'POST /carts/{cart}/clear': { data: { ...cart, lines: [] } },
    'POST /carts/{cart}/status': { data: { ...cart, status: 'submitted' } },
    'POST /carts/{cart}/convert': reply(201, { data: converted }),
    'PUT /carts/{cart}/visibility': { data: { ...cart, visibility: 'scope' } },
    'PATCH /carts/{cart}/lines': { data: cart },
    'DELETE /carts/{cart}/lines': { data: cart },
    'POST /carts/{cart}/members': { data: member },
    'DELETE /carts/{cart}/members/{member}': reply(204),
    ...routes
  })
}

async function mountCart(): Promise<VueWrapper> {
  const wrapper = await mountIntegration(PolycartCart, 'polycart', { props: { cartId: CART_ID } })
  await settle()

  return wrapper as unknown as VueWrapper
}

async function submit(wrapper: VueWrapper, testid: string): Promise<void> {
  await wrapper.find(`[data-testid="${testid}"]`).trigger('submit')
  await settle()
}

async function click(wrapper: VueWrapper, testid: string): Promise<void> {
  await wrapper.find(`[data-testid="${testid}"]`).trigger('click')
  await settle()
}

/** Each change reloads the cart: one `carts.show` on mount, one after. */
function expectReloaded(calls: PackageCall[]): void {
  expect(requests(calls, 'GET', '/carts/{cart}')).toHaveLength(2)
}

beforeEach(() => {
  clearAtriumData()
  api.mockReset()
  api.mockResolvedValue({ data: screenPayload('polycart.cart', 'detail', '/polycart/carts/:cart', CART_ID) })
})

afterEach(async () => {
  resetPackageApis()
  vi.restoreAllMocks()
  await useRouter().replace('/')
})

describe('loading', () => {
  it('loads the cart, its type and its nested carts through the polycart client', async () => {
    const calls = polycart()
    const wrapper = await mountCart()

    expect(requests(calls, 'GET', '/carts/{cart}').map(call => call.path)).toEqual([`/carts/${CART_ID}`])
    expect(requests(calls, 'GET', '/types')).toHaveLength(1)
    expect(requests(calls, 'GET', '/carts')[0]!.query).toEqual({ parent: CART_ID })
    expect(calls.every(call => call.pkg === 'polycart')).toBe(true)

    expect(wrapper.find('[data-testid="polycart-cart-title"]').text()).toBe('Spring order')
    expect(wrapper.find('[data-testid="polycart-subtotal"]').text()).toContain('30.00')
    expect(wrapper.find('[data-testid="polycart-quantity"]').text()).toContain('3')
    expect(wrapper.find('[data-testid="polycart-lines-count"]').text()).toContain('2')
    expect(wrapper.find('[data-testid="polycart-unpriced"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('User #7')
    expect(wrapper.text()).toContain('Team #3')

    const lines = tableRows(wrapper, 'polycart-lines')
    expect(lines).toHaveLength(2)
    expect(lines[0]!.text()).toContain('Product #12')
    expect(lines[0]!.text()).toContain('15.00')
    expect(lines[1]!.text()).toContain('Custom')

    expect(wrapper.findAll('[data-testid="polycart-member"]')[0]!.text()).toContain('User #9')
    expect(wrapper.find('[data-testid="polycart-parent-link"]').attributes('href')).toBe(`/polycart/carts/${PARENT_ID}`)
    expect(wrapper.find('[data-testid="polycart-child"] a').attributes('href')).toBe(`/polycart/carts/${CHILD_ID}`)
  })

  it('asks for no nested carts when the cart has none', async () => {
    const calls = polycart({ 'GET /carts/{cart}': { data: { ...cart, children_count: 0 } } })
    await mountCart()

    expect(requests(calls, 'GET', '/carts')).toEqual([])
  })

  it('offers only the moves and conversions its type allows from the current status', async () => {
    polycart()
    const wrapper = await mountCart()

    const moves = selectValues(wrapper, 'polycart-transition-status')
    const conversions = selectValues(wrapper, 'polycart-convert-to')

    expect(moves).toEqual(['submitted', 'cancelled'])
    expect(conversions).toEqual(['quote'])
  })

  it('warns that a cart of a type no longer registered cannot be changed', async () => {
    polycart({ 'GET /carts/{cart}': { data: { ...cart, type: 'retired' } } })
    const wrapper = await mountCart()

    expect(wrapper.find('[data-testid="polycart-unknown-type"]').text()).toContain('retired')
    expect(wrapper.find('[data-testid="polycart-transition-form"]').exists()).toBe(false)
  })

  it('shows the not-found state on a 404', async () => {
    polycart({ 'GET /carts/{cart}': reply(404, { message: 'Not found.' }) })
    const wrapper = await mountCart()

    expect(wrapper.find('[data-testid="polycart-not-found"]').exists()).toBe(true)
  })

  it('shows the no-access state when the cart answers 403', async () => {
    polycart({ 'GET /carts/{cart}': reply(403) })
    const wrapper = await mountCart()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="polycart-delete"]').exists()).toBe(false)
  })
})

describe('the page', () => {
  it('asks atrium.screens.show for polycart.cart with the cart as its record and the open tab', async () => {
    polycart()
    await useShell(integrationShell([integrationEntry('polycart')]))
    const wrapper = await mountIntegration(CartPage, 'polycart', { route: { path: `/polycart/carts/${CART_ID}`, query: { tab: 'keen.activity' } } })
    await settle()

    expect(api).toHaveBeenCalledWith('/screens/polycart.cart', { query: { record: CART_ID, tab: 'keen.activity' } })
    expect(wrapper.find('[data-testid="polycart-cart-title"]').text()).toBe('Spring order')
  })
})

describe('cart actions', () => {
  it('updates the label and meta', async () => {
    const calls = polycart()
    const wrapper = await mountCart()

    await control(wrapper, 'polycart-details-label').setValue('Autumn order')
    await control(wrapper, 'polycart-details-meta').setValue('{"note": "rush"}')
    await submit(wrapper, 'polycart-details-form')

    const [update] = requests(calls, 'PATCH', '/carts/{cart}')
    expect(update!.path).toBe(`/carts/${CART_ID}`)
    expect(update!.body).toEqual({ label: 'Autumn order', meta: { note: 'rush' } })
    expectReloaded(calls)
    expect(wrapper.find('[data-testid="polycart-notice"]').text()).toContain('Cart updated.')
  })

  it('refuses meta that is not a JSON object, without a request', async () => {
    const calls = polycart()
    const wrapper = await mountCart()

    await control(wrapper, 'polycart-details-meta').setValue('{not json')
    await submit(wrapper, 'polycart-details-form')

    expect(requests(calls, 'PATCH', '/carts/{cart}')).toEqual([])
    expect(wrapper.find('[data-testid="polycart-details-form"]').text()).toContain('must be a JSON object')
  })

  it('shows the field errors of a 422 on update', async () => {
    polycart({ 'PATCH /carts/{cart}': reply(422, { message: 'Invalid.', errors: { label: ['The label may not be greater than 255 characters.'] } }) })
    const wrapper = await mountCart()

    await submit(wrapper, 'polycart-details-form')

    expect(wrapper.find('[data-testid="polycart-details-form"]').text()).toContain('The label may not be greater than 255 characters.')
  })

  it('deletes the cart after a confirmation, then returns to the list', async () => {
    const confirm = vi.spyOn(window, 'confirm').mockReturnValue(true)
    const calls = polycart()
    const wrapper = await mountCart()

    await click(wrapper, 'polycart-delete')

    expect(confirm).toHaveBeenCalledOnce()
    expect(requests(calls, 'DELETE', '/carts/{cart}').map(call => [call.path, call.body])).toEqual([[`/carts/${CART_ID}`, undefined]])
    // The list page loads lazily on its first visit; wait for the navigation.
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe('/polycart/carts'))
  })

  it('does not delete when the confirmation is declined', async () => {
    vi.spyOn(window, 'confirm').mockReturnValue(false)
    const calls = polycart()
    const wrapper = await mountCart()

    await click(wrapper, 'polycart-delete')

    expect(requests(calls, 'DELETE', '/carts/{cart}')).toEqual([])
  })

  it('clears the cart after a confirmation', async () => {
    vi.spyOn(window, 'confirm').mockReturnValue(true)
    const calls = polycart()
    const wrapper = await mountCart()

    await click(wrapper, 'polycart-clear')

    expect(requests(calls, 'POST', '/carts/{cart}/clear').map(call => [call.path, call.body])).toEqual([[`/carts/${CART_ID}/clear`, undefined]])
    expectReloaded(calls)
  })

  it('does not clear when the confirmation is declined', async () => {
    vi.spyOn(window, 'confirm').mockReturnValue(false)
    const calls = polycart()
    const wrapper = await mountCart()

    await click(wrapper, 'polycart-clear')

    expect(requests(calls, 'POST', '/carts/{cart}/clear')).toEqual([])
  })

  it('moves the cart to a next status', async () => {
    const calls = polycart()
    const wrapper = await mountCart()

    await select(wrapper, 'polycart-transition-status').setValue('cancelled')
    await submit(wrapper, 'polycart-transition-form')

    expect(requests(calls, 'POST', '/carts/{cart}/status').map(call => [call.path, call.body])).toEqual([[`/carts/${CART_ID}/status`, { status: 'cancelled' }]])
    expectReloaded(calls)
  })

  it('shows the cart type\'s refusal of a move (a 422 with no field errors)', async () => {
    polycart({ 'POST /carts/{cart}/status': reply(422, { message: 'An order cannot move from open to paid.' }) })
    const wrapper = await mountCart()

    await submit(wrapper, 'polycart-transition-form')

    expect(wrapper.find('[data-testid="polycart-error"]').text()).toContain('An order cannot move from open to paid.')
  })

  it('converts the cart, keeping the original by default, then opens the converted cart', async () => {
    const calls = polycart()
    const wrapper = await mountCart()

    await submit(wrapper, 'polycart-convert-form')

    expect(requests(calls, 'POST', '/carts/{cart}/convert').map(call => [call.path, call.body])).toEqual([[`/carts/${CART_ID}/convert`, { to: 'quote', copy: true }]])
    expect(useRouter().currentRoute.value.path).toBe(`/polycart/carts/${converted.id}`)
  })

  it('converts in place when "keep the original" is unticked', async () => {
    const calls = polycart({ 'POST /carts/{cart}/convert': reply(201, { data: { ...cart, type: 'quote' } }) })
    const wrapper = await mountCart()

    await wrapper.find('[data-testid="polycart-convert-copy"]').trigger('click')
    await submit(wrapper, 'polycart-convert-form')

    expect(requests(calls, 'POST', '/carts/{cart}/convert')[0]!.body).toEqual({ to: 'quote', copy: false })
    expectReloaded(calls)
  })

  it('sets who may see the cart', async () => {
    const calls = polycart()
    const wrapper = await mountCart()

    expect(selected(wrapper, 'polycart-visibility')).toBe('private')

    await select(wrapper, 'polycart-visibility').setValue('boundary')
    await submit(wrapper, 'polycart-visibility-form')

    expect(requests(calls, 'PUT', '/carts/{cart}/visibility').map(call => [call.path, call.body])).toEqual([[`/carts/${CART_ID}/visibility`, { visibility: 'boundary' }]])
    expectReloaded(calls)
  })
})

describe('line actions', () => {
  it('updates a line\'s quantity', async () => {
    const calls = polycart()
    const wrapper = await mountCart()

    const line = tableRows(wrapper, 'polycart-lines')[0]!
    // A number field commits what is typed when it loses focus.
    await line.find('[data-testid="polycart-line-quantity"]').setValue('5')
    await line.find('[data-testid="polycart-line-quantity"]').trigger('blur')
    await line.find('[data-testid="polycart-line-form"]').trigger('submit')
    await settle()

    expect(requests(calls, 'PATCH', '/carts/{cart}/lines').map(call => [call.path, call.body])).toEqual([
      [`/carts/${CART_ID}/lines`, { lines: [{ id: productLine.id, quantity: 5 }] }]
    ])
    expectReloaded(calls)
  })

  it('shows a line\'s quantity error on a 422', async () => {
    polycart({ 'PATCH /carts/{cart}/lines': reply(422, { message: 'Invalid.', errors: { 'lines.0.quantity': ['The quantity must be at least 0.'] } }) })
    const wrapper = await mountCart()

    const line = tableRows(wrapper, 'polycart-lines')[0]!
    await line.find('[data-testid="polycart-line-form"]').trigger('submit')
    await settle()

    expect(tableRows(wrapper, 'polycart-lines')[0]!.text()).toContain('The quantity must be at least 0.')
  })

  it('removes a line', async () => {
    const calls = polycart()
    const wrapper = await mountCart()

    await tableRows(wrapper, 'polycart-lines')[0]!.find('[data-testid="polycart-line-remove"]').trigger('click')
    await settle()

    const [remove] = requests(calls, 'DELETE', '/carts/{cart}/lines')
    expect(remove!.path).toBe(`/carts/${CART_ID}/lines`)
    expect(remove!.query).toEqual({ 'lines[]': productLine.id })
    expect(remove!.body).toBeUndefined()
    expectReloaded(calls)
  })
})

describe('member actions', () => {
  it('shares the cart with a member and a role', async () => {
    const calls = polycart()
    const wrapper = await mountCart()

    await control(wrapper, 'polycart-share-type').setValue('user')
    await control(wrapper, 'polycart-share-id').setValue('12')
    await control(wrapper, 'polycart-share-role').setValue('viewer')
    await submit(wrapper, 'polycart-share-form')

    expect(requests(calls, 'POST', '/carts/{cart}/members').map(call => [call.path, call.body])).toEqual([
      [`/carts/${CART_ID}/members`, { member_type: 'user', member_id: '12', role: 'viewer' }]
    ])
    expectReloaded(calls)
  })

  it('shows the share form\'s field errors on a 422', async () => {
    polycart({ 'POST /carts/{cart}/members': reply(422, { message: 'Invalid.', errors: { member_id: ['No such member.'] } }) })
    const wrapper = await mountCart()

    await submit(wrapper, 'polycart-share-form')

    expect(wrapper.find('[data-testid="polycart-share-form"]').text()).toContain('No such member.')
  })

  it('offers no share form for a cart outside any scope', async () => {
    polycart({ 'GET /carts/{cart}': { data: { ...cart, scope_type: null, scope_id: null } } })
    const wrapper = await mountCart()

    expect(wrapper.find('[data-testid="polycart-share-form"]').exists()).toBe(false)
  })

  it('unshares a member', async () => {
    const calls = polycart()
    const wrapper = await mountCart()

    await wrapper.find('[data-testid="polycart-member-remove"]').trigger('click')
    await settle()

    expect(requests(calls, 'DELETE', '/carts/{cart}/members/{member}').map(call => [call.path, call.body])).toEqual([
      [`/carts/${CART_ID}/members/${member.id}`, undefined]
    ])
    expectReloaded(calls)
  })
})

describe('a refused change', () => {
  it('shows the no-access state on a 403, and changes nothing', async () => {
    const calls = polycart({ 'PUT /carts/{cart}/visibility': reply(403) })
    const wrapper = await mountCart()

    await submit(wrapper, 'polycart-visibility-form')

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(requests(calls, 'GET', '/carts/{cart}')).toHaveLength(1)
  })
})
