import type { RouteRecordRaw } from 'vue-router'

/**
 * Polycart's pages, added to Atrium's router before its first navigation.
 * Names follow Nuxt's file-based ones (`pages/{pkg}/runs/[run].vue` ->
 * `{pkg}-runs-run`), which the screen declarations name.
 */
export const routes: RouteRecordRaw[] = [
  { path: '/polycart/carts/:cart', name: 'polycart-carts-cart', component: () => import('./pages/carts/[cart].vue') },
  { path: '/polycart/carts', name: 'polycart-carts', component: () => import('./pages/carts/index.vue') },
  { path: '/polycart/types', name: 'polycart-types', component: () => import('./pages/types.vue') }
]
