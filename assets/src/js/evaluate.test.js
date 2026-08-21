import { describe, expect, it } from 'vitest'
import { evaluate, NOT_FOUND, passes } from './evaluate.js'

describe('evaluate', () => {
  it('handles strict and loose equality', () => {
    expect(evaluate('1', '1', '===')).toBe(true)
    expect(evaluate('1', 1, '===')).toBe(false)
    expect(evaluate('1', 1, '==')).toBe(true)
    expect(evaluate('1', 1, 'equals')).toBe(true)
  })

  it('handles negation', () => {
    expect(evaluate('1', 1, '!==')).toBe(true)
    expect(evaluate('1', 2, '!=')).toBe(true)
    expect(evaluate('1', 1, 'not equal')).toBe(false)
  })

  it('compares actual against expected for ordering operators', () => {
    expect(evaluate(5, 10, '>')).toBe(true)
    expect(evaluate(5, 5, '>=')).toBe(true)
    expect(evaluate(5, 1, '<')).toBe(true)
    expect(evaluate(5, 5, '<=')).toBe(true)
  })

  it('handles containment', () => {
    expect(evaluate('b', ['a', 'b'], 'in')).toBe(true)
    expect(evaluate('c', ['a', 'b'], 'contains')).toBe(false)
    expect(evaluate('c', ['a', 'b'], 'not in')).toBe(true)
  })

  it('indexes into an object value with choice', () => {
    expect(evaluate('x', { top: 'x' }, '==', 'top')).toBe(true)
  })

  it('falls back to loose equality for an unknown operator', () => {
    expect(evaluate('1', 1, 'wat')).toBe(true)
  })

  describe('parity regressions', () => {
    it('indexes into an ARRAY value with choice, not just a plain object (brief\'s isObject() excluded arrays)', () => {
      expect(evaluate('a', ['a', 'b'], '===', '0')).toBe(true)
      expect(evaluate('b', ['a', 'b'], '===', '0')).toBe(false)
    })

    it('falls back to loose equality inside contains()/"in" when no array/object/string branch matches, not false', () => {
      expect(evaluate(5, 5, 'in')).toBe(true)
      expect(evaluate(5, 6, 'in')).toBe(false)
    })

    it('gates the contains() string branch on the ACTUAL operand\'s type, not the expected operand\'s', () => {
      expect(evaluate(5, 'abc', 'in')).toBe(false)

      expect(evaluate(['a', 'b', 'c'], 'b', 'in')).toBe(true)
    })

    it('does not double-apply choice indexing for "not in"/"does not contain"', () => {
      expect(evaluate('a', { top: ['a', 'b'] }, 'not in', 'top')).toBe(false)
    })
  })
})

describe('passes', () => {
  const get = (setting) => ({ enabled: true, mode: 'advanced' })[setting]

  it('ANDs conditions at the top level', () => {
    expect(passes([
      { setting: 'enabled', operator: '==', value: true },
      { setting: 'mode', operator: '==', value: 'advanced' },
    ], get)).toBe(true)

    expect(passes([
      { setting: 'enabled', operator: '==', value: true },
      { setting: 'mode', operator: '==', value: 'basic' },
    ], get)).toBe(false)
  })

  it('ORs one level down', () => {
    expect(passes([
      [
        { setting: 'mode', operator: '==', value: 'basic' },
        { setting: 'mode', operator: '==', value: 'advanced' },
      ],
    ], get)).toBe(true)
  })

  it('is visible when there are no conditions', () => {
    expect(passes([], get)).toBe(true)
  })

  it('fails OPEN (passes) when getValue reports NOT_FOUND for a condition\'s setting', () => {
    const getWithMissing = (setting) => (
      'known' === setting ? true : NOT_FOUND
    )

    expect(passes([
      { setting: 'unknown', operator: '==', value: false },
    ], getWithMissing)).toBe(true)

    expect(passes([
      { setting: 'unknown', operator: '==', value: false },
      { setting: 'known', operator: '==', value: false },
    ], getWithMissing)).toBe(false)
  })
})
