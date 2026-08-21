import { afterEach, expect, it, vi } from 'vitest'
// This file builds document/window as plain objects in several tests below (see afterEach), which
// only works in Vitest's default 'node' environment. The two tests needing a real DOM (for
// wp.element.render) construct one locally with JSDOM instead.
import { JSDOM } from 'jsdom'
import {
  bootstrapSelectEnhancements,
  handleChange,
  selectedValues,
  normaliseChoices,
  resolveInitialValue,
  tryEnhanceSelect,
} from './select.js'
import { canUseCoreComponents, mountEnhancedSelect } from './select-enhanced-ui.js'

it('collects the values of every selected option, in document order', () => {
  const options = [
    { value: 'home', selected: true },
    { value: 'about', selected: false },
    { value: 'contact', selected: true },
  ]

  expect(selectedValues(options)).toEqual(['home', 'contact'])
})

it('returns an empty array when nothing is selected', () => {
  const options = [
    { value: 'home', selected: false },
    { value: 'about', selected: false },
  ]

  expect(selectedValues(options)).toEqual([])
})

it('keeps duplicate values rather than de-duplicating them', () => {
  const options = [
    { value: '12', selected: true },
    { value: '12', selected: true },
  ]

  expect(selectedValues(options)).toEqual(['12', '12'])
})

it('normalises DOM options into value/label pairs', () => {
  const options = [
    { value: 'home', text: 'Home' },
    { value: 'about', text: 'About' },
  ]

  expect(normaliseChoices(options)).toEqual([
    { value: 'home', label: 'Home' },
    { value: 'about', label: 'About' },
  ])
})

it('resolveInitialValue returns correct shapes for multiple and single selects', () => {
  expect(resolveInitialValue({ multiple: true, value: ['a', 'b'] })).toEqual(['a', 'b'])
  expect(resolveInitialValue({ multiple: true, value: 'a' })).toEqual(['a'])
  expect(resolveInitialValue({ multiple: true, value: null })).toEqual([])

  expect(resolveInitialValue({ multiple: false, value: null })).toBe('')
  expect(resolveInitialValue({ multiple: false, value: 'about' })).toBe('about')
  expect(resolveInitialValue({ multiple: false, value: 12 })).toBe('12')
})

afterEach(() => {
  delete global.window
  delete global.document
})

it('does not write array values for single-select controls', () => {
  const set = vi.fn()
  const customize = vi.fn(() => ({ set }))
  global.window = { wp: { customize } }

  handleChange({
    target: {
      nodeName: 'SELECT',
      multiple: false,
      options: [{ value: 'home', selected: true }],
      dataset: {
        wpcBuilderSelectSetting: 'acme_select',
      },
    },
  })

  expect(customize).not.toHaveBeenCalled()
  expect(set).not.toHaveBeenCalled()
})

it('writes selected values as an array for multiple-select controls', () => {
  const set = vi.fn()
  const customize = vi.fn(() => ({ set }))
  global.window = { wp: { customize } }

  handleChange({
    target: {
      nodeName: 'SELECT',
      multiple: true,
      options: [
        { value: 'home', selected: true },
        { value: 'about', selected: false },
        { value: 'contact', selected: true },
      ],
      dataset: {
        wpcBuilderSelectSetting: 'acme_select',
      },
    },
  })

  expect(customize).toHaveBeenCalledWith('acme_select')
  expect(set).toHaveBeenCalledWith(['home', 'contact'])
})

it('returns false and keeps native control when wp components are unavailable', () => {
  const mount = vi.fn(() => true)

  const didMount = tryEnhanceSelect({
    wp: { customize: () => ({ get: () => '' }) },
    element: {
      nodeName: 'SELECT',
      multiple: false,
      options: [],
      dataset: {
        wpcBuilderSelectSetting: 'acme_select',
      },
    },
    mountEnhancedSelectImpl: mount,
  })

  expect(didMount).toBe(false)
  expect(mount).not.toHaveBeenCalled()
})

it('attempts mount with wp-core payload when enhancement prerequisites exist', () => {
  const mount = vi.fn(() => true)
  const customize = vi.fn(() => ({ get: () => ['home'] }))
  const controlRoot = {
    querySelector: (selector) => (
      selector === '.customize-control-title'
        ? { textContent: 'Homepage selector' }
        : null
    ),
  }

  const didMount = tryEnhanceSelect({
    wp: {
      customize,
      element: {
        createElement: () => null,
        render: () => null,
        useEffect: () => null,
        useState: () => null,
      },
      components: {
        ComboboxControl: () => null,
        FormTokenField: () => null,
      },
    },
    element: {
      nodeName: 'SELECT',
      multiple: true,
      options: [{ value: 'home', text: 'Home' }],
      closest: () => controlRoot,
      dataset: {
        wpcBuilderSelectSetting: 'acme_select',
        wpcBuilderSelectMaxSelectionNumber: '4',
      },
    },
    mountEnhancedSelectImpl: mount,
  })

  expect(didMount).toBe(true)
  expect(customize).toHaveBeenCalledWith('acme_select')
  expect(mount).toHaveBeenCalledWith(expect.objectContaining({
    element: expect.any(Object),
    settingId: 'acme_select',
    multiple: true,
    choices: [{ value: 'home', label: 'Home' }],
    initialValue: ['home'],
    maxSelectionNumber: 4,
    label: 'Homepage selector',
  }))
})

it('renders ComboboxControl in single mode and writes scalar values', () => {
  const set = vi.fn()
  const bind = vi.fn()
  const unbind = vi.fn()
  const setting = { set, bind, unbind }
  const customize = vi.fn(() => setting)
  const comboboxControl = vi.fn(() => null)
  const select = {
    options: [
      { value: 'home', selected: true },
      { value: 'about', selected: false },
    ],
    value: 'home',
    disabled: false,
    tabIndex: 0,
    setAttribute: vi.fn(),
    insertAdjacentElement: vi.fn(),
  }

  global.document = new JSDOM('<!doctype html><html><body></body></html>').window.document

  const wp = {
    customize,
    element: {
      createElement: vi.fn((component, props) => {
        if (typeof component === 'function') {
          return component(props ?? {})
        }

        return { component, props }
      }),
      render: vi.fn(),
      useEffect: (callback) => callback(),
      useState: (initial) => [initial, vi.fn()],
    },
    components: {
      ComboboxControl: comboboxControl,
      FormTokenField: vi.fn(() => null),
    },
    i18n: {
      __: (text) => text,
    },
  }

  const didMount = mountEnhancedSelect({
    wp,
    element: select,
    settingId: 'acme_select',
    multiple: false,
    choices: [
      { value: 'home', label: 'Home' },
      { value: 'about', label: 'About' },
    ],
    initialValue: 'home',
    label: 'Homepage selector',
    maxSelectionNumber: null,
  })

  expect(didMount).toBe(true)
  expect(comboboxControl).toHaveBeenCalledWith(expect.objectContaining({
    label: '',
    'aria-label': 'Homepage selector',
    placeholder: 'Start typing to search options',
    value: 'home',
    options: [
      { value: 'home', label: 'Home' },
      { value: 'about', label: 'About' },
    ],
  }))

  const props = comboboxControl.mock.calls[0][0]
  props.onChange('about')

  expect(set).toHaveBeenCalledWith('about')
  expect(select.value).toBe('about')
})

it('renders FormTokenField in multiple mode and writes value arrays', () => {
  const setting = { set: vi.fn(), bind: vi.fn(), unbind: vi.fn() }
  const customize = vi.fn(() => setting)
  const formTokenField = vi.fn(() => null)
  const select = {
    options: [
      { value: 'home', selected: true },
      { value: 'about', selected: false },
    ],
    value: '',
    disabled: false,
    tabIndex: 0,
    setAttribute: vi.fn(),
    insertAdjacentElement: vi.fn(),
  }

  const wp = {
    customize,
    element: {
      createElement: vi.fn((component, props) => {
        if (typeof component === 'function') {
          return component(props ?? {})
        }

        return { component, props }
      }),
      render: vi.fn(),
      useEffect: (callback) => callback(),
      useState: (initial) => [initial, vi.fn()],
    },
    components: {
      ComboboxControl: vi.fn(() => null),
      FormTokenField: formTokenField,
    },
    i18n: {
      __: (text) => text,
    },
  }
  global.document = new JSDOM('<!doctype html><html><body></body></html>').window.document

  const didMount = mountEnhancedSelect({
    wp,
    element: select,
    settingId: 'acme_select',
    multiple: true,
    choices: [
      { value: 'home', label: 'Home' },
      { value: 'about', label: 'About' },
    ],
    initialValue: ['home'],
    label: 'Homepage selector',
    maxSelectionNumber: 3,
  })

  expect(didMount).toBe(true)
  expect(formTokenField).toHaveBeenCalledWith(expect.objectContaining({
    label: '',
    'aria-label': 'Homepage selector',
    placeholder: 'Start typing to search options',
    value: ['Home'],
    suggestions: ['Home', 'About'],
  }))

  const props = formTokenField.mock.calls[0][0]
  props.onChange(['About'])

  expect(setting.set).toHaveBeenCalledWith(['about'])
  expect(select.options[0].selected).toBe(false)
  expect(select.options[1].selected).toBe(true)
  expect(select.disabled).toBe(true)
  expect(select.tabIndex).toBe(-1)
  expect(select.setAttribute).toHaveBeenCalledWith('aria-hidden', 'true')

  setting.set.mockClear()
  props.onChange(['Home', 'free-typed-unknown'])
  expect(setting.set).toHaveBeenCalledWith(['home'])
})

it('binds bootstrap init and control add hook for enhancement scans', () => {
  const addEventListener = vi.fn()
  const bind = vi.fn()
  const querySelectorAll = vi.fn(() => [])
  const controlQuerySelectorAll = vi.fn(() => [])
  const win = {
    wp: {
      customize: {
        control: { bind },
      },
    },
  }
  const doc = {
    addEventListener,
    querySelectorAll,
  }

  bootstrapSelectEnhancements({ win, doc })

  expect(addEventListener).toHaveBeenCalledWith('change', expect.any(Function))
  expect(querySelectorAll).toHaveBeenCalledWith('select[data-wpc-builder-select-ui="enhance"]')
  expect(bind).toHaveBeenCalledWith('add', expect.any(Function))

  const addHandler = bind.mock.calls[0][1]
  addHandler({ container: [{ querySelectorAll: controlQuerySelectorAll }] })

  expect(controlQuerySelectorAll).toHaveBeenCalledWith('select[data-wpc-builder-select-ui="enhance"]')
})

it('canUseCoreComponents returns false when hooks are absent', () => {
  const base = {
    element: {
      createElement: () => null,
      render: () => null,
      useEffect: () => null,
      useState: () => null,
    },
    components: {
      ComboboxControl: () => null,
      FormTokenField: () => null,
    },
  }

  expect(canUseCoreComponents(base)).toBe(true)
  expect(canUseCoreComponents({ ...base, element: { ...base.element, useEffect: undefined } })).toBe(false)
  expect(canUseCoreComponents({ ...base, element: { ...base.element, useState: undefined } })).toBe(false)
})
