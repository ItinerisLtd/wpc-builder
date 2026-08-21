// @vitest-environment jsdom
import { expect, it, vi } from 'vitest'
import {
  URL_HELPER_TEXT,
  URL_PLACEHOLDER,
  URL_PREFIX,
  createUrlFieldWrapper,
  decorateUrlInput,
  decorateUrlInputElement,
  placeholderValue,
  shouldDecorateUrlInput,
} from './url.js'

it('uses a URL-shaped placeholder when none is configured', () => {
  expect(placeholderValue('')).toBe(URL_PLACEHOLDER)
  expect(placeholderValue('   ')).toBe(URL_PLACEHOLDER)
})

it('keeps an explicitly configured placeholder', () => {
  expect(placeholderValue('https://example.org/docs')).toBe('https://example.org/docs')
})

it('defines helper text and prefix for URL fields', () => {
  expect(URL_HELPER_TEXT).toBe('')
  expect(URL_PREFIX).toBe('')
})

it('decorates linked URL inputs once', () => {
  expect(
    shouldDecorateUrlInput({
      type: 'url',
      dataset: {
        customizeSettingLink: 'setting_id',
      },
    }),
  ).toBe(true)

  expect(
    shouldDecorateUrlInput({
      type: 'url',
      dataset: {
        customizeSettingLink: 'setting_id',
        wpcBuilderUrlDecorated: '1',
      },
    }),
  ).toBe(false)

  expect(
    shouldDecorateUrlInput({
      type: 'text',
      dataset: {
        customizeSettingLink: 'setting_id',
      },
    }),
  ).toBe(false)
})

it('builds a url field wrapper with an icon and an input-wrap slot', () => {
  const { field, inputWrap } = createUrlFieldWrapper()

  expect(field.className).toBe('wpc-builder-url-field')
  expect(inputWrap.className).toBe('wpc-builder-url-field__input-wrap')

  const icon = field.querySelector('.wpc-builder-url-field__icon')

  expect(icon.getAttribute('aria-hidden')).toBe('true')
  expect(inputWrap.contains(icon)).toBe(true)
})

it('decorates a linked url input by moving it into the wrapper structure', () => {
  document.body.innerHTML = '<div><input type="url" data-customize-setting-link="x"></div>'

  const input = document.querySelector('input')

  decorateUrlInputElement(input)

  expect(input.closest('.wpc-builder-url-field__input-wrap')).not.toBeNull()
  expect(input.classList.contains('wpc-builder-url-field__input')).toBe(true)
  expect(input.dataset.wpcBuilderUrlDecorated).toBe('1')
})

it('does not decorate an already-decorated input again', () => {
  document.body.innerHTML = '<div><input type="url" data-customize-setting-link="x"></div>'

  const input = document.querySelector('input')

  decorateUrlInputElement(input)

  const wrapperCountAfterFirstCall = document.querySelectorAll('.wpc-builder-url-field').length

  decorateUrlInputElement(input)

  expect(document.querySelectorAll('.wpc-builder-url-field').length).toBe(wrapperCountAfterFirstCall)
})

it('decorateUrlInput wraps a url input with no linked setting, unlike decorateUrlInputElement', () => {
  document.body.innerHTML = '<div><input type="url"></div>'

  const input = document.querySelector('input')

  decorateUrlInput(input)

  expect(input.closest('.wpc-builder-url-field__input-wrap')).not.toBeNull()
  expect(input.classList.contains('wpc-builder-url-field__input')).toBe(true)
})

it('decorateUrlInput is idempotent', () => {
  document.body.innerHTML = '<div><input type="url"></div>'

  const input = document.querySelector('input')

  decorateUrlInput(input)

  const wrapperCountAfterFirstCall = document.querySelectorAll('.wpc-builder-url-field').length

  decorateUrlInput(input)

  expect(document.querySelectorAll('.wpc-builder-url-field').length).toBe(wrapperCountAfterFirstCall)
})

it('only registers its global listeners once, even if its module code runs twice', async () => {
  delete window.__wpcBuilderUrlJsInitialised

  vi.resetModules()

  const addEventListenerSpy = vi.spyOn(document, 'addEventListener')

  await import('./url.js')

  const callsAfterFirstLoad = addEventListenerSpy.mock.calls.length

  expect(callsAfterFirstLoad).toBeGreaterThan(0)

  vi.resetModules()
  await import('./url.js')

  expect(addEventListenerSpy.mock.calls.length).toBe(callsAfterFirstLoad)

  addEventListenerSpy.mockRestore()
  delete window.__wpcBuilderUrlJsInitialised
})
