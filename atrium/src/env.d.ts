/// <reference types="vite/client" />
import type { paths } from '../../client/src/schema'

// Types `useAtriumIntegration('polycart')`, `useIntegrationQuery('polycart', …)` and
// `mockPackageApi('polycart', …)` by Polycart's API.
declare module '@refactor-circus/atrium' {
  interface AtriumIntegrationPaths {
    polycart: paths
  }
}
