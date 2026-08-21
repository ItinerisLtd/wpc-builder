import { test, expect } from '@wordpress/e2e-test-utils-playwright'
import { openCustomizer } from '../helpers/customizer.js'

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

test.describe('Color field', () => {
  test('accepts a stored rgba() until the picker changes it, then loses its alpha channel (docs/known-limitations.md)', async ({ page, admin }) => {
    await openCustomizer(admin, 'e2e')

    const control = page.locator('#customize-control-accent_color')
    await expect(control).toBeVisible()

    // on load
    expect(await settingValue(page, 'accent_color')).toBe('rgba(12,34,56,0.5)')

    // picker opened, then closed without choosing
    const toggle = control.locator('.wp-color-result')
    await toggle.click()
    await expect(control.locator('.iris-picker')).toBeVisible()
    await toggle.click()
    await expect(control.locator('.iris-picker')).toBeHidden()

    expect(await settingValue(page, 'accent_color')).toBe('rgba(12,34,56,0.5)')

    // after typing #abcdef: core's WP_Customize_Color_Control is hex-only
    // (maxlength="7"), so the stored rgba() is gone the moment it's touched.
    await toggle.click()
    const hexInput = control.locator('.color-picker-hex')
    await hexInput.fill('#abcdef')
    await hexInput.press('Tab')

    await expect.poll(() => settingValue(page, 'accent_color')).toBe('#abcdef')
  })
})
