
import { canUseCoreComponents, mountEnhancedSelect } from './select-enhanced-ui.js'

export function selectedValues (options) {
  const values = []

  for (const option of options) {
    if (option.selected) {
      values.push(option.value)
    }
  }

  return values
}

export function normaliseChoices (options) {
  return Array.from(options, (option) => ({ value: String(option.value), label: String(option.text ?? option.label ?? '') }))
}

export function resolveInitialValue ({ multiple, value }) {
  if (!multiple) {
    return value == null ? '' : String(value)
  }

  if (Array.isArray(value)) {
    return value.map((v) => String(v))
  }

  if (value == null || value === '') {
    return []
  }

  return [String(value)]
}

export function handleChange (event) {
  const select = event.target

  if (!select || select.nodeName !== 'SELECT') {
    return
  }

  if (!select.multiple) {
    return
  }

  const settingId = select?.dataset?.wpcBuilderSelectSetting ?? null

  if (!settingId || !window?.wp?.customize) {
    return
  }

  const setting = window.wp.customize(settingId)

  if (!setting) {
    return
  }

  setting.set(selectedValues(select.options))
}

function resolveSelectLabel (element) {
  const ariaLabel = element?.getAttribute?.('aria-label')?.trim()

  if (ariaLabel) {
    return ariaLabel
  }

  const controlTitle = element.closest('.customize-control')?.querySelector('.customize-control-title')?.textContent?.trim()

  if (controlTitle) {
    return controlTitle
  }

  return ''
}

function maxSelectionNumber (element) {
  const raw = element?.dataset?.wpcBuilderSelectMaxSelectionNumber ?? ''

  if (raw === '') {
    return null
  }

  const parsed = Number.parseInt(raw, 10)

  return Number.isNaN(parsed) ? null : parsed
}

export function tryEnhanceSelect ({
  wp,
  element,
  mountEnhancedSelectImpl = mountEnhancedSelect,
}) {
  if (!canUseCoreComponents(wp)) {
    return false
  }

  if (!element || element.nodeName !== 'SELECT') {
    return false
  }

  const settingId = element?.dataset?.wpcBuilderSelectSetting ?? null

  if (!settingId || typeof wp?.customize !== 'function') {
    return false
  }

  const setting = wp.customize(settingId)

  if (!setting || typeof setting.get !== 'function') {
    return false
  }

  return mountEnhancedSelectImpl({
    wp,
    element,
    settingId,
    multiple: Boolean(element.multiple),
    choices: normaliseChoices(element.options ?? []),
    initialValue: resolveInitialValue({
      multiple: Boolean(element.multiple),
      value: setting.get(),
    }),
    // Soft hint only: PHP sanitisation remains authoritative for the cap.
    maxSelectionNumber: maxSelectionNumber(element),
    label: resolveSelectLabel(element),
  })
}

function enhanceVisibleSelects (wp, root = document) {
  const selects = root.querySelectorAll?.('select[data-wpc-builder-select-ui="enhance"]') ?? []

  for (const select of selects) {
    if (select.dataset.wpcBuilderSelectEnhanced === '1') {
      continue
    }

    const mounted = tryEnhanceSelect({ wp, element: select })

    select.dataset.wpcBuilderSelectEnhanced = mounted ? '1' : '0'
  }
}

export function bootstrapSelectEnhancements ({
  win = window,
  doc = document,
} = {}) {
  const wp = win?.wp

  doc.addEventListener('change', handleChange)
  enhanceVisibleSelects(wp, doc)

  if (typeof wp?.customize?.control?.bind !== 'function') {
    return
  }

  wp.customize.control.bind('add', (control) => {
    const root = control?.container?.[0] ?? control?.container ?? doc

    enhanceVisibleSelects(wp, root)
  })
}

if (typeof window !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrapSelectEnhancements)
  } else {
    bootstrapSelectEnhancements()
  }
}
