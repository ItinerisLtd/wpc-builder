import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { test, expect } from '@wordpress/e2e-test-utils-playwright'
import { uploadAndSelectImage } from '../helpers/media.js'
import { openCustomizer, saveCustomizer } from '../helpers/customizer.js'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const TEST_IMAGE = path.join(__dirname, '../fixtures/test-image.png')

test.describe('Image field', () => {
  test('selects, then removes, an image through the media modal, and persists the result', async ({ page, admin }) => {
    await openCustomizer(admin, 'e2e')

    const control = page.locator('#customize-control-hero_image')
    await expect(control).toBeVisible()

    // Not assumed to be empty: a run that failed between selecting an image
    // and removing it again leaves one published, and everything below
    // starts from the empty state.
    const remove = control.locator('.wpc-builder-image__remove')

    if (await remove.isVisible()) {
      await remove.click()
      await saveCustomizer(page)
    }

    await expect(control.locator('.wpc-builder-image__placeholder')).toBeVisible()

    await control.getByRole('button', { name: 'Select image' }).click()
    await uploadAndSelectImage(page, TEST_IMAGE)

    await expect(control.locator('.wpc-builder-image__thumbnail')).toBeVisible()
    await expect(control.getByRole('button', { name: 'Change image' })).toBeVisible()

    await saveCustomizer(page)

    await openCustomizer(admin, 'e2e')
    const reloadedControl = page.locator('#customize-control-hero_image')

    await expect(reloadedControl.locator('.wpc-builder-image__thumbnail')).toBeVisible()

    await reloadedControl.getByRole('button', { name: 'Remove' }).click()
    await expect(reloadedControl.locator('.wpc-builder-image__placeholder')).toBeVisible()

    await saveCustomizer(page)

    await openCustomizer(admin, 'e2e')
    await expect(page.locator('#customize-control-hero_image .wpc-builder-image__placeholder')).toBeVisible()
  })
})
