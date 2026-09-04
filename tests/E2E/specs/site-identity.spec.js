import { test, expect } from '@wordpress/e2e-test-utils-playwright'
import { openCustomizer, saveCustomizer } from '../helpers/customizer.js'

/**
 * Reads the live wp.customize() setting value, not the DOM input's
 * displayed value, matching what actually gets sent on save.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} id
 */
async function settingValue (page, id) {
  return page.evaluate((settingId) => window.wp.customize(settingId)(), id)
}

test.describe('Extending the core Site Identity section', () => {
  test('adds a custom field alongside core controls and saves it', async ({ page, admin }) => {
    await openCustomizer(admin, 'title_tagline')

    // Proves register() detected 'title_tagline' already exists and skipped
    // add_section(), while still registering the field against it: both a
    // core control and the package's own field render in the same section.
    await expect(page.locator('#customize-control-blogname')).toBeVisible()

    const control = page.locator('#customize-control-site_icon_note')
    await expect(control).toBeVisible()

    const input = control.locator('input[type="text"]')
    await input.fill('Custom site identity note')
    await input.press('Tab')

    await saveCustomizer(page)

    expect(await settingValue(page, 'site_icon_note')).toBe('Custom site identity note')
  })
})
