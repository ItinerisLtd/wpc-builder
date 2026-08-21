import { afterEach, describe, expect, it } from 'vitest'
import { format, translate, translatePlural } from './i18n.js'

afterEach(() => {
  delete globalThis.wp
})

describe('translate', () => {
  it('passes the text and the package text domain to wp.i18n.__', () => {
    const calls = []

    globalThis.wp = {
      i18n: {
        __: (text, domain) => {
          calls.push([text, domain])

          return 'Ajouter une ligne'
        },
      },
    }

    expect(translate('Add row')).toBe('Ajouter une ligne')
    expect(calls).toEqual([['Add row', 'wpc-builder']])
  })

  it('prefers an injected wp over the global one', () => {
    globalThis.wp = { i18n: { __: () => 'from the global' } }

    const injected = { i18n: { __: () => 'from the argument' } }

    expect(translate('Add row', injected)).toBe('from the argument')
  })

  it('returns the text unchanged when wp-i18n is absent', () => {
    expect(translate('Add row')).toBe('Add row')
  })
})

describe('translatePlural', () => {
  it('passes both forms, the count and the text domain to wp.i18n._n', () => {
    const calls = []

    globalThis.wp = {
      i18n: {
        _n: (single, plural, count, domain) => {
          calls.push([single, plural, count, domain])

          return 'Limite : %s lignes'
        },
      },
    }

    expect(translatePlural('Limit: %s row', 'Limit: %s rows', 4)).toBe('Limite : %s lignes')
    expect(calls).toEqual([['Limit: %s row', 'Limit: %s rows', 4, 'wpc-builder']])
  })

  it('picks the English form by count when wp-i18n is absent', () => {
    expect(translatePlural('Limit: %s row', 'Limit: %s rows', 1)).toBe('Limit: %s row')
    expect(translatePlural('Limit: %s row', 'Limit: %s rows', 2)).toBe('Limit: %s rows')
  })
})

describe('format', () => {
  it('interpolates through wp.i18n.sprintf when available', () => {
    globalThis.wp = {
      i18n: {
        sprintf: (text, ...values) => text.replace('%s', String(values[0])),
      },
    }

    expect(format('Row %s', 3)).toBe('Row 3')
  })

  it('replaces each %s placeholder in order when sprintf is absent', () => {
    expect(format('Limit: %s rows', 5)).toBe('Limit: 5 rows')
  })
})
