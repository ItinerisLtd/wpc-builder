import { translate } from './i18n.js'

function allowedProtocols () {
  if (typeof window === 'undefined') return []

  const fromServer = window.wpcBuilderUrlValidationSettings?.allowedProtocols

  return Array.isArray(fromServer) ? fromServer : []
}

// Mirrors Support\UrlValidation::isValidOrEmpty(); keep in sync. Not the
// native type="url" constraint, which rejects relative/anchor/tel URLs
// the server accepts.
function isValidOrEmptyUrl (value) {
  const url = String(value ?? '').trim()

  if (url === '') return true

  if (/\s/.test(url)) return false

  const scheme = /^([a-z][a-z0-9+.-]*):/i.exec(url)

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
