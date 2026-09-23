// @vitest-environment jsdom
import { describe, expect, it, vi } from 'vitest'

function buildControlApiMock () {
  const control = vi.fn()

  control.each = vi.fn()
  control.bind = vi.fn()

  return control
}

async function loadTabsJs () {
  vi.resetModules()

  const control = buildControlApiMock()

  window.wp = { customize: { control } }

  await import('./tabs.js')

  return control
}

describe('module initialisation', () => {
  it('registers its control-embed listeners on load', async () => {
    const control = await loadTabsJs()

    expect(control.each).toHaveBeenCalledTimes(1)
    expect(control.bind).toHaveBeenCalledWith('add', expect.any(Function))

    delete window.wp
  })

  it('ignores a non-wpc-builder-tabs control and never registers embedded.done()', async () => {
    const control = await loadTabsJs()
    const bindWhenEmbedded = control.each.mock.calls[0][0]
    const embedded = { done: vi.fn() }

    bindWhenEmbedded({ params: { type: 'text' }, deferred: { embedded } })

    expect(embedded.done).not.toHaveBeenCalled()

    delete window.wp
  })

  it('wires a control exactly once via the per-control guard', async () => {
    const control = await loadTabsJs()
    const bindWhenEmbedded = control.each.mock.calls[0][0]
    const embedded = { done: vi.fn() }
    const wpControl = { params: { type: 'wpc-builder-tabs' }, deferred: { embedded } }

    bindWhenEmbedded(wpControl)
    bindWhenEmbedded(wpControl)

    expect(wpControl._wpcBuilderTabsInit).toBe(true)
    expect(embedded.done).toHaveBeenCalledTimes(1)

    delete window.wp
  })

  it('does nothing when the control container is missing', async () => {
    const control = await loadTabsJs()
    const bindWhenEmbedded = control.each.mock.calls[0][0]
    const embedded = { done: vi.fn() }

    bindWhenEmbedded({ params: { type: 'wpc-builder-tabs' }, deferred: { embedded } })

    expect(() => embedded.done.mock.calls[0][0]()).not.toThrow()

    delete window.wp
  })
})

describe('tab-switching behaviour', () => {
  function buildMenuContainer () {
    const container = document.createElement('div')

    container.innerHTML = '<ul class="wpc-builder-tabs__menu">'
      + '<li><button type="button" class="wpc-builder-tabs__item" data-tab-id="global">Global</button></li>'
      + '<li><button type="button" class="wpc-builder-tabs__item" data-tab-id="landing">Landing</button></li>'
      + '</ul>'

    return container
  }

  async function embed (field) {
    const control = await loadTabsJs()
    const bindWhenEmbedded = control.each.mock.calls[0][0]
    const container = buildMenuContainer()
    const embedded = { done: vi.fn() }

    bindWhenEmbedded({
      params: { type: 'wpc-builder-tabs', field },
      container: [container],
      deferred: { embedded },
    })

    embedded.done.mock.calls[0][0]()

    return { container, control }
  }

  it('activates the first tab by default', async () => {
    const { container } = await embed({ tabs: { global: 'Global', landing: 'Landing' }, assignments: {} })
    const buttons = container.querySelectorAll('.wpc-builder-tabs__item')

    expect(buttons[0].classList.contains('wpc-builder-tabs__item--active')).toBe(true)
    expect(buttons[1].classList.contains('wpc-builder-tabs__item--active')).toBe(false)

    delete window.wp
  })

  it('never looks up a sibling control when nothing is assigned to any tab', async () => {
    const { control } = await embed({ tabs: { global: 'Global' }, assignments: {} })

    expect(control).not.toHaveBeenCalled()

    delete window.wp
  })

  it('hides a sibling control whose tab is not active, shows it once its tab is clicked', async () => {
    const siblingContainer = document.createElement('li')

    const { container, control } = await embed({
      tabs: { global: 'Global', landing: 'Landing' },
      assignments: { header_landing_buttons: 'landing' },
    })

    control.mockImplementation((id, callback) => {
      if (id === 'header_landing_buttons') {
        callback({ container: [siblingContainer] })
      }
    })

    container.querySelector('[data-tab-id="global"]').click()

    expect(siblingContainer.classList.contains('wpc-builder-tabs__hidden')).toBe(true)

    container.querySelector('[data-tab-id="landing"]').click()

    expect(siblingContainer.classList.contains('wpc-builder-tabs__hidden')).toBe(false)

    delete window.wp
  })

  it('toggles the active class between tab buttons on click', async () => {
    const { container } = await embed({ tabs: { global: 'Global', landing: 'Landing' }, assignments: {} })

    container.querySelector('[data-tab-id="landing"]').click()

    const buttons = container.querySelectorAll('.wpc-builder-tabs__item')

    expect(buttons[0].classList.contains('wpc-builder-tabs__item--active')).toBe(false)
    expect(buttons[1].classList.contains('wpc-builder-tabs__item--active')).toBe(true)

    delete window.wp
  })
})
