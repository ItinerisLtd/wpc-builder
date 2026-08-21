import { renderElement } from './template.js'
import { translate } from './i18n.js'

export function canUseCoreComponents (wp) {
  return typeof wp?.element?.createElement === 'function'
    && typeof wp?.element?.render === 'function'
    && typeof wp?.element?.useEffect === 'function'
    && typeof wp?.element?.useState === 'function'
    && typeof wp?.components?.ComboboxControl === 'function'
    && typeof wp?.components?.FormTokenField === 'function'
}

function asArrayValue (value) {
  if (Array.isArray(value)) {
    return value.map((item) => String(item))
  }

  if (value == null || value === '') {
    return []
  }

  return [String(value)]
}

function asScalarValue (value) {
  return value == null ? '' : String(value)
}

function normaliseChoiceLabel (choice) {
  const label = String(choice?.label ?? '')

  return '' === label ? String(choice?.value ?? '') : label
}

function tokenChoicesFromChoices (choices) {
  const labelCounts = new Map()

  for (const choice of choices) {
    const label = normaliseChoiceLabel(choice)

    labelCounts.set(label, (labelCounts.get(label) ?? 0) + 1)
  }

  return choices.map((choice) => {
    const value = String(choice.value)
    const label = normaliseChoiceLabel(choice)
    const token = (labelCounts.get(label) ?? 0) > 1
      ? `${label} (${value})`
      : label

    return { value, token }
  })
}

function setNativeValue (element, value, multiple) {
  if (!element?.options) {
    return
  }

  if (multiple) {
    const values = new Set(asArrayValue(value))

    for (const option of element.options) {
      option.selected = values.has(String(option.value))
    }

    return
  }

  element.value = asScalarValue(value)
}

function deactivateNativeSelect (element) {
  element.disabled = true
  element.tabIndex = -1
  element.setAttribute('aria-hidden', 'true')
  element.setAttribute('hidden', 'hidden')
}

export function mountEnhancedSelect ({
  wp,
  element,
  settingId,
  multiple,
  choices,
  initialValue,
  label = '',
  // This remains a soft hint input only. The authoritative cap stays in the
  // PHP sanitiser (`Fields\Select`) to preserve server-side parity.
  maxSelectionNumber: _maxSelectionNumber,
}) {
  if (!canUseCoreComponents(wp) || !settingId || !element) {
    return false
  }

  const setting = wp.customize?.(settingId)

  if (!setting || typeof setting.set !== 'function') {
    return false
  }

  const mountPoint = renderElement(document, '<div class="wpc-builder-select__enhanced-ui"></div>')

  element.insertAdjacentElement('afterend', mountPoint)

  const { createElement, render, useEffect, useState } = wp.element
  const { ComboboxControl, FormTokenField } = wp.components
  const accessibleLabel = label || translate('Select options', wp)
  const searchPlaceholder = translate('Start typing to search options', wp)
  const tokenChoices = multiple ? tokenChoicesFromChoices(choices) : []
  const tokenByValue = new Map(tokenChoices.map((choice) => [choice.value, choice.token]))
  const valueByToken = new Map(tokenChoices.map((choice) => [choice.token, choice.value]))

  const EnhancedSelect = () => {
    const [value, setValue] = useState(initialValue)

    useEffect(() => {
      if (typeof setting.bind !== 'function') {
        return undefined
      }

      const listener = (nextValue) => {
        const normalised = multiple ? asArrayValue(nextValue) : asScalarValue(nextValue)

        setValue(normalised)
        setNativeValue(element, normalised, multiple)
      }

      setting.bind(listener)

      if (typeof setting.unbind !== 'function') {
        return undefined
      }

      return () => setting.unbind(listener)
    }, [])

    if (multiple) {
      return createElement(FormTokenField, {
        label: '',
        'aria-label': accessibleLabel,
        placeholder: searchPlaceholder,
        value: asArrayValue(value).map((v) => tokenByValue.get(v) ?? v),
        suggestions: tokenChoices.map((choice) => choice.token),
        __nextHasNoMarginBottom: true,
        onChange: (nextTokens) => {
          const knownTokens = asArrayValue(nextTokens).filter((token) => valueByToken.has(token))
          const normalised = knownTokens.map((token) => valueByToken.get(token) ?? token)

          setValue(normalised)
          setting.set(normalised)
          setNativeValue(element, normalised, true)
        },
      })
    }

    return createElement(ComboboxControl, {
      label: '',
      'aria-label': accessibleLabel,
      placeholder: searchPlaceholder,
      value: asScalarValue(value),
      options: choices,
      __nextHasNoMarginBottom: true,
      onChange: (nextValue) => {
        const normalised = asScalarValue(nextValue)

        setValue(normalised)
        setting.set(normalised)
        setNativeValue(element, normalised, false)
      },
    })
  }

  try {
    render(createElement(EnhancedSelect), mountPoint)
    setNativeValue(element, initialValue, multiple)
    deactivateNativeSelect(element)

    return true
  } catch (error) {
    mountPoint.remove()

    return false
  }
}
