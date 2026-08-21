import { test, expect } from '@wordpress/e2e-test-utils-playwright'
import { openCustomizer } from '../helpers/customizer.js'

test.describe('Selective refresh (live preview)', () => {
  test('updates the preview via a partial refresh, not a full iframe reload', async ({ page, admin }) => {
    await openCustomizer(admin, 'e2e')

    const previewFrameElement = page.locator('#customize-preview iframe')
    const preview = page.frameLocator('#customize-preview iframe')

    await expect(preview.locator('[data-wpc-builder-e2e="live_message"]')).toHaveText('original')

    // A real navigation would wipe this marker; a selective-refresh
    // partial swap (innerHTML only) leaves the rest of the document,
    // and this global, untouched.
    await previewFrameElement.evaluate((iframe) => {
      iframe.contentWindow.__wpcBuilderE2eMarker = true
    })

    const control = page.locator('#customize-control-live_message')
    const input = control.locator('input[type="text"]')
    await input.fill('updated live')
    await input.press('Tab')

    // Core's selective-refresh briefly keeps the outgoing partial in the
    // DOM (fading it out via `.customize-partial-refreshing`) alongside
    // the incoming one, so two elements can match for a moment; the
    // freshly-inserted one is always last.
    await expect(preview.locator('[data-wpc-builder-e2e="live_message"]').last()).toHaveText('updated live')

    const markerSurvived = await previewFrameElement.evaluate(
      (iframe) => iframe.contentWindow.__wpcBuilderE2eMarker === true,
    )

    expect(markerSurvived).toBe(true)
  })
})
