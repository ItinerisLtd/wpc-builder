import { expect } from '@wordpress/e2e-test-utils-playwright'

/**
 * Uploads and selects an image through the real `wp.media()` modal, the
 * shared interaction both `Fields\Image` and a Repeater image sub-field
 * use (`assets/src/js/image.js`, `assets/src/js/repeater.js`).
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} filePath Absolute path to the image to upload.
 */
export async function uploadAndSelectImage (page, filePath) {
  const modal = page.locator('.media-modal')
  await modal.waitFor({ state: 'visible' })

  await modal.getByRole('tab', { name: 'Upload files' }).click()
  await modal.locator('input[type="file"]').setInputFiles(filePath)

  // A freshly-uploaded attachment is selected automatically; clicking it
  // again would toggle it back off.
  const selectButton = modal.getByRole('button', { name: 'Select', exact: true })
  await expect(selectButton).toBeEnabled({ timeout: 30_000 })
  await selectButton.click()
  await modal.waitFor({ state: 'hidden' })
}
