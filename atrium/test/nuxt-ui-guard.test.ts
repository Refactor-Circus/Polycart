import { rawHtmlIn } from '@refactor-circus/atrium/testing'
import { expect, it } from 'vitest'

/*
 * Polycart's module composes Nuxt UI, as Atrium's own components do (D-091,
 * D-093): no page or component renders a raw `<button>`, `<input>`,
 * `<select>`, `<textarea>`, `<table>`, `<details>` or `<a href>`.
 */
it('composes Nuxt UI, with no raw HTML controls (D-091)', () => {
  expect(rawHtmlIn('src')).toEqual([])
})
