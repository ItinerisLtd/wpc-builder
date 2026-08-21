// @vitest-environment jsdom
import { describe, expect, it, vi } from 'vitest'
import { createLinkUi, decode, encode, normaliseLinkValue, readLinkGroupValue, seedLinkGroup, seedValue } from './link.js'

describe('normaliseLinkValue', () => {
  it('passes through a well-formed object', () => {
    expect(normaliseLinkValue({ url: 'https://example.test', text: 'Visit', target: '_blank' }))
      .toEqual({ url: 'https://example.test', text: 'Visit', target: '_blank' })
  })

  it('treats a plain string as a legacy url, for backward compatibility', () => {
    expect(normaliseLinkValue('https://legacy.example.test'))
      .toEqual({ url: 'https://legacy.example.test', text: '', target: '_self' })
  })

  it('treats null/undefined/empty-string as the empty shape', () => {
    const empty = { url: '', text: '', target: '_self' }

    expect(normaliseLinkValue(null)).toEqual(empty)
    expect(normaliseLinkValue(undefined)).toEqual(empty)
    expect(normaliseLinkValue('')).toEqual(empty)
  })

  it('normalises any value other than "_blank" to "_self"', () => {
    expect(normaliseLinkValue({ url: '', text: '', target: '_self' }).target).toBe('_self')
    expect(normaliseLinkValue({ url: '', text: '', target: 'nonsense' }).target).toBe('_self')
  })
})

describe('encode/decode round trip', () => {
  it('round-trips a value through the hidden input wire format', () => {
    const value = { url: 'https://example.test', text: 'Visit', target: '_blank' }

    expect(decode(encode(value))).toEqual(value)
  })

  it('decodes an empty or malformed hidden value to the empty shape', () => {
    const empty = { url: '', text: '', target: '_self' }

    expect(decode('')).toEqual(empty)
    expect(decode('not json')).toEqual(empty)
  })
})

describe('seedLinkGroup / readLinkGroupValue', () => {
  function buildGroup () {
    const container = document.createElement('div')

    container.innerHTML = '<input type="text" class="wpc-builder-link__text">'
      + '<input type="url" class="wpc-builder-link__url">'
      + '<input type="checkbox" class="wpc-builder-link__target">'

    return container
  }

  it('seeds all three inputs from a value', () => {
    const container = buildGroup()

    seedLinkGroup(container, { url: 'https://example.test', text: 'Visit us', target: '_blank' })

    expect(container.querySelector('.wpc-builder-link__url').value).toBe('https://example.test')
    expect(container.querySelector('.wpc-builder-link__text').value).toBe('Visit us')
    expect(container.querySelector('.wpc-builder-link__target').checked).toBe(true)
  })

  it('reads all three inputs back into a value, round-tripping seedLinkGroup', () => {
    const container = buildGroup()
    const value = { url: 'https://example.test', text: 'Visit us', target: '_blank' }

    seedLinkGroup(container, value)

    expect(readLinkGroupValue(container)).toEqual(value)
  })

  it('decorates the url input the same way as a plain Fields\\Url input', () => {
    const container = buildGroup()

    seedLinkGroup(container, { url: 'https://example.test', text: '', target: '_self' })

    expect(container.querySelector('.wpc-builder-link__url').closest('.wpc-builder-url-field__input-wrap')).not.toBeNull()
  })

  it('does not throw when a sub-input is missing from the container', () => {
    const container = document.createElement('div')

    container.innerHTML = '<input type="url" class="wpc-builder-link__url">'

    expect(() => seedLinkGroup(container, { url: '', text: '', target: '_self' })).not.toThrow()
    expect(readLinkGroupValue(container)).toEqual({ url: '', text: '', target: '_self' })
  })
})

describe('seedValue', () => {
  it('prefers params.value over setting() and the hidden input', () => {
    const fromParams = { url: 'https://from-params.test', text: 'Params', target: '_self' }
    const fromSetting = { url: 'https://from-setting.test', text: 'Setting', target: '_self' }

    expect(seedValue(fromSetting, fromParams, 'irrelevant')).toEqual(fromParams)
  })

  it('falls back to setting() when params.value is absent', () => {
    const fromSetting = { url: 'https://from-setting.test', text: 'Setting', target: '_self' }

    expect(seedValue(fromSetting, undefined, 'irrelevant')).toEqual(fromSetting)
  })

  it('falls back to the hidden input value when both are absent', () => {
    const value = { url: 'https://from-hidden.test', text: 'Hidden', target: '_blank' }

    expect(seedValue(undefined, undefined, encode(value))).toEqual(value)
  })

  it('treats a legacy string setting() value as the url', () => {
    expect(seedValue('https://legacy.example.test', undefined, '')).toEqual({
      url: 'https://legacy.example.test',
      text: '',
      target: '_self',
    })
  })

  it('does not treat a bare array params.value as a well-formed value, falling through to setting()', () => {
    const fromSetting = { url: 'https://from-setting.test', text: 'Setting', target: '_self' }

    expect(seedValue(fromSetting, [], 'irrelevant')).toEqual(fromSetting)
  })
})

describe('createLinkUi', () => {
  function buildContainer () {
    const container = document.createElement('div')

    container.innerHTML = '<input type="text" class="wpc-builder-link__text">'
      + '<input type="url" class="wpc-builder-link__url">'
      + '<input type="checkbox" class="wpc-builder-link__target">'

    return container
  }

  it('seeds the visible inputs from control.params.value on creation', () => {
    const container = buildContainer()
    const hiddenInput = document.createElement('input')

    hiddenInput.type = 'hidden'

    const control = {
      params: { value: { url: 'https://example.test', text: 'Visit us', target: '_blank' } },
    }

    createLinkUi(control, container, hiddenInput)

    expect(container.querySelector('.wpc-builder-link__url').value).toBe('https://example.test')
    expect(container.querySelector('.wpc-builder-link__text').value).toBe('Visit us')
    expect(container.querySelector('.wpc-builder-link__target').checked).toBe(true)
    expect(decode(hiddenInput.value)).toEqual({
      url: 'https://example.test',
      text: 'Visit us',
      target: '_blank',
    })
  })

  it('decorates the url input the same way as a plain Fields\\Url input', () => {
    const container = buildContainer()
    const hiddenInput = document.createElement('input')

    hiddenInput.type = 'hidden'

    createLinkUi({ params: { value: null } }, container, hiddenInput)

    const urlInput = container.querySelector('.wpc-builder-link__url')

    expect(urlInput.closest('.wpc-builder-url-field__input-wrap')).not.toBeNull()
    expect(urlInput.classList.contains('wpc-builder-url-field__input')).toBe(true)
  })

  it('writes the hidden input and fires change when a visible input changes', () => {
    const container = buildContainer()
    const hiddenInput = document.createElement('input')

    hiddenInput.type = 'hidden'

    const control = { params: { value: null } }
    const onChange = vi.fn()

    hiddenInput.addEventListener('change', onChange)
    createLinkUi(control, container, hiddenInput)

    const urlInput = container.querySelector('.wpc-builder-link__url')

    urlInput.value = 'https://updated.test'
    urlInput.dispatchEvent(new Event('input', { bubbles: true }))

    expect(onChange).toHaveBeenCalledTimes(1)
    expect(decode(hiddenInput.value).url).toBe('https://updated.test')
  })

  it('writes target as _blank when checked, _self when unchecked', () => {
    const container = buildContainer()
    const hiddenInput = document.createElement('input')

    hiddenInput.type = 'hidden'

    createLinkUi({ params: { value: null } }, container, hiddenInput)

    const targetInput = container.querySelector('.wpc-builder-link__target')

    targetInput.checked = true
    targetInput.dispatchEvent(new Event('change', { bubbles: true }))

    expect(decode(hiddenInput.value).target).toBe('_blank')

    targetInput.checked = false
    targetInput.dispatchEvent(new Event('change', { bubbles: true }))

    expect(decode(hiddenInput.value).target).toBe('_self')
  })
})

describe('module initialisation', () => {
  it('only registers its global control-embed listener once, even if its module code runs twice', async () => {
    delete window.__wpcBuilderLinkJsInitialised

    vi.resetModules()

    window.wp = { customize: { control: { each: vi.fn(), bind: vi.fn() } } }

    await import('./link.js')

    const callsAfterFirstLoad = window.wp.customize.control.each.mock.calls.length

    expect(callsAfterFirstLoad).toBeGreaterThan(0)

    vi.resetModules()
    await import('./link.js')

    expect(window.wp.customize.control.each.mock.calls.length).toBe(callsAfterFirstLoad)

    delete window.wp
    delete window.__wpcBuilderLinkJsInitialised
  })

  it('wires up a control exactly once via the per-control guard, and embedded.done() actually renders the UI', async () => {
    delete window.__wpcBuilderLinkJsInitialised

    vi.resetModules()

    window.wp = { customize: { control: { each: vi.fn(), bind: vi.fn() } } }

    await import('./link.js')

    const bindWhenEmbedded = window.wp.customize.control.each.mock.calls[0][0]

    const container = document.createElement('div')

    container.innerHTML = '<input type="text" class="wpc-builder-link__text">'
      + '<input type="url" class="wpc-builder-link__url">'
      + '<input type="checkbox" class="wpc-builder-link__target">'
      + '<input type="hidden">'

    const embedded = { done: vi.fn() }
    const control = { params: { type: 'wpc-builder-link', value: null }, container: [container], deferred: { embedded } }

    bindWhenEmbedded(control)

    expect(control._wpcBuilderLinkInit).toBe(true)
    expect(embedded.done).toHaveBeenCalledTimes(1)

    bindWhenEmbedded(control)

    expect(embedded.done).toHaveBeenCalledTimes(1)

    const onEmbedded = embedded.done.mock.calls[0][0]

    onEmbedded()

    const urlInput = container.querySelector('.wpc-builder-link__url')
    const hiddenInput = container.querySelector('input[type="hidden"]')

    urlInput.value = 'https://updated.test'
    urlInput.dispatchEvent(new Event('input', { bubbles: true }))

    expect(decode(hiddenInput.value).url).toBe('https://updated.test')

    delete window.wp
    delete window.__wpcBuilderLinkJsInitialised
  })

  it('ignores a non-wpc-builder-link control and never registers embedded.done()', async () => {
    delete window.__wpcBuilderLinkJsInitialised

    vi.resetModules()

    window.wp = { customize: { control: { each: vi.fn(), bind: vi.fn() } } }

    await import('./link.js')

    const bindWhenEmbedded = window.wp.customize.control.each.mock.calls[0][0]
    const embedded = { done: vi.fn() }
    const control = { params: { type: 'text' }, deferred: { embedded } }

    bindWhenEmbedded(control)

    expect(embedded.done).not.toHaveBeenCalled()
    expect(control._wpcBuilderLinkInit).toBeUndefined()

    delete window.wp
    delete window.__wpcBuilderLinkJsInitialised
  })
})
