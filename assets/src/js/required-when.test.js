// @vitest-environment jsdom
import { describe, expect, it, vi } from 'vitest'

function buildDomControl () {
  const container = document.createElement('li')

  container.innerHTML = '<input type="text" class="some-input">'

  return { container: [container] }
}

function buildApi (domControl, getEnabledValue) {
  const api = (id, cb) => {
    if (cb) {
      cb({ bind: () => {} })
    }

    return undefined
  }

  api.control = (id, cb) => {
    if (cb) {
      if ('control1' === id) {
        cb(domControl)
      }

      return undefined
    }

    return 'enabled' === id ? { setting: { get: getEnabledValue } } : undefined
  }

  return api
}

describe('required-when.js wiring (integration via public API)', () => {
  it('marks the control required and sets the native required attribute when conditions pass', async () => {
    vi.resetModules()

    const domControl = buildDomControl()

    global.window = {
      wp: { customize: buildApi(domControl, () => true) },
      wpcBuilderRequiredWhenDependencies: {
        control1: [{ setting: 'enabled', operator: '==', value: true }],
      },
    }

    await import('./required-when.js')

    const container = domControl.container[0]

    expect(container.classList.contains('wpc-builder-required')).toBe(true)
    expect(container.querySelector('input').hasAttribute('required')).toBe(true)

    delete global.window
  })

  it('leaves the control alone when conditions do not pass', async () => {
    vi.resetModules()

    const domControl = buildDomControl()

    global.window = {
      wp: { customize: buildApi(domControl, () => false) },
      wpcBuilderRequiredWhenDependencies: {
        control1: [{ setting: 'enabled', operator: '==', value: true }],
      },
    }

    await import('./required-when.js')

    const container = domControl.container[0]

    expect(container.classList.contains('wpc-builder-required')).toBe(false)
    expect(container.querySelector('input').hasAttribute('required')).toBe(false)

    delete global.window
  })

  it('reacts to a bound sibling value change, updating the required state live', async () => {
    vi.resetModules()

    const domControl = buildDomControl()
    let enabledValue = false
    let boundApply = null

    const api = (id, cb) => {
      if (cb) {
        cb({ bind: (fn) => { boundApply = fn } })
      }

      return undefined
    }
    api.control = (id, cb) => {
      if (cb) {
        if ('control1' === id) {
          cb(domControl)
        }

        return undefined
      }

      return 'enabled' === id ? { setting: { get: () => enabledValue } } : undefined
    }

    global.window = {
      wp: { customize: api },
      wpcBuilderRequiredWhenDependencies: {
        control1: [{ setting: 'enabled', operator: '==', value: true }],
      },
    }

    await import('./required-when.js')

    const container = domControl.container[0]

    expect(container.classList.contains('wpc-builder-required')).toBe(false)

    enabledValue = true
    boundApply()

    expect(container.classList.contains('wpc-builder-required')).toBe(true)
    expect(container.querySelector('input').hasAttribute('required')).toBe(true)

    enabledValue = false
    boundApply()

    expect(container.classList.contains('wpc-builder-required')).toBe(false)
    expect(container.querySelector('input').hasAttribute('required')).toBe(false)

    delete global.window
  })

  it('does nothing when wp.customize or the localised payload is missing', async () => {
    vi.resetModules()

    global.window = {}

    await expect(import('./required-when.js')).resolves.toBeTruthy()

    delete global.window
  })
})
