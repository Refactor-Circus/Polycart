import { ref } from 'vue'
import type { components, operations } from '../../../client/src/schema'
import { AtriumIntegrationError, useAtriumIntegration, type AtriumIntegrationClient } from '@refactor-circus/atrium'
import { atriumStatus } from '@refactor-circus/atrium'

/*
 * Shared pieces of Polycart's screens and widgets: the payload types from the
 * package's generated `client/src/schema.d.ts`, the client, a runner for changes, and the
 * display rules v1 kept in `Atrium\Format` and `Atrium\Badges`.
 */

type Schemas = components['schemas']

export type PolycartClient = AtriumIntegrationClient<'polycart'>
export type PolycartCart = Schemas['CartResource']
export type PolycartLine = Schemas['CartLineResource']
export type PolycartMember = Schemas['CartMemberResource']
export type PolycartType = Schemas['CartTypeResource']
export type PolycartStats = Schemas['CartStatsResource']
export type PolycartVisibility = Schemas['Visibility']

/** `carts.index`'s query parameters. */
export type PolycartCartsQuery = NonNullable<operations['polycart.carts.index']['parameters']['query']>

/** `carts.update`'s body; its `meta` is any JSON object, whatever the schema says. */
export type PolycartUpdateCart = Schemas['UpdateCartRequest']

/** Who may see a cart without being shared in, as Polycart's `Visibility` enum lists them. */
export const POLYCART_VISIBILITIES: readonly PolycartVisibility[] = ['private', 'scope', 'boundary']

/** A cursor page, as `carts.index` and `search.index` answer. */
export interface PolycartCursorPage<T> {
  data: T[]
  meta: { next_cursor: string | null, prev_cursor: string | null }
}

/**
 * The Polycart client, signed by the app's auth adapter; null while the
 * integration is not active (the page then shows as unavailable).
 */
export function usePolycart(): PolycartClient | null {
  return useAtriumIntegration('polycart').client
}

/** The layer path of a cart's page. */
export function polycartCartPath(id: string): string {
  return `/polycart/carts/${encodeURIComponent(id)}`
}

/** A cart's name: its label, or its id. */
export function polycartCartTitle(cart: Pick<PolycartCart, 'id' | 'label'>): string {
  return cart.label || cart.id
}

/** Whether a cart's lifetime has run out. */
export function polycartExpired(cart: Pick<PolycartCart, 'expires_at'>, now: Date = new Date()): boolean {
  return cart.expires_at !== null && new Date(cart.expires_at).getTime() <= now.getTime()
}

/** The last segment of a morph class (`App\Models\User` as `User`), as v1's `class_basename()`. */
export function polycartBasename(type: string): string {
  return type.split('\\').pop() || type
}

/** Who a cart belongs to: `User #7`, a guest session (`null`: say "Guest"), or nobody (`—`). */
export function polycartOwner(cart: Pick<PolycartCart, 'owner_type' | 'owner_id' | 'session_key'>): string | null {
  if (cart.owner_type !== null) {
    return `${polycartBasename(cart.owner_type)} #${cart.owner_id ?? ''}`
  }

  return cart.session_key === null ? '—' : null
}

/** Minor units as a decimal amount, or a dash when there is no price, as v1's `Format::money()`. */
export function polycartMoney(minor: number | null | undefined): string {
  if (minor === null || minor === undefined) {
    return '—'
  }

  return (minor / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

export type PolycartStatusColor = 'info' | 'success' | 'warning' | 'error' | 'neutral' | 'primary'

const STATUS_COLORS: Record<Exclude<PolycartStatusColor, 'primary'>, readonly string[]> = {
  info: ['pending', 'submitted', 'awaiting', 'awaiting_approval', 'awaiting_payment', 'awaiting_confirmation', 'in_review', 'review', 'requested', 'quoted'],
  success: ['open', 'active', 'accepted', 'approved', 'complete', 'completed', 'checked_out', 'checkout', 'paid', 'fulfilled', 'delivered', 'shipped', 'won', 'done'],
  warning: ['on_hold', 'held', 'hold', 'paused', 'suspended', 'backordered', 'disputed'],
  error: ['declined', 'rejected', 'cancelled', 'canceled', 'failed', 'refused', 'revoked', 'void', 'voided', 'lost'],
  neutral: ['expired', 'abandoned', 'merged', 'converted', 'closed', 'archived', 'refunded']
}

/**
 * The colour of a status, read by meaning as v1's `Badges` did: `info` for
 * pending, `success` done or active, `warning` held, `error` failed or
 * refused, `neutral` over, and `primary` for anything else. An expired cart
 * is over, whatever its status.
 */
export function polycartStatusColor(status: string | null, expired = false): PolycartStatusColor {
  if (expired) {
    return 'neutral'
  }

  const key = (status ?? '').trim().toLowerCase().replace(/[\s-]/g, '_')

  for (const [color, statuses] of Object.entries(STATUS_COLORS)) {
    if (statuses.includes(key)) {
      return color as PolycartStatusColor
    }
  }

  return 'primary'
}

/**
 * The statuses a cart of `type` may move to from `current`: the type's
 * transitions when it declares them, otherwise any other of its statuses.
 */
export function polycartNextStatuses(type: PolycartType | null, current: string | null): string[] {
  if (!type || type.statuses.length === 0) {
    return []
  }

  const transitions = type.transitions as Record<string, string[] | undefined> | null

  if (transitions === null) {
    return type.statuses.filter(status => status !== current)
  }

  return current === null ? [] : [...(transitions[current] ?? [])]
}

/** The data of an `openapi-fetch` answer, or an `AtriumIntegrationError` carrying its status and body. */
export function polycartData<T>(result: { data?: unknown, error?: unknown, response: Response }): T {
  if (!result.response.ok) {
    throw new AtriumIntegrationError(result.response.status, result.error)
  }

  return result.data as T
}

/**
 * A value the vendored schema types too narrowly, sent as Polycart validates
 * it: a cart's `meta` is any JSON object (typed `string[]`), and `unexpired`
 * a Laravel boolean, which `1` passes and the string `true` does not.
 */
export function polycartLoosely<T>(value: unknown): T {
  return value as T
}

/**
 * Runs a change against Polycart. A 422 fills `errors` (by field) or, for a
 * refusal of the cart's type, `message`; a 403 sets `forbidden`; any other
 * failure sets `message`. `run()` resolves whether the change succeeded, so
 * the screen reloads only then.
 */
export function usePolycartAction() {
  const errors = ref<Record<string, string[]>>({})
  const message = ref<string | null>(null)
  const forbidden = ref(false)
  const busy = ref(false)

  async function run(action: () => Promise<unknown>): Promise<boolean> {
    busy.value = true
    errors.value = {}
    message.value = null
    forbidden.value = false

    try {
      await action()
      return true
    } catch (error) {
      const status = atriumStatus(error)
      const body = (error instanceof AtriumIntegrationError ? error.body : null) as { message?: unknown, errors?: Record<string, string[]> } | null

      if (status === 403) {
        forbidden.value = true
      } else if (status === 422 && body?.errors && Object.keys(body.errors).length > 0) {
        errors.value = body.errors
      } else {
        message.value = typeof body?.message === 'string' ? body.message : error instanceof Error ? error.message : String(error)
      }

      return false
    } finally {
      busy.value = false
    }
  }

  /** The first error of `field`, or of any of its entries (`lines.0.quantity` for `lines`). */
  function error(field: string): string | undefined {
    const exact = errors.value[field]?.[0]

    if (exact) {
      return exact
    }

    const nested = Object.entries(errors.value).find(([key]) => key.startsWith(`${field}.`))

    return nested?.[1]?.[0]
  }

  return { errors, message, forbidden, busy, run, error }
}
