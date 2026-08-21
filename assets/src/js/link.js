import { decorateUrlInput } from './url.js'

const TARGET_BLANK = '_blank'
const TARGET_SELF = '_self'

function normaliseTarget (target) {
  return target === TARGET_BLANK ? TARGET_BLANK : TARGET_SELF
}

export function normaliseLinkValue (raw) {
  if (raw && typeof raw === 'object' && !Array.isArray(raw)) {
    return {
      url: typeof raw.url === 'string' ? raw.url : '',
      text: typeof raw.text === 'string' ? raw.text : '',
      target: normaliseTarget(raw.target),
    }
  }

  if (typeof raw === 'string' && raw !== '') {
    return { url: raw, text: '', target: TARGET_SELF }
  }

  return { url: '', text: '', target: TARGET_SELF }
}

export function encode (value) {
  return encodeURIComponent(JSON.stringify(value))
}

export function decode (hiddenValue) {
  if (!hiddenValue) {
    return normaliseLinkValue(null)
  }

  try {
    return normaliseLinkValue(JSON.parse(decodeURIComponent(hiddenValue)))
  } catch (error) {
    return normaliseLinkValue(null)
  }
}

function coerceValue (value) {
  if (value && typeof value === 'object' && !Array.isArray(value)) {
    return normaliseLinkValue(value)
  }

  if (typeof value === 'string' && value !== '') {
    return normaliseLinkValue(value)
  }

  return null
}

export function seedValue (settingValue, paramsValue, hiddenInputValue) {
  const fromParams = coerceValue(paramsValue)

  if (null !== fromParams) {
    return fromParams
  }

  const fromSetting = coerceValue(settingValue)

  if (null !== fromSetting) {
    return fromSetting
  }

  return decode(hiddenInputValue)
}

function validateUrlInputElement (inputElement) {
  return typeof window.wpcBuilderUrlValidation?.validateUrlInputElement === 'function'
    ? window.wpcBuilderUrlValidation.validateUrlInputElement(inputElement)
    : true
}

export function seedLinkGroup (container, value) {
  const textInput = container.querySelector('.wpc-builder-link__text')
  const urlInput = container.querySelector('.wpc-builder-link__url')
  const targetInput = container.querySelector('.wpc-builder-link__target')

  if (urlInput) {
    decorateUrlInput(urlInput)
    urlInput.value = value.url
  }

  if (textInput) {
    textInput.value = value.text
  }

  if (targetInput) {
    targetInput.checked = value.target === TARGET_BLANK
  }
}

export function readLinkGroupValue (container) {
  const textInput = container.querySelector('.wpc-builder-link__text')
  const urlInput = container.querySelector('.wpc-builder-link__url')
  const targetInput = container.querySelector('.wpc-builder-link__target')

  return {
    url: urlInput ? urlInput.value : '',
    text: textInput ? textInput.value : '',
    target: targetInput?.checked ? TARGET_BLANK : TARGET_SELF,
  }
}

export function createLinkUi (control, container, hiddenInput) {
  const textInput = container.querySelector('.wpc-builder-link__text')
  const urlInput = container.querySelector('.wpc-builder-link__url')
  const targetInput = container.querySelector('.wpc-builder-link__target')

  if (!textInput || !urlInput || !targetInput) {
    return
  }

  const settingValue = typeof control.setting === 'function' ? control.setting() : undefined
  const value = seedValue(settingValue, control.params.value, hiddenInput.value)

  seedLinkGroup(container, value)
  hiddenInput.value = encode(value)

  function persist () {
    hiddenInput.value = encode(readLinkGroupValue(container))
    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }))
  }

  function handleUrlChange () {
    if (!validateUrlInputElement(urlInput) && typeof urlInput.reportValidity === 'function') {
      urlInput.reportValidity()
    }

    persist()
  }

  textInput.addEventListener('input', persist)
  urlInput.addEventListener('input', persist)
  urlInput.addEventListener('change', handleUrlChange)
  targetInput.addEventListener('change', persist)
}

function bindWhenEmbedded (control) {
  if (control.params.type !== 'wpc-builder-link') {
    return
  }

  if (control._wpcBuilderLinkInit) {
    return
  }

  control._wpcBuilderLinkInit = true

  control.deferred.embedded.done(() => {
    const container = control.container && control.container[0]

    if (!container) {
      return
    }

    const hiddenInput = container.querySelector('input[type="hidden"]')

    if (!hiddenInput) {
      return
    }

    createLinkUi(control, container, hiddenInput)
  })
}

function init () {
  if (!window.wp?.customize) {
    return
  }

  window.wp.customize.control.each(bindWhenEmbedded)
  window.wp.customize.control.bind('add', bindWhenEmbedded)
}

if (typeof window !== 'undefined' && !window.__wpcBuilderLinkJsInitialised) {
  window.__wpcBuilderLinkJsInitialised = true

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
  } else {
    init()
  }
}
