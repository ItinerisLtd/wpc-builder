import { randomUUID } from 'node:crypto'
import { expect } from '@wordpress/e2e-test-utils-playwright'

/**
 * Opens the Customizer with a fresh, per-test changeset UUID so a prior
 * spec's unpublished autosave draft (WordPress restores the current
 * user's autosaved changeset by default) never leaks into this test.
 *
 * @param {import('@wordpress/e2e-test-utils-playwright').Admin} admin
 * @param {string} sectionId
 */
export async function openCustomizer (admin, sectionId) {
  await admin.visitAdminPage(
    'customize.php',
    `autofocus[section]=${sectionId}&changeset_uuid=${randomUUID()}`,
  )
}

/**
 * Clicks Save and waits for the changeset request to come back.
 *
 * WordPress disables the Save button as soon as the request starts, so
 * asserting only on `toBeDisabled()` passes mid-flight, and the navigation
 * that usually follows aborts the save. That raced: the reopened Customizer
 * showed the pre-save value.
 *
 * Matched on `customize_changeset_status=publish` rather than the
 * `customize_save` action, which the 60-second changeset autosave also
 * posts. A save that WordPress rejects answers 200 with
 * `{"success": false}`, so the response is asserted with its `data` in the
 * failure message, which is where `setting_validities` lives.
 *
 * A save WordPress blocks client-side never issues a request at all: it
 * rejects in `previewer.save()` when a control carries a notification. That
 * surfaces here as a wait timeout rather than the notification itself. No
 * field in this package adds client-side notifications today.
 *
 * @param {import('@playwright/test').Page} page
 */
export async function saveCustomizer (page) {
  await expect(page.locator('#save')).toBeEnabled()

  const [response] = await Promise.all([
    page.waitForResponse(
      (candidate) => candidate.url().includes('admin-ajax.php')
        && (candidate.request().postData() ?? '').includes('customize_changeset_status=publish'),
    ),
    page.locator('#save').click(),
  ])

  expect(response.ok()).toBe(true)
  const body = await response.json()

  expect(body.success, `save rejected: ${JSON.stringify(body.data)}`).toBe(true)
  await expect(page.locator('#save')).toBeDisabled()
}

/**
 * Reads the live wp.customize() setting value, not the DOM input's
 * displayed value, matching what actually gets sent on save.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} id
 */
export async function settingValue (page, id) {
  return page.evaluate((settingId) => window.wp.customize(settingId)(), id)
}
