import { expect, it } from 'vitest'
import { isUnset, rangeValueFor, readoutText } from './slider.js'

it('treats only null, undefined and the empty string as unset', () => {
  expect(isUnset(null)).toBe(true)
  expect(isUnset(undefined)).toBe(true)
  expect(isUnset('')).toBe(true)
})

it('does NOT treat a stored zero as unset', () => {
  expect(isUnset(0)).toBe(false)
  expect(isUnset('0')).toBe(false)
})

it('reads out the stored value as text', () => {
  expect(readoutText('35')).toBe('35')
  expect(readoutText(0)).toBe('0')
})

it('says so in words when nothing is stored', () => {
  expect(readoutText('')).toBe('not set')
  expect(readoutText(null)).toBe('not set')
})

it('parks an unset thumb at min rather than at the midpoint', () => {
  expect(rangeValueFor('', '0')).toBe('0')
  expect(rangeValueFor(null, '10')).toBe('10')
  expect(rangeValueFor(undefined, '15')).toBe('15')
})

it('leaves a stored value on the thumb untouched', () => {
  expect(rangeValueFor('35', '0')).toBe('35')
  expect(rangeValueFor(0, '0')).toBe('0')
})
