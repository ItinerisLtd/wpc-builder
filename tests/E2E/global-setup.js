import { RequestUtils } from '@wordpress/e2e-test-utils-playwright'
import { STORAGE_STATE_PATH } from './storage-state.js'

/**
 * Logs in once via the REST API and writes the resulting cookies to disk,
 * so every spec's `page` fixture starts already authenticated as admin
 * instead of each spec repeating a login flow.
 */
export default async function globalSetup() {
  const requestUtils = await RequestUtils.setup({
    storageStatePath: STORAGE_STATE_PATH,
  })

  await requestUtils.setupRest()
}
