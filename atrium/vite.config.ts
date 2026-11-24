import { readdirSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { ATRIUM_SDK_ROOT, atriumComponentResolver, atriumModule } from '@refactor-circus/atrium/vite'
import Components from 'unplugin-vue-components/vite'

// Polycart's Atrium module: `pnpm build` writes ../dist/atrium (committed), `pnpm test` runs Vitest.
const config = atriumModule({
  key: 'polycart',
  outDir: '../dist/atrium',
  inputs: ['src', '../client/src', 'package.json', 'vite.config.ts', 'pnpm-lock.yaml'],
  test: { setupFiles: ['test/setup.ts'] }
})

/*
 * Tests only, until the SDK's preset covers it: the SDK's own `.vue` files use
 * `<Atrium*>` and `<U*>` without importing them, and the preset's test config
 * resolves those tags only outside node_modules. Installed from GitHub, the SDK
 * sits in node_modules, so this resolves the tags in the SDK's files alone.
 */
function sdkComponents() {
  const runtime = join(dirname(fileURLToPath(import.meta.resolve('@nuxt/ui/unplugin'))), 'runtime')
  const index = (dir: string) => new Map(
    (readdirSync(join(runtime, dir), { recursive: true }) as string[])
      .filter(file => file.endsWith('.vue'))
      .map(file => [`U${file.split('/').pop()!.slice(0, -'.vue'.length)}`, join(runtime, dir, file)])
  )
  const overrides = index('vue/components')
  const components = index('components')
  const sdk = join(ATRIUM_SDK_ROOT, 'src').replace(/[.*+?^${}()|[\]\\]/g, '\\$&')

  // Named apart: Nuxt UI refuses a second plugin called unplugin-vue-components.
  return { ...Components({
    dts: false,
    dirs: [],
    include: [new RegExp(`^${sdk}[\\\\/].*\\.vue$`)],
    exclude: [],
    resolvers: [
      atriumComponentResolver({ ui: false, direct: true }),
      (name: string) => {
        const from = overrides.get(name) ?? components.get(name)

        return from ? { name: 'default', from } : undefined
      }
    ]
  }), name: 'polycart:atrium-sdk-components' }
}

export default process.env.VITEST
  ? { ...config, plugins: [...(config.plugins ?? []), sdkComponents()] }
  : config
