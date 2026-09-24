import { translate } from './i18n.js'

function allowedProtocols () {
  if (typeof window === 'undefined') return []

  const fromServer = window.wpcBuilderUrlValidationSettings?.allowedProtocols

  return Array.isArray(fromServer) ? fromServer : []
}

let decoderTextarea = null

function decodeHtmlEntities (value) {
  if (typeof document === 'undefined') return value

  // Runs on every keystroke and every repeater row render, so the
  // element is created once and reused rather than allocated per call.
  decoderTextarea ??= document.createElement('textarea')

  try {
    decoderTextarea.innerHTML = value

    return decoderTextarea.value
  } catch {
    // A Trusted-Types CSP throws on the innerHTML assignment above.
    // Falling back to the undecoded value keeps validation working
    // (see is_valid_or_empty_url()'s wider whitespace/bracket/control
    // rejection) instead of throwing out of every keystroke.
    return value
  }
}

// Mirrors Support\is_valid_or_empty_url(); keep in sync. Not the native
// type="url" constraint, which rejects relative/anchor/tel URLs the
// server accepts.
function isValidOrEmptyUrl (value) {
  const url = String(value ?? '').trim()

  if (url === '') return true

  const decoded = decodeHtmlEntities(url)

  // eslint-disable-next-line no-control-regex -- rejecting C0 controls is the point
  if (/[\x00-\x20"<>]/.test(decoded)) return false

  const scheme = /^([a-z][a-z0-9+.-]*):/i.exec(decoded)

  if (scheme === null) return true

  return allowedProtocols().includes(scheme[1].toLowerCase())
}

function isLinkedUrlInput (target) {
  return target instanceof HTMLInputElement
    && target.type === 'url'
    && target.dataset.customizeSettingLink !== undefined
}

function validateUrlInputElement (inputElement) {
  if (!(inputElement instanceof HTMLInputElement) || inputElement.type !== 'url') {
    return true
  }

  const isValid = isValidOrEmptyUrl(inputElement.value)

  inputElement.setCustomValidity(isValid ? '' : translate('Please enter a valid URL.'))

  return isValid
}

if (typeof window !== 'undefined') {
  window.wpcBuilderUrlValidation = {
    isLinkedUrlInput,
    isValidOrEmptyUrl,
    validateUrlInputElement,
  }
}
