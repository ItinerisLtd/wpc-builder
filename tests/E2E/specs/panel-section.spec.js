import { test, expect } from '@wordpress/e2e-test-utils-playwright'
import { openCustomizer, saveCustomizer, settingValue } from '../helpers/customizer.js'

test.describe('Section inside a custom panel', () => {
  test('renders the panel and its nested section, and saves a field inside it', async ({ page, admin }) => {
    await openCustomizer(admin, 'footer_social')

    await expect(page.locator('#accordion-panel-footer_panel')).toBeVisible()
    await expect(page.locator('#accordion-section-footer_social')).toBeVisible()

    const control = page.locator('#customize-control-footer_social_url')
    const input = control.locator('input[type="text"]')
    await input.fill('https://example.test/social')
    await input.press('Tab')

    await saveCustomizer(page)

    expect(await settingValue(page, 'footer_social_url')).toBe('https://example.test/social')
  })
})
