import { renderElement } from './template.js'

export const URL_PLACEHOLDER = 'https://example.com'
export const URL_HELPER_TEXT = ''
export const URL_PREFIX = ''

const URL_FIELD_WRAPPER_MARKUP = '<div class="wpc-builder-url-field"><div class="wpc-builder-url-field__input-wrap"><span class="wpc-builder-url-field__icon dashicons dashicons-admin-links" aria-hidden="true"></span></div></div>'

export function placeholderValue (currentPlaceholder) {
  return typeof currentPlaceholder === 'string' && currentPlaceholder.trim() !== ''
    ? currentPlaceholder
    : URL_PLACEHOLDER
}

function isLinkedUrlInputFallback (target) {
  return target !== null
    && typeof target === 'object'
    && target.type === 'url'
    && target.dataset !== undefined
    && target.dataset.customizeSettingLink !== undefined
}

function isLinkedUrlInput (target) {
  return typeof window !== 'undefined'
    && typeof window.wpcBuilderUrlValidation?.isLinkedUrlInput === 'function'
    ? window.wpcBuilderUrlValidation.isLinkedUrlInput(target)
    : isLinkedUrlInputFallback(target)
}

export function shouldDecorateUrlInput (target) {
  return isLinkedUrlInput(target)
    && target.dataset.wpcBuilderUrlDecorated !== '1'
}

function validateUrlInputElement (inputElement) {
  return typeof window !== 'undefined'
    && typeof window.wpcBuilderUrlValidation?.validateUrlInputElement === 'function'
    ? window.wpcBuilderUrlValidation.validateUrlInputElement(inputElement)
    : true
}

export function createUrlFieldWrapper () {
  const field = renderElement(document, URL_FIELD_WRAPPER_MARKUP)
  const inputWrap = field.querySelector('.wpc-builder-url-field__input-wrap')

  return { field, inputWrap }
}

export function decorateUrlInput (inputElement) {
  if (!(inputElement instanceof HTMLInputElement) || inputElement.dataset.wpcBuilderUrlDecorated === '1') {
    return
  }

  const parent = inputElement.parentElement

  if (parent === null) {
    return
  }

  const { field, inputWrap } = createUrlFieldWrapper()

  parent.insertBefore(field, inputElement)
  inputWrap.appendChild(inputElement)
  inputElement.classList.add('wpc-builder-url-field__input')
  inputElement.placeholder = placeholderValue(inputElement.placeholder)
  inputElement.dataset.wpcBuilderUrlDecorated = '1'
}

export function decorateUrlInputElement (inputElement) {
  if (!(inputElement instanceof HTMLInputElement) || !shouldDecorateUrlInput(inputElement)) {
    return
  }

  decorateUrlInput(inputElement)
}

function decorateVisibleUrlInputs () {
  document
    .querySelectorAll('input[type="url"][data-customize-setting-link]')
    .forEach((input) => decorateUrlInputElement(input))
}

function validateVisibleUrlInputs () {
  document
    .querySelectorAll('input[type="url"][data-customize-setting-link]')
    .forEach((input) => {
      if (!(input instanceof HTMLInputElement)) {
        return
      }

      validateUrlInputElement(input)
    })
}

function handleInput (event) {
  if (!isLinkedUrlInput(event.target)) {
    return
  }

  decorateUrlInputElement(event.target)
  validateUrlInputElement(event.target)
}

function handleChange (event) {
  if (!isLinkedUrlInput(event.target)) {
    return
  }

  decorateUrlInputElement(event.target)

  if (!validateUrlInputElement(event.target) && typeof event.target.reportValidity === 'function') {
    event.target.reportValidity()
  }
}

function refreshUrlInputs () {
  decorateVisibleUrlInputs()
  validateVisibleUrlInputs()
}

function init () {
  document.addEventListener('input', handleInput, true)
  document.addEventListener('change', handleChange, true)

  refreshUrlInputs()

  if (window.wp?.customize !== undefined) {
    window.wp.customize.control.bind('add', refreshUrlInputs)
  }
}

if (typeof window !== 'undefined' && !window.__wpcBuilderUrlJsInitialised) {
  window.__wpcBuilderUrlJsInitialised = true

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
  } else {
    init()
  }
}
