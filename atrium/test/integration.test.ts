import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { describe, expect, it } from 'vitest'
import polycart, { screens } from '../src/atrium'
import PolycartCartsByType from '../src/components/PolycartCartsByType.vue'
import PolycartRecentCarts from '../src/components/PolycartRecentCarts.vue'
import { searchPolycartCarts } from '../src/components/search'
import fixtureJson from '@refactor-circus/atrium/fixtures/integrations/polycart.json'
import locale from '../src/locales/polycart.en.json'
import { withSetup } from '@refactor-circus/atrium/testing'
import { hasI18nKey, missingI18nKeys } from '@refactor-circus/atrium/testing'
import { useRouter } from '@refactor-circus/atrium/testing'

// Read through a variable: Vite rewrites `new URL('…', import.meta.url)`
// into a served asset URL, which `fileURLToPath()` refuses.
const here = import.meta.url

/*
 * The Polycart integration's layer side (AC 49, 53, 59, 60, 62): every
 * screen and nav path PHP declares (`fixtures/integrations/polycart.json`,
 * written by `PolycartIntegrationTest`) has its page; `src/atrium.ts` maps each
 * screen key to that page's route and registers a component for every widget
 * and a resolver for every search source the fixture lists; and every `t()`
 * key it uses exists.
 */

interface Declarations {
  key: string
  navigation: { link: { path: string, external: boolean } | null }[]
  widgets: { key: string, component: string, data: Record<string, unknown> }[]
  settings: unknown
  search_sources: { key: string, integration: string }[]
  screens: { key: string, kind: string, path: string, subject_type: string | null }[]
}

const fixture = fixtureJson as unknown as Declarations

const indexSource = readFileSync(fileURLToPath(new URL('../src/atrium.ts', here)), 'utf8')

/** A Nuxt route path as PHP declares it: `:param()` read as `:param`. */
function declaredPath(path: string): string {
  return path.replace(/\(\)/g, '')
}

describe('the polycart fixture', () => {
  it('declares the three screens of AC 59, two widgets and one search source', () => {
    expect(fixture.key).toBe('polycart')
    expect(fixture.screens.map(screen => [screen.key, screen.kind, screen.path])).toEqual([
      ['polycart.carts', 'list', '/polycart/carts'],
      ['polycart.cart', 'detail', '/polycart/carts/:cart'],
      ['polycart.types', 'list', '/polycart/types']
    ])
    expect(fixture.navigation.map(item => item.link?.path)).toEqual(['/polycart/carts', '/polycart/types'])
    expect(fixture.widgets.map(widget => [widget.key, widget.component, widget.data])).toEqual([
      ['polycart.carts-by-type', 'polycart.carts-by-type', {}],
      ['polycart.recent-carts', 'polycart.recent-carts', {}]
    ])
    expect(fixture.settings).toBeNull()
    expect(fixture.search_sources.map(source => [source.key, source.integration])).toEqual([['polycart', 'polycart']])
  })

  it('has a screens entry in src/atrium.ts for every screen key, and nothing else', () => {
    expect(Object.keys(screens).sort()).toEqual(fixture.screens.map(screen => screen.key).sort())
  })

  it('has a page for every screen, at the path PHP declares', async () => {
    const routes = (await withSetup(() => useRouter())).getRoutes()

    for (const screen of fixture.screens) {
      const route = routes.find(candidate => candidate.name === screens[screen.key])

      expect(route, screen.key).toBeDefined()
      expect(declaredPath(route!.path), screen.key).toBe(screen.path)
    }
  })

  it('has a page for every nav link', async () => {
    const router = await withSetup(() => useRouter())

    expect(fixture.navigation.map(item => router.resolve(item.link!.path).name)).toEqual([
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
  it('registers a component for each fixture widget and a resolver for each fixture search source, and nothing else', () => {
    expect(Object.keys(polycart.widgets ?? {})).toEqual(fixture.widgets.map(widget => widget.component))
    expect(polycart.widgets?.['polycart.carts-by-type']).toBe(PolycartCartsByType)
    expect(polycart.widgets?.['polycart.recent-carts']).toBe(PolycartRecentCarts)
    expect(Object.keys(polycart.searchSources ?? {})).toEqual(fixture.search_sources.map(source => source.key))
    expect(polycart.searchSources?.['polycart']).toBe(searchPolycartCarts)
    expect(Object.keys(polycart.extensions ?? {})).toEqual([])
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
