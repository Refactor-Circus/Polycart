import type { components } from '../../client/src/schema'
import type { AtriumIntegrationScreenData } from '@refactor-circus/atrium'
import type { PackageCall } from '@refactor-circus/atrium/testing'

/*
 * Polycart payloads for the screen and widget suites, each typed with
 * `satisfies` against the vendored `components` of Polycart's schema.
 */

type Schemas = components['schemas']

export const CART_ID = '01jcart00000000000000000001'
export const CHILD_ID = '01jcart00000000000000000002'
export const PARENT_ID = '01jcart00000000000000000000'

export const productLine = {
  id: '01jline00000000000000000001',
  cart_id: CART_ID,
  purchasable_type: 'App\\Models\\Product',
  purchasable_id: '12',
  quantity: 2,
  unit_price: 1500,
  total: 3000,
  options: null,
  meta: null,
  position: 1,
  created_at: '2026-11-20T10:00:00+00:00',
  updated_at: '2026-11-20T10:00:00+00:00'
} satisfies Schemas['CartLineResource']

export const customLine = {
  id: '01jline00000000000000000002',
  cart_id: CART_ID,
  purchasable_type: null,
  purchasable_id: null,
  quantity: 1,
  unit_price: null,
  total: null,
  options: null,
  meta: null,
  position: 2,
  created_at: '2026-11-20T10:00:00+00:00',
  updated_at: '2026-11-20T10:00:00+00:00'
} satisfies Schemas['CartLineResource']

export const member = {
  id: '01jmember000000000000000001',
  cart_id: CART_ID,
  member_type: 'App\\Models\\User',
  member_id: '9',
  role: 'editor',
  created_at: '2026-11-20T10:00:00+00:00'
} satisfies Schemas['CartMemberResource']

export const cart = {
  id: CART_ID,
  type: 'order',
  status: 'open',
  source: 'api',
  label: 'Spring order',
  owner_type: 'App\\Models\\User',
  owner_id: '7',
  session_key: null,
  scope_type: 'App\\Models\\Team',
  scope_id: '3',
  boundary_type: 'App\\Models\\Organization',
  boundary_id: '1',
  visibility: 'private',
  parent_id: PARENT_ID,
  root_id: PARENT_ID,
  meta: {},
  lines_count: 2,
  children_count: 1,
  quantity: 3,
  subtotal: 3000,
  fully_priced: false,
  lines: [productLine, customLine],
  members: [member],
  expires_at: null,
  created_at: '2026-11-20T10:00:00+00:00',
  updated_at: '2026-11-21T10:00:00+00:00'
} satisfies Schemas['CartResource']

export const childCart = {
  ...cart,
  id: CHILD_ID,
  type: 'quote',
  status: null,
  label: 'Spring quote',
  parent_id: CART_ID,
  lines_count: 0,
  children_count: 0,
  lines: [],
  members: []
} satisfies Schemas['CartResource']

export const guestCart = {
  ...cart,
  id: '01jcart00000000000000000003',
  type: 'quote',
  status: null,
  label: null,
  owner_type: null,
  owner_id: null,
  session_key: 'guest-session',
  scope_type: null,
  scope_id: null,
  parent_id: null,
  root_id: null,
  lines_count: 0,
  children_count: 0,
  expires_at: '2020-01-01T00:00:00+00:00'
} satisfies Schemas['CartResource']

/** Polycart types the transitions as `Record<string, never>`; it sends each status's next ones. */
const orderTransitions = { open: ['submitted', 'cancelled'], submitted: ['paid', 'cancelled'] } as unknown as Schemas['CartTypeResource']['transitions']

export const orderType = {
  key: 'order',
  statuses: ['open', 'submitted', 'paid', 'cancelled'],
  initial_status: 'open',
  transitions: orderTransitions,
  converts_to: ['quote'],
  parents: null,
  requires_parent: false,
  requires_price: true,
  merges_lines: true,
  lifetime: '30 days'
} satisfies Schemas['CartTypeResource']

export const quoteType = {
  key: 'quote',
  statuses: [],
  initial_status: null,
  transitions: null,
  converts_to: ['order'],
  parents: [],
  requires_parent: true,
  requires_price: false,
  merges_lines: false,
  lifetime: null
} satisfies Schemas['CartTypeResource']

/** A cart as `stats.show` lists it: counted, without its lines and members. */
function summary<T extends { lines?: unknown, members?: unknown }>({ lines, members, ...rest }: T): Omit<T, 'lines' | 'members'> {
  void lines
  void members

  return rest
}

export const stats = {
  by_type: [
    { type: 'order', count: 4 },
    { type: 'quote', count: 0 }
  ],
  recent: [summary(cart), summary(guestCart)]
} satisfies Schemas['CartStatsResource']

/** A cursor page of carts, as `carts.index` and `search.index` answer. */
export function cursorPage<T>(data: T[], next: string | null = null, prev: string | null = null) {
  return {
    data,
    links: { first: null, last: null, prev: null, next: null },
    meta: { path: '/polycart/carts', per_page: 15, next_cursor: next, prev_cursor: prev }
  }
}

/** An `atrium.screens.show` payload for one of Polycart's screens. */
export function screenPayload(key: string, kind: 'list' | 'detail', path: string, record: string | null = null): AtriumIntegrationScreenData {
  return { key, kind, path, subject_type: null, record, open_tab: 'overview', tabs: [], sections: {}, rows: {} }
}

/** The calls `calls` holds for a method and a route template. */
export function requests(calls: readonly PackageCall[], method: string, route: string): PackageCall[] {
  return calls.filter(call => call.method === method && call.route === route)
}
