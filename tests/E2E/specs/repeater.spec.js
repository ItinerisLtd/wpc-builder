import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { test, expect } from '@wordpress/e2e-test-utils-playwright'
import { uploadAndSelectImage } from '../helpers/media.js'
import { openCustomizer, saveCustomizer } from '../helpers/customizer.js'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const TEST_IMAGE = path.join(__dirname, '../fixtures/test-image.png')

/**
 * Reads the live wp.customize() setting value. Its shape differs by
 * origin: freshly loaded from the server, `Fields\Repeater`'s
 * `defaultSanitizeCallback()` stores (and PHP therefore localises) a
 * genuine nested array; once `repeater.js`'s own `persist()` writes to
 * the linked hidden input, the browser-side setting becomes the
 * `encodeURIComponent(JSON.stringify(rows))` string that input carries.
 * Both are handled here so the helper works before and after a reload,
 * mirroring `repeater.js`'s own `decode()`: a malformed or non-array
 * value normalises to `[]` rather than throwing.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} id
 */
async function readRows (page, id) {
  const raw = await page.evaluate((settingId) => window.wp.customize(settingId)(), id)

  if (Array.isArray(raw)) {
    return raw
  }

  if (!raw) {
    return []
  }

  try {
    const parsed = JSON.parse(decodeURIComponent(raw))

    return Array.isArray(parsed) ? parsed : []
  } catch {
    return []
  }
}

test.describe('Repeater field', () => {
  test('adds, fills, drags to reorder, removes a row, and persists the result', async ({ page, admin }) => {
    await openCustomizer(admin, 'e2e')

    const control = page.locator('#customize-control-gallery')
    await expect(control).toBeVisible()

    // Not assumed to be 0: a real changeset only diffs against whatever
    // is already live, so this test adds relative to it and removes
    // everything it added again at the end, leaving the field as it
    // found it.
    const rows = control.locator('.wpc-builder-repeater__row')
    const before = await rows.count()

    await control.getByRole('button', { name: 'Add row' }).click()
    await control.getByRole('button', { name: 'Add row' }).click()
    await control.getByRole('button', { name: 'Add row' }).click()
    await expect(rows).toHaveCount(before + 3)

    const [first, second, third] = [before, before + 1, before + 2]

    await rows.nth(first).locator('.wpc-builder-repeater__row-header').click()
    await rows.nth(first).locator('[data-field="caption"]').fill('First')

    await rows.nth(first).getByRole('button', { name: 'Add image' }).click()
    await uploadAndSelectImage(page, TEST_IMAGE)
    await expect(rows.nth(first).locator('.wpc-builder-repeater__image-preview img')).toBeVisible()

    await rows.nth(second).locator('.wpc-builder-repeater__row-header').click()
    await rows.nth(second).locator('[data-field="caption"]').fill('Second')

    await rows.nth(third).locator('.wpc-builder-repeater__remove').click()
    await expect(rows).toHaveCount(before + 2)

    let currentRows = await readRows(page, 'gallery')
    expect(currentRows[first]).toMatchObject({ caption: 'First' })
    expect(currentRows[second]).toMatchObject({ caption: 'Second' })

    // Drag the last row's handle above the one before it to reorder.
    await rows.nth(second).locator('.wpc-builder-repeater__handle').dragTo(rows.nth(first))

    currentRows = await readRows(page, 'gallery')
    expect(currentRows[first]).toMatchObject({ caption: 'Second' })
    expect(currentRows[second]).toMatchObject({ caption: 'First' })

    await saveCustomizer(page)

    await openCustomizer(admin, 'e2e')
    let reloadedControl = page.locator('#customize-control-gallery')
    await expect(reloadedControl).toBeVisible()

    const persistedRows = await readRows(page, 'gallery')
    expect(persistedRows).toHaveLength(before + 2)
    expect(persistedRows[first]).toMatchObject({ caption: 'Second' })
    expect(persistedRows[second].caption).toBe('First')
    expect(persistedRows[second].photo).toBeTruthy()

    // Clean up: remove every row this test added, publish, and confirm
    // the field is back to where it started, so a repeated local run
    // isn't polluted by this run's published rows.
    const reloadedRows = reloadedControl.locator('.wpc-builder-repeater__row')

    for (let i = before; i < before + 2; i++) {
      await reloadedRows.nth(before).locator('.wpc-builder-repeater__remove').click()
    }

    await expect(reloadedRows).toHaveCount(before)

    await saveCustomizer(page)
  })
})
