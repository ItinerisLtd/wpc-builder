
import { translate } from './i18n.js'

export function isUnset (value) {
  return null === value || undefined === value || '' === value
}

export function readoutText (value) {
  return isUnset(value) ? translate('not set') : String(value)
}

export function rangeValueFor (value, min) {
  return isUnset(value) ? min : String(value)
}

function bindSlider (control) {
  const container = control.container?.[0]

  if (!container) {
    return
  }

  const wrapper = container.querySelector('[data-wpc-builder-slider]')

  if (!wrapper) {
    return
  }

  const range = wrapper.querySelector('.wpc-builder-slider__range')
  const output = wrapper.querySelector('.wpc-builder-slider__value')

  if (!range || !output) {
    return
  }

  const renderFromSetting = () => {
    const value = control.setting ? control.setting() : range.value

    range.value = rangeValueFor(value, range.min)
    output.textContent = readoutText(value)
  }

  const renderFromInput = () => {
    output.textContent = range.value
  }

  range.addEventListener('input', renderFromInput)
  range.addEventListener('change', renderFromInput)

  if (control.setting) {
    control.setting.bind(renderFromSetting)
  }

  renderFromSetting()
}

function bindWhenEmbedded (control) {
  if ('wpc-builder-slider' !== control.params.type) {
    return
  }

  control.deferred.embedded.done(() => {
    bindSlider(control)
  })
}

function init () {
  if (!window.wp?.customize) {
    return
  }

  window.wp.customize.control.each(bindWhenEmbedded)
  window.wp.customize.control.bind('add', bindWhenEmbedded)
}

if ('undefined' !== typeof document) {
  if ('loading' === document.readyState) {
    document.addEventListener('DOMContentLoaded', init)
  } else {
    init()
  }
}
