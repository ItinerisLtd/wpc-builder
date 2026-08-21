// @vitest-environment jsdom
import { beforeAll, describe, expect, it } from 'vitest'

beforeAll(async () => {
  await import('./url-validation.js')

  window.wpcBuilderUrlValidationSettings = {
    allowedProtocols: [
      'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'irc6', 'ircs',
      'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'sms', 'svn', 'tel',
      'fax', 'xmpp', 'webcal', 'urn',
    ],
  }
})

describe('isValidOrEmptyUrl', () => {
  it('accepts the URL shapes the server-side rule accepts', () => {
    const { isValidOrEmptyUrl } = window.wpcBuilderUrlValidation

    const accepted = [
      '',
      '   ',
      'https://example.test/path',
      'HTTPS://EXAMPLE.TEST/PATH',
      '/contact',
      '#footer',
      '?s=query',
      '//cdn.example.test/x',
      'example.test/foo',
      'tel:+441234567890',
      'mailto:hello@example.test',
      'https://exämple.test/path',
    ]

    accepted.forEach((url) => expect(isValidOrEmptyUrl(url), url).toBe(true))
  })

  it('rejects whitespace-containing garbage and disallowed schemes', () => {
    const { isValidOrEmptyUrl } = window.wpcBuilderUrlValidation

    const rejected = [
      'not a url',
      'https://example.test/a b',
      'javascript:alert(1)',
      'data:text/html,x',
    ]

    rejected.forEach((url) => expect(isValidOrEmptyUrl(url), url).toBe(false))
  })

  it('uses the server-localised protocol list', () => {
    const { isValidOrEmptyUrl } = window.wpcBuilderUrlValidation

    const original = window.wpcBuilderUrlValidationSettings

    window.wpcBuilderUrlValidationSettings = { allowedProtocols: ['https', 'custom'] }

    try {
      expect(isValidOrEmptyUrl('custom:thing')).toBe(true)
      expect(isValidOrEmptyUrl('http://example.test')).toBe(false)
    } finally {
      window.wpcBuilderUrlValidationSettings = original
    }
  })

  it('degrades to rejecting scheme-qualified URLs, without throwing, when the server settings are absent', () => {
    const { isValidOrEmptyUrl } = window.wpcBuilderUrlValidation

    const original = window.wpcBuilderUrlValidationSettings

    delete window.wpcBuilderUrlValidationSettings

    try {
      expect(() => isValidOrEmptyUrl('https://example.test')).not.toThrow()
      expect(isValidOrEmptyUrl('https://example.test')).toBe(false)
      expect(isValidOrEmptyUrl('/contact')).toBe(true)
    } finally {
      window.wpcBuilderUrlValidationSettings = original
    }
  })

  it('degrades to rejecting scheme-qualified URLs, without throwing, when the localised allowedProtocols value is not an array', () => {
    const { isValidOrEmptyUrl } = window.wpcBuilderUrlValidation

    const original = window.wpcBuilderUrlValidationSettings

    window.wpcBuilderUrlValidationSettings = { allowedProtocols: { https: true } }

    try {
      expect(() => isValidOrEmptyUrl('https://example.test')).not.toThrow()
      expect(isValidOrEmptyUrl('https://example.test')).toBe(false)
    } finally {
      window.wpcBuilderUrlValidationSettings = original
    }
  })
})

describe('validateUrlInputElement', () => {
  function urlInput (value) {
    const input = document.createElement('input')

    input.type = 'url'
    input.value = value

    return input
  }

  it('accepts a relative URL the native type="url" constraint would reject', () => {
    const input = urlInput('/contact')

    // validationMessage may still carry the native typeMismatch; only the
    // return value and the custom-error flag are this function's contract.
    expect(window.wpcBuilderUrlValidation.validateUrlInputElement(input)).toBe(true)
    expect(input.validity.customError).toBe(false)
  })

  it('flags a disallowed scheme with a custom validity message', () => {
    const input = urlInput('javascript:alert(1)')

    expect(window.wpcBuilderUrlValidation.validateUrlInputElement(input)).toBe(false)
    expect(input.validationMessage).toBe('Please enter a valid URL.')
  })

  it('clears a previous custom validity message once the value is corrected', () => {
    const input = urlInput('not a url')

    window.wpcBuilderUrlValidation.validateUrlInputElement(input)

    expect(input.validity.customError).toBe(true)

    input.value = '#footer'

    expect(window.wpcBuilderUrlValidation.validateUrlInputElement(input)).toBe(true)
    expect(input.validity.customError).toBe(false)
  })

  it('passes through a non-url input untouched', () => {
    const input = document.createElement('input')

    input.type = 'text'
    input.value = 'not a url'

    expect(window.wpcBuilderUrlValidation.validateUrlInputElement(input)).toBe(true)
  })
})
