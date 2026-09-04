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

/**
 * Sets a native range input's value programmatically and dispatches the
 * events core's setting-link binding listens for: dragging a range slider
 * precisely via mouse is brittle, so this is the reliable path.
 *
 * @param {import('@playwright/test').Locator} locator
 * @param {string} value
 */
async function setRangeValue (locator, value) {
  await locator.evaluate((element, next) => {
    element.value = next
    element.dispatchEvent(new Event('input', { bubbles: true }))
    element.dispatchEvent(new Event('change', { bubbles: true }))
  }, value)
}

test.describe('Field coverage', () => {
  test('Checkbox saves a boolean', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    await page.locator('#customize-control-checkbox_field input[type="checkbox"]').click()
    await saveCustomizer(page)

    expect(await settingValue(page, 'checkbox_field')).toBe(true)
  })

  test('CheckboxToggle saves a boolean', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    await page.locator('#customize-control-checkbox_toggle_field input.wpc-builder-toggle__input').click()
    await saveCustomizer(page)

    expect(await settingValue(page, 'checkbox_toggle_field')).toBe(true)
  })

  test('CheckboxSwitch saves a boolean', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    await page.locator('#customize-control-checkbox_switch_field input.wpc-builder-toggle__input').click()
    await saveCustomizer(page)

    expect(await settingValue(page, 'checkbox_switch_field')).toBe(true)
  })

  test('Toggle saves a boolean', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    await page.locator('#customize-control-toggle_field input.wpc-builder-toggle__input').click()
    await saveCustomizer(page)

    expect(await settingValue(page, 'toggle_field')).toBe(true)
  })

  test('Number saves and clamps within min/max', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    const input = page.locator('#customize-control-number_field input[type="number"]')
    await input.fill('42')
    await input.press('Tab')
    await saveCustomizer(page)

    expect(await settingValue(page, 'number_field')).toBe('42')
  })

  test('Dimensions saves free text', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    const input = page.locator('#customize-control-dimensions_field input.wpc-builder-dimensions__input')
    await input.fill('300px')
    await input.press('Tab')
    await saveCustomizer(page)

    expect(await settingValue(page, 'dimensions_field')).toBe('300px')
  })

  test('Textarea saves free text', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    const textarea = page.locator('#customize-control-textarea_field textarea')
    await textarea.fill('Some textarea content')
    await textarea.press('Tab')
    await saveCustomizer(page)

    expect(await settingValue(page, 'textarea_field')).toBe('Some textarea content')
  })

  test('Url saves a valid URL', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    const input = page.locator('#customize-control-url_field input[type="url"]')
    await input.fill('https://example.test')
    await input.press('Tab')
    await saveCustomizer(page)

    expect(await settingValue(page, 'url_field')).toBe('https://example.test')
  })

  test('Slider saves a numeric value', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    const range = page.locator('#customize-control-slider_field input.wpc-builder-slider__range')
    await setRangeValue(range, '55')
    await saveCustomizer(page)

    expect(await settingValue(page, 'slider_field')).toBe('55')
  })

  test('ColorPalette saves the clicked swatch', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    await page.locator('#customize-control-color_palette_field label.wpc-builder-color-palette__swatch').first().click()
    await saveCustomizer(page)

    expect(await settingValue(page, 'color_palette_field')).toBe('#ff0000')
  })

  test('Radio saves the selected choice', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    await page.locator('#customize-control-radio_field input[type="radio"][value="b"]').click()
    await saveCustomizer(page)

    expect(await settingValue(page, 'radio_field')).toBe('b')
  })

  test('RadioButtonset saves the selected choice', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    await page.locator('#customize-control-radio_buttonset_field label.wpc-builder-radio-buttonset__label', { hasText: 'B' }).click()
    await saveCustomizer(page)

    expect(await settingValue(page, 'radio_buttonset_field')).toBe('b')
  })

  test('Multicheck saves an array of checked values', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    const control = page.locator('#customize-control-multicheck_field')
    await control.locator('input.wpc-builder-multicheck__checkbox[value="a"]').click()
    await control.locator('input.wpc-builder-multicheck__checkbox[value="b"]').click()
    await saveCustomizer(page)

    expect(await settingValue(page, 'multicheck_field')).toEqual(['a', 'b'])
  })

  test('DropdownPages saves the selected page id', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    const select = page.locator('#customize-control-dropdown_pages_field select')
    await select.selectOption({ label: 'Sample Page' })
    await saveCustomizer(page)

    expect(await settingValue(page, 'dropdown_pages_field')).toMatch(/^[1-9]\d*$/)
  })

  test('Select (core components enhanced UI) saves the chosen option', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    const control = page.locator('#customize-control-select_field')
    await control.locator('.wpc-builder-select__enhanced-ui input[role="combobox"]').click()
    await page.getByRole('option', { name: 'B' }).click()
    await saveCustomizer(page)

    expect(await settingValue(page, 'select_field')).toBe('b')
  })

  test('PostSelect (queried choices) saves a real post id', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    const control = page.locator('#customize-control-post_select_field')
    await control.locator('.wpc-builder-select__enhanced-ui input[role="combobox"]').click()
    // Named, not .first(): the deactivated native <select>'s own options
    // briefly remain in the DOM (and briefly visible, before
    // deactivateNativeSelect() runs) matching the same "option" role, and
    // are earlier in document order than the popover's real listbox
    // options. Targeting a known default-seeded post title skips that race.
    await page.getByRole('option', { name: /Hello world/i }).click()
    await saveCustomizer(page)

    expect(await settingValue(page, 'post_select_field')).toMatch(/^[1-9]\d*$/)
  })

  test('Link requires text and URL together and saves the compound value', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    const control = page.locator('#customize-control-link_field')
    await control.locator('input.wpc-builder-link__text').fill('Example link')
    await control.locator('input.wpc-builder-link__url').fill('https://example.test/link')
    await control.locator('input.wpc-builder-link__target').click()
    await control.locator('input.wpc-builder-link__url').press('Tab')

    await saveCustomizer(page)

    // The linked hidden input (and so the client-side setting mirror) holds
    // the URL-encoded JSON string link.js writes, not a parsed object -
    // Fields\Link::decodeRaw() only parses it server-side.
    const raw = await settingValue(page, 'link_field')
    expect(JSON.parse(decodeURIComponent(raw))).toEqual({
      url: 'https://example.test/link',
      text: 'Example link',
      target: '_blank',
    })
  })

  test('Editor saves HTML entered via the Quicktags text tab', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    const control = page.locator('#customize-control-editor_field')
    await control.locator('.wp-switch-editor.switch-html').click()

    const textarea = control.locator('textarea.wpc-builder-editor__textarea')
    await textarea.fill('<p>Editor content</p>')
    await textarea.press('Tab')

    await saveCustomizer(page)

    expect(await settingValue(page, 'editor_field')).toBe('<p>Editor content</p>')
  })

  test('Custom renders its static HTML and registers no setting', async ({ page, admin }) => {
    await openCustomizer(admin, 'field_coverage')

    const control = page.locator('#customize-control-custom_field')
    await expect(control.locator('[data-wpc-builder-e2e="custom_field"]')).toHaveText('Custom field markup')

    expect(await page.evaluate(() => window.wp.customize.has('custom_field'))).toBe(false)
  })
})
