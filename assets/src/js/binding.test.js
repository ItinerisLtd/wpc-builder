import { describe, expect, it } from 'vitest'
import { NOT_FOUND } from './evaluate.js'
import { bindConditions, buildGetValue, collectSettings } from './binding.js'

describe('buildGetValue', () => {
  it('reports NOT_FOUND when no control exists for the setting id at all', () => {
    const api = Object.assign(() => undefined, {
      control: () => undefined,
    })

    expect(buildGetValue(api)('alert_enabled')).toBe(NOT_FOUND)
  })

  it('reads the value off control.setting when the control exists', () => {
    const api = Object.assign(() => undefined, {
      control: (id) => ('known' === id ? { setting: { get: () => 'from-control' } } : undefined),
    })

    expect(buildGetValue(api)('known')).toBe('from-control')
  })

  it('falls back to the raw wp.customize setting when the control exists but control.setting does not yet', () => {
    const api = Object.assign(
      (id) => ('known' === id ? { get: () => 'from-raw-setting' } : undefined),
      { control: () => ({}) },
    )

    expect(buildGetValue(api)('known')).toBe('from-raw-setting')
  })

  it('reports NOT_FOUND when the control exists without .setting and the raw setting also does not exist', () => {
    const api = Object.assign(() => undefined, {
      control: () => ({}),
    })

    expect(buildGetValue(api)('known')).toBe(NOT_FOUND)
  })
})

describe('collectSettings', () => {
  it('skips sparse array holes, recurses into nested groups, and de-duplicates', () => {
    const conditions = [
      { setting: 'a' },
      /* hole */ ,
      { setting: 'b' },
      [ , { setting: 'c' } ],
      { notSetting: true },
      { setting: 'a' },
    ]

    expect(collectSettings(conditions)).toEqual(['a', 'b', 'c'])
  })

  it('returns an empty list for an empty condition list', () => {
    expect(collectSettings([])).toEqual([])
  })
})

describe('bindConditions', () => {
  it('evaluates immediately, calls onEvaluate once, and returns the evaluator', () => {
    const values = { enabled: true }
    const getValue = (setting) => values[setting]
    const conditions = [{ setting: 'enabled', operator: '==', value: true }]
    const calls = []

    const api = (id, cb) => {
      cb({ bind: () => {} })

      return undefined
    }

    const evaluate = bindConditions(api, getValue, conditions, (result) => {
      calls.push(result)
    })

    expect(calls).toEqual([true])
    expect(evaluate()).toBe(true)
  })

  it('re-evaluates and calls onEvaluate again when a bound setting changes', () => {
    const values = { enabled: false }
    const getValue = (setting) => values[setting]
    const conditions = [{ setting: 'enabled', operator: '==', value: true }]
    const calls = []
    let boundApply = null

    const api = (id, cb) => {
      cb({ bind: (fn) => { boundApply = fn } })

      return undefined
    }

    bindConditions(api, getValue, conditions, (result) => {
      calls.push(result)
    })

    expect(calls).toEqual([false])

    values.enabled = true
    boundApply()

    expect(calls).toEqual([false, true])
  })
})
