import { describe, expect, it, vi } from 'vitest'

describe('dependencies.js wiring (integration via public API)', () => {
  it('skips sparse array holes and registers listeners for unique setting ids recursively', async () => {
    vi.resetModules()

    const controlCalls = []
    const apiCalls = []

    const api = (id, cb) => {
      apiCalls.push(id)
      if (cb) {
        cb({ bind: (_fn) => {} })
      }
      return undefined
    }
    api.control = (id, cb) => {
      controlCalls.push(id)
      if (cb) {
        cb({ active: { set: () => {}, validate: () => {} }, setting: undefined })
      }
      return undefined
    }

    const data = {
      control1: [
        { setting: 'a' },
        /* hole */ ,
        { setting: 'b' },
        [ , { setting: 'c' } ],
        { notSetting: true },
        { setting: 'a' },
      ],
    }

    // Provide globals before importing the module so the IIFE runs with them
    global.window = { wp: { customize: api }, wpcBuilderVisibilityDependencies: data }

    await import('./dependencies.js')

    expect(controlCalls[0]).toBe('control1')
    expect(controlCalls).toContain('control1')

    expect(apiCalls).toEqual(expect.arrayContaining(['a', 'b', 'c']))

    delete global.window
  })
})
