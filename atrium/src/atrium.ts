// Not ported (D-032):
// - Operator mode (`polycart.atrium.show_all`): v1's screens, widgets and
//   search listed every cart for an operator. Polycart's API lists only the
//   carts the user can access, and has no operator route.
// - Hiding each control the user may not use (v1's `@polycartCan` checks):
//   no v2 route reports a cart's abilities, so every control shows and
//   Polycart's 403 answers with the no-access notice (D-031).
// - The share form's pickers: v1 offered the configured owner and scope
//   aliases (`polycart.owners`, `polycart.scopes`) as member types and the
//   cart type's roles. No v2 route lists either, so both are text fields, and
//   the form shows for every scoped cart (v1 also asked the type whether it
//   is shareable); Polycart's 422 refuses what v1 never offered.
// - A move or conversion the user may not make: v1 offered only those the
//   policy allowed; the screen offers what the type allows, and Polycart's
//   403 refuses the rest.
// - The cart's full scope path (v1's `paths`, every ancestor scope): the
//   cart resource carries only its own scope, which the screen shows.
// - A line's options (v1 listed them): `CartLineResource.options` is typed
//   as an empty object, so the screen shows only the purchasable, or "Custom".
// - The `row.actions` section slot on each cart and type row: `atrium.screens.show`
//   loads no records, so a row is not something other packages can extend.
//   The screens' tabs and their `header.actions`, `before`, `aside` and
//   `after` sections still render.
// - The cart type's PHP class under its key on the types screen:
//   `CartTypeResource` does not carry it.

import PolycartCartsByType from './components/PolycartCartsByType.vue'
import PolycartRecentCarts from './components/PolycartRecentCarts.vue'
import { searchPolycartCarts } from './components/search'
import './atrium.css'
import { defineAtriumModule, mergeAtriumMessages } from '@refactor-circus/atrium'
import polycart_en from './locales/polycart.en.json'
import { routes } from './routes'

/**
 * Polycart (refactor-circus/polycart): carts and cart types. Its pages call
 * Polycart's API through `useAtriumIntegration('polycart')`; its two widgets
 * share one `stats.show` request per dashboard load; its search source runs
 * in ⌘K. It declares no settings panel, extension or live nav resolver.
 */
export const screens: Record<string, string> = {
  'polycart.carts': 'polycart-carts',
  'polycart.cart': 'polycart-carts-cart',
  'polycart.types': 'polycart-types'
}

/**
 * Polycart's Atrium module (D-082): everything above, registered while the
 * polycart integration is active.
 */
export default defineAtriumModule({
  key: 'polycart',
  routes,
  screens,
  widgets: {
    'polycart.carts-by-type': PolycartCartsByType,
    'polycart.recent-carts': PolycartRecentCarts
  },
  searchSources: {
    'polycart': searchPolycartCarts
  },
  messages: { en: mergeAtriumMessages(polycart_en) }
})
