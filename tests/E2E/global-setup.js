import { execSync } from 'node:child_process'
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright'
import { STORAGE_STATE_PATH } from './storage-state.js'

/**
 * Resets stored theme mods, then logs in once via the REST API and writes
 * the resulting cookies to disk, so every spec's `page` fixture starts
 * already authenticated as admin instead of each spec repeating a login
 * flow.
 *
 * wp-env's WordPress data persists across local runs (only `wp-env
 * destroy` wipes it), unlike CI's always-fresh install: without this
 * reset, a field already saved to its test target value from a prior
 * local run looks unchanged to the Customizer's dirty-check, and Save
 * never re-enables. Living here, rather than in a wrapper script, means
 * every invocation picks it up, including `npx playwright test` run
 * directly, a `-g` filter, or UI mode.
 */
export default async function globalSetup() {
  execSync('npx wp-env run cli wp theme mod remove --all', { stdio: 'inherit' })

  const requestUtils = await RequestUtils.setup({
    storageStatePath: STORAGE_STATE_PATH,
  })

  await requestUtils.setupRest()
}
