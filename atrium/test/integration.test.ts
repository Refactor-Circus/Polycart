import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { describe, expect, it } from 'vitest'
import polycart, { screens } from '../src/atrium'
import PolycartCartsByType from '../src/components/PolycartCartsByType.vue'
import PolycartRecentCarts from '../src/components/PolycartRecentCarts.vue'
import { searchPolycartCarts } from '../src/components/search'
import locale from '../src/locales/polycart.en.json'
import { expectIntegrationManifest, withSetup } from '@refactor-circus/atrium/testing'
import { hasI18nKey, missingI18nKeys } from '@refactor-circus/atrium/testing'
import { useRouter } from '@refactor-circus/atrium/testing'

// Read through a variable: Vite rewrites `new URL('…', import.meta.url)`
// into a served asset URL, which `fileURLToPath()` refuses.
const here = import.meta.url

/*
 * Polycart's integration manifest (`src/integration.json`, D-090) and the
 * module that renders it (AC 49, 53, 59, 60, 62): the manifest passes the
 * checks Atrium makes when it registers it; every screen and nav path it
 * declares has its page; `src/atrium.ts` maps each screen key to that page's
 * route and registers a component for every widget and a resolver for every
 * search source it lists; and every `t()` key the module uses exists.
 */

interface Declarations {
  key: string
  version: number
  features: string[]
  navigation: { path: string }[]
  widgets: { key: string, component?: string }[]
  settings?: unknown
  search_sources: { key: string }[]
  screens: { key: string, kind: string, path: string, subject_type?: string }[]
  hooks?: { tabs?: Record<string, unknown>, sections?: Record<string, unknown> }[]
}

const manifest = expectIntegrationManifest('src/integration.json', 'polycart') as unknown as Declarations

const indexSource = readFileSync(fileURLToPath(new URL('../src/atrium.ts', here)), 'utf8')

/** A route's path as the manifest writes it: `:param()` read as `:param`. */
function declaredPath(path: string): string {
  return path.replace(/\(\)/g, '')
}

describe('the polycart manifest', () => {
  it('declares the three screens of AC 59, two widgets and one search source', () => {
    expect(manifest.key).toBe('polycart')
    expect(manifest.version).toBe(1)
    expect(manifest.features).toEqual(['polycart.atrium'])
    expect(manifest.screens.map(screen => [screen.key, screen.kind, screen.path])).toEqual([
      ['polycart.carts', 'list', '/polycart/carts'],
      ['polycart.cart', 'detail', '/polycart/carts/:cart'],
      ['polycart.types', 'list', '/polycart/types']
    ])
    expect(manifest.navigation.map(item => item.path)).toEqual(['/polycart/carts', '/polycart/types'])
    expect(manifest.widgets.map(widget => widget.component ?? widget.key)).toEqual([
      'polycart.carts-by-type',
      'polycart.recent-carts'
    ])
    expect(manifest.settings).toBeUndefined()
    expect(manifest.hooks).toBeUndefined()
    expect(manifest.search_sources.map(source => source.key)).toEqual(['polycart'])
  })

  it('has a screens entry in src/atrium.ts for every screen key, and nothing else', () => {
    expect(Object.keys(screens).sort()).toEqual(manifest.screens.map(screen => screen.key).sort())
  })

  it('has a page for every screen, at the path the manifest declares', async () => {
    const routes = (await withSetup(() => useRouter())).getRoutes()

    for (const screen of manifest.screens) {
      const route = routes.find(candidate => candidate.name === screens[screen.key])

      expect(route, screen.key).toBeDefined()
      expect(declaredPath(route!.path), screen.key).toBe(screen.path)
    }
  })

  it('has a page for every nav link', async () => {
    const router = await withSetup(() => useRouter())

    expect(manifest.navigation.map(item => router.resolve(item.path).name)).toEqual([
      screens['polycart.carts'],
      screens['polycart.types']
    ])
  })

  it('resolves a cart\'s path to the cart page', async () => {
    const router = await withSetup(() => useRouter())

    expect(router.resolve('/polycart/carts/01jcart').name).toBe(screens['polycart.cart'])
  })
})

describe('the polycart registrations', () => {
  it('registers a component for each manifest widget and a resolver for each manifest search source, and nothing else', () => {
    expect(Object.keys(polycart.widgets ?? {})).toEqual(manifest.widgets.map(widget => widget.component ?? widget.key))
    expect(polycart.widgets?.['polycart.carts-by-type']).toBe(PolycartCartsByType)
    expect(polycart.widgets?.['polycart.recent-carts']).toBe(PolycartRecentCarts)
    expect(Object.keys(polycart.searchSources ?? {})).toEqual(manifest.search_sources.map(source => source.key))
    expect(polycart.searchSources?.['polycart']).toBe(searchPolycartCarts)
    expect(Object.keys(polycart.extensions ?? {})).toEqual((manifest.hooks ?? []).flatMap(hook => [...Object.keys(hook.tabs ?? {}), ...Object.keys(hook.sections ?? {})]))
    expect(Object.keys(polycart.settingsPanels ?? {})).toEqual([])
    expect(Object.keys(polycart.liveResolvers ?? {})).toEqual([])
  })

  it('lists what it does not port at the top of src/atrium.ts, operator mode included (D-032)', () => {
    expect(indexSource.startsWith('// Not ported (D-032)')).toBe(true)
    expect(indexSource).toContain('polycart.atrium.show_all')
  })
})

describe('the polycart strings', () => {
  it('uses only t() keys its locale files hold', () => {
    expect(missingI18nKeys('polycart')).toEqual([])
  })

  it('carries v1\'s notices, confirmations and the visibility of each Visibility case', () => {
    for (const key of [
      'polycart.cart.updated', 'polycart.cart.deleted', 'polycart.cart.cleared', 'polycart.cart.status_changed',
      'polycart.cart.converted', 'polycart.cart.visibility_changed', 'polycart.cart.line_updated',
      'polycart.cart.line_removed', 'polycart.cart.shared', 'polycart.cart.unshared',
      'polycart.cart.delete_confirm', 'polycart.cart.clear_confirm',
      'polycart.visibility.private', 'polycart.visibility.scope', 'polycart.visibility.boundary'
    ]) {
      expect(hasI18nKey(locale, key), key).toBe(true)
    }
  })
})
