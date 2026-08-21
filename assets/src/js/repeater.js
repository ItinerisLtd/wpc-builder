import { compileTemplate, renderElement } from './template.js'
import { format, translate, translatePlural } from './i18n.js'
import { normaliseLinkValue, readLinkGroupValue, seedLinkGroup } from './link.js'
import { decorateUrlInput } from './url.js'

export function encode (rows) {
  return encodeURIComponent(JSON.stringify(rows))
}

export function decode (value) {
  if (!value) {
    return []
  }

  try {
    const parsed = JSON.parse(decodeURIComponent(value))

    return Array.isArray(parsed) ? parsed : []
  } catch (error) {
    return []
  }
}

export function seedRows (settingValue, paramsValue, hiddenInputValue) {
  const fromParams = coerceRows(paramsValue)

  if (null !== fromParams && fromParams.length) {
    return fromParams
  }

  const fromSetting = coerceRows(settingValue)

  if (null !== fromSetting) {
    return fromSetting
  }

  return decode(hiddenInputValue)
}

function coerceRows (value) {
  if (Array.isArray(value)) {
    return value
  }

  if ('string' === typeof value) {
    return decode(value)
  }

  if (value && 'object' === typeof value) {
    return Object.values(value)
  }

  return null
}

const NATIVE_INPUT_TYPES = ['text', 'url', 'email', 'number', 'date']

const IMAGE_SUBFIELD_TYPES = ['image', 'cropped_image', 'upload']

const NON_SCALAR_SUBFIELD_TYPES = [...IMAGE_SUBFIELD_TYPES, 'radio', 'radio-image', 'color']

function isUrlFieldType (type) {
  return type === 'url' || type === 'link'
}

const CHECKBOX_TEMPLATE = compileTemplate(
  '<input type="checkbox" data-field="{{ data.fieldId }}"<# if (data.checked) { #> checked<# } #>>',
)

const SELECT_TEMPLATE = compileTemplate('<select data-field="{{ data.fieldId }}"<# if (data.multiple) { #> multiple<# } #>><# _.each(data.options, function (option) { #><option value="{{ option.value }}"<# if (option.selected) { #> selected<# } #>>{{ option.label }}</option><# }); #></select>')

// The leading \n is deliberate: the HTML parser discards exactly one
// U+000A right after a <textarea> start tag, so this sacrificial
// newline absorbs that discard and lets a genuine leading newline in
// data.value survive.
const TEXTAREA_TEMPLATE = compileTemplate('<textarea data-field="{{ data.fieldId }}">\n{{ data.value }}</textarea>')

const GENERIC_INPUT_TEMPLATE = compileTemplate(
  '<input type="{{ data.type }}" data-field="{{ data.fieldId }}" value="{{ data.value }}">',
)

function checkboxInput (doc, fieldDef, value) {
  return renderElement(doc, CHECKBOX_TEMPLATE({ fieldId: fieldDef.id, checked: Boolean(value) }))
}

function selectInput (doc, fieldDef, value) {
  const choices = fieldDef.choices || {}
  const multiple = Boolean(fieldDef.multiple)
  const selected = multiple
    ? (Array.isArray(value) ? value.map(String) : [])
    : [String(value)]
  const options = Object.keys(choices).map((choiceValue) => ({
    value: choiceValue,
    label: choices[choiceValue],
    selected: selected.includes(choiceValue),
  }))

  return renderElement(doc, SELECT_TEMPLATE({ fieldId: fieldDef.id, multiple, options }))
}

function textareaInput (doc, fieldDef, value) {
  return renderElement(doc, TEXTAREA_TEMPLATE({ fieldId: fieldDef.id, value: null == value ? '' : String(value) }))
}

function genericInput (doc, fieldDef, value) {
  const type = isUrlFieldType(fieldDef.type)
    ? 'url'
    : (NATIVE_INPUT_TYPES.includes(fieldDef.type) ? fieldDef.type : 'text')

  return renderElement(doc, GENERIC_INPUT_TEMPLATE({
    type,
    fieldId: fieldDef.id,
    value: value == null ? '' : String(value),
  }))
}

const ROW_TEMPLATE = compileTemplate('<details class="wpc-builder-repeater__row" draggable="false" data-index="{{ data.index }}"><summary class="wpc-builder-repeater__row-header"><span class="wpc-builder-repeater__handle" aria-hidden="true"></span><span class="wpc-builder-repeater__row-label">{{ data.label }}</span><button type="button" class="wpc-builder-repeater__remove">{{ data.removeLabel }}</button></summary><div class="wpc-builder-repeater__row-body"></div></details>')

const FIELD_INNER_TEMPLATE = compileTemplate('<# if (data.label) { #><span class="customize-control-title">{{ data.label }}</span><# } #><# if (data.description) { #><span class="description customize-control-description">{{{ data.description }}}</span><# } #>')

const FIELD_LABEL_WRAPPER_TEMPLATE = compileTemplate('<label class="wpc-builder-repeater__field">{{{ data.inner }}}</label>')

const FIELD_DIV_WRAPPER_TEMPLATE = compileTemplate('<div class="wpc-builder-repeater__field">{{{ data.inner }}}</div>')

function createRowElement (doc, row, index, rowLabel) {
  return renderElement(doc, ROW_TEMPLATE({
    index,
    label: rowLabelFor(row, index, rowLabel),
    removeLabel: translate('Remove'),
  }))
}

function createFieldWrapper (doc, fieldDef, inputElement) {
  const isNonScalarEditor = NON_SCALAR_SUBFIELD_TYPES.includes(fieldDef.type)
    || inputElement.dataset?.linkGroup === '1'
  const inner = FIELD_INNER_TEMPLATE({ label: fieldDef.label || '', description: fieldDef.description || '' })
  const template = isNonScalarEditor ? FIELD_DIV_WRAPPER_TEMPLATE : FIELD_LABEL_WRAPPER_TEMPLATE
  const wrapper = renderElement(doc, template({ inner }))

  wrapper.appendChild(inputElement)

  return wrapper
}

function rowLabelFor (row, index, rowLabel) {
  /* translators: %s: row number. */
  const fallback = format(translate('Row %s'), index + 1)

  if (!rowLabel) {
    return fallback
  }

  if (rowLabel.type === 'field' && rowLabel.field) {
    const value = row[rowLabel.field]

    if (!Array.isArray(value) && value && typeof value === 'object') {
      const linkLabel = value.text || value.url

      if (linkLabel) {
        return String(linkLabel)
      }
    } else if (value !== undefined && value !== null && value !== '') {
      return String(value)
    }
  }

  return rowLabel.value || fallback
}

export function normaliseImageValue (value) {
  if (value && typeof value === 'object') {
    return {
      id: value.id ?? '',
      url: value.url ?? '',
      filename: value.filename ?? '',
    }
  }

  if (value) {
    return { id: value, url: '', filename: '' }
  }

  return { id: '', url: '', filename: '' }
}

const IMAGE_PREVIEW_TEMPLATE = compileTemplate(
  '<# if (data.url) { #><img src="{{ data.url }}" alt="{{ data.filename }}"><# } else { #>{{ data.emptyLabel }}<# } #>',
)

const IMAGE_PICKER_TEMPLATE = compileTemplate('<div class="wpc-builder-repeater__image-picker"><div class="wpc-builder-repeater__image-preview">{{{ data.previewHtml }}}</div><button type="button" class="button wpc-builder-repeater__image-add">{{ data.addLabel }}</button><button type="button" class="button-link wpc-builder-repeater__image-remove"<# if (!data.hasImage) { #> hidden<# } #>>{{ data.removeLabel }}</button><input type="hidden" data-field="{{ data.fieldId }}" value="{{ data.value }}"></div>')

const RADIO_GROUP_TEMPLATE = compileTemplate('<div class="wpc-builder-repeater__radio-group"><# _.each(data.options, function (option) { #><label><input type="radio" name="{{ data.groupName }}" value="{{ option.value }}" data-field="{{ data.fieldId }}"<# if (option.checked) { #> checked<# } #>> {{ option.label }}</label><# }); #></div>')

// An <input type="color"> cannot hold an empty value and cannot hold
// rgba()/hsla(), so the text input is the stored value and the swatch
// mirrors it whenever it parses as hex. The swatch is a pointer
// convenience only, hence aria-hidden and out of the tab order. The
// dimmed class tracks the swatch, not the value: a stored rgba() leaves
// the swatch on a colour it cannot represent.
const COLOR_GROUP_TEMPLATE = compileTemplate('<div class="wpc-builder-repeater__color<# if (!data.swatch) { #> wpc-builder-repeater__color--empty<# } #>" data-field="{{ data.fieldId }}" data-color-group="1"><input type="color" class="wpc-builder-repeater__color-picker" tabindex="-1" aria-hidden="true"<# if (data.swatch) { #> value="{{ data.swatch }}"<# } #>><input type="text" class="wpc-builder-repeater__color-value" value="{{ data.value }}"<# if (data.label) { #> aria-label="{{ data.label }}"<# } #>></div>')

const LIMIT_NOTICE_MARKUP = '<p class="wpc-builder-repeater__limit-notice"></p>'

function imagePreviewHtml (current) {
  return IMAGE_PREVIEW_TEMPLATE({
    url: current.url,
    filename: current.filename,
    emptyLabel: translate('No image selected'),
  })
}

function renderImagePreview (doc, container, current) {
  container.innerHTML = imagePreviewHtml(current)
}

function openMediaFrame (onSelect) {
  if (!window.wp?.media) {
    return
  }

  const frame = window.wp.media({ multiple: false })

  frame.on('select', () => {
    const selection = frame.state().get('selection').first()

    onSelect(selection.toJSON())
  })

  frame.open()
}

function createImagePicker (doc, fieldDef, value) {
  const current = normaliseImageValue(value)

  return renderElement(doc, IMAGE_PICKER_TEMPLATE({
    fieldId: fieldDef.id,
    value: current.id ? String(current.id) : '',
    previewHtml: imagePreviewHtml(current),
    addLabel: current.url ? translate('Change image') : translate('Add image'),
    removeLabel: translate('Remove'),
    hasImage: Boolean(current.url),
  }))
}

function createDropdownPagesInput (doc, fieldDef, value) {
  if (!fieldDef.dropdown) {
    return null
  }

  // fieldDef.dropdown is PHP-sourced (wp_dropdown_pages()), not one of
  // this file's own templates, so it isn't guaranteed to have <select>
  // as its own root element. Parse into a container and search it
  // instead of using renderElement()'s firstElementChild contract.
  const container = doc.createElement('div')

  container.innerHTML = fieldDef.dropdown

  const select = container.querySelector('select')

  if (!select) {
    return null
  }

  select.removeAttribute('name')
  select.dataset.field = fieldDef.id
  select.value = value == null || value === '' ? '0' : String(value)

  return select
}

function isLegacyLinkString (value) {
  return typeof value === 'string' && value !== ''
}

function createLegacyLinkInput (doc, fieldDef, value) {
  const input = genericInput(doc, { id: fieldDef.id, type: 'url' }, value)

  decorateUrlInput(input)

  return input
}

function createLinkGroupInput (doc, fieldDef, value) {
  if (!fieldDef.link) {
    return genericInput(doc, fieldDef, value)
  }

  const container = doc.createElement('div')

  container.innerHTML = fieldDef.link

  const group = container.querySelector('.wpc-builder-link')

  if (!group) {
    return genericInput(doc, fieldDef, value)
  }

  group.dataset.field = fieldDef.id
  group.dataset.linkGroup = '1'
  seedLinkGroup(group, normaliseLinkValue(value))

  return group
}

function createRadioGroup (doc, fieldDef, value) {
  const choices = fieldDef.choices || {}
  const groupName = `wpc-builder-repeater-radio-${fieldDef.id}-${Math.random().toString(36).slice(2)}`
  const options = Object.keys(choices).map((choiceValue) => ({
    value: choiceValue,
    label: choices[choiceValue],
    checked: choiceValue === String(value),
  }))

  return renderElement(doc, RADIO_GROUP_TEMPLATE({ fieldId: fieldDef.id, groupName, options }))
}

export function colorSwatchValue (value) {
  const matches = /^#([\da-f]{3}|[\da-f]{6}|[\da-f]{8})$/i.exec(String(value ?? ''))

  if (!matches) {
    return ''
  }

  const digits = matches[1].toLowerCase()

  if (3 === digits.length) {
    return `#${digits[0]}${digits[0]}${digits[1]}${digits[1]}${digits[2]}${digits[2]}`
  }

  return `#${digits.slice(0, 6)}`
}

function createColorGroup (doc, fieldDef, value) {
  const current = 'string' === typeof value ? value : ''

  return renderElement(doc, COLOR_GROUP_TEMPLATE({
    fieldId: fieldDef.id,
    label: fieldDef.label || '',
    value: current,
    swatch: colorSwatchValue(current),
  }))
}

function syncColorGroup (group, target) {
  const picker = group.querySelector('.wpc-builder-repeater__color-picker')
  const valueInput = group.querySelector('.wpc-builder-repeater__color-value')

  if (target === picker) {
    valueInput.value = picker.value
  }

  const swatch = colorSwatchValue(valueInput.value)

  if (swatch && target !== picker) {
    picker.value = swatch
  }

  group.classList.toggle('wpc-builder-repeater__color--empty', '' === swatch)

  return valueInput.value
}

export function createSubfieldInput (doc, fieldDef, value) {
  if (fieldDef.type === 'checkbox') {
    return checkboxInput(doc, fieldDef, value)
  }

  if (fieldDef.type === 'select') {
    return selectInput(doc, fieldDef, value)
  }

  if (fieldDef.type === 'textarea') {
    return textareaInput(doc, fieldDef, value)
  }

  if (fieldDef.type === 'color') {
    return createColorGroup(doc, fieldDef, value)
  }

  if (IMAGE_SUBFIELD_TYPES.includes(fieldDef.type)) {
    return createImagePicker(doc, fieldDef, value)
  }

  if (fieldDef.type === 'wpc-builder-link') {
    return isLegacyLinkString(value)
      ? createLegacyLinkInput(doc, fieldDef, value)
      : createLinkGroupInput(doc, fieldDef, value)
  }

  if (fieldDef.type === 'dropdown-pages') {
    const dropdown = createDropdownPagesInput(doc, fieldDef, value)

    if (dropdown != null) {
      return dropdown
    }
  }

  if (fieldDef.type === 'radio' || fieldDef.type === 'radio-image') {
    return createRadioGroup(doc, fieldDef, value)
  }

  return genericInput(doc, fieldDef, value)
}

function validateUrlInputElement (inputElement) {
  return typeof window.wpcBuilderUrlValidation?.validateUrlInputElement === 'function'
    ? window.wpcBuilderUrlValidation.validateUrlInputElement(inputElement)
    : true
}

function readSubfieldValue (element) {
  if (element.type === 'checkbox') {
    return element.checked
  }

  if (element.tagName === 'SELECT' && element.multiple) {
    return Array.from(element.selectedOptions).map((option) => option.value)
  }

  return element.value
}

export function createRepeaterUi (control, container, hiddenInput) {
  const doc = container.ownerDocument
  const field = control.params.field || {}
  const fieldDefs = field.fields || {}
  const fieldIds = Object.keys(fieldDefs)
  const rowLabel = field.row_label
  const limit = typeof field.limit === 'number' && field.limit > 0 ? field.limit : null
  const buttonLabel = field.button_label || translate('Add row')

  const settingValue = typeof control.setting === 'function' ? control.setting() : undefined

  let rows = seedRows(settingValue, control.params.value, hiddenInput.value)

  const list = container.querySelector('[data-repeater]')

  const EventCtor = doc.defaultView?.Event ?? Event
  const ElementCtor = doc.defaultView?.Element ?? Element

  let draggedIndex = null

  let dropIndicatorRow = null

  const imageFieldIds = fieldIds.filter((id) => IMAGE_SUBFIELD_TYPES.includes(fieldDefs[id].type))
  const imageMetaCache = new Map()

  function cacheImageMeta (value) {
    if (!value || typeof value !== 'object' || value.id == null || value.id === '' || !value.url) return

    imageMetaCache.set(String(value.id), { url: value.url, filename: value.filename ?? '' })
  }

  function hydrateImageValue (value) {
    if (value == null || typeof value === 'object') return value

    const meta = imageMetaCache.get(String(value))

    return meta ? { id: value, url: meta.url, filename: meta.filename } : value
  }

  rows.forEach((row) => imageFieldIds.forEach((id) => cacheImageMeta(row[id])))

  function clearDropIndicator () {
    if (!dropIndicatorRow) return

    dropIndicatorRow.classList.remove('wpc-builder-repeater__row--drop-before', 'wpc-builder-repeater__row--drop-after')
    dropIndicatorRow = null
  }

  function onFieldEvent (event) {
    const target = event.target
    const fieldEl = target?.closest ? target.closest('[data-field]') : null
    const fieldId = fieldEl?.dataset?.field

    if (!fieldId) return

    const rowElement = fieldEl.closest('.wpc-builder-repeater__row')

    if (!rowElement) return

    const index = Number(rowElement.dataset.index)
    const fieldDef = fieldDefs[fieldId]
    const isLinkGroup = fieldEl.dataset.linkGroup === '1'
    const isColorGroup = fieldEl.dataset.colorGroup === '1'

    let isValid = true

    if (isColorGroup) {
      updateField(index, fieldId, syncColorGroup(fieldEl, target))
    } else if (isLinkGroup) {
      if (target.classList && target.classList.contains('wpc-builder-link__url')) {
        isValid = validateUrlInputElement(target)
      }

      updateField(index, fieldId, readLinkGroupValue(fieldEl))
    } else {
      const isUrlField = Boolean(fieldDef) && (isUrlFieldType(fieldDef.type) || target.type === 'url')

      isValid = !isUrlField || validateUrlInputElement(target)
      updateField(index, fieldId, readSubfieldValue(target))
    }

    const label = rowElement.querySelector('.wpc-builder-repeater__row-label')

    if (label) {
      label.textContent = rowLabelFor(rows[index], index, rowLabel)
    }

    if (!isValid && event.type === 'change' && typeof target.reportValidity === 'function') {
      target.reportValidity()
    }
  }

  function handleImageAdd (button) {
    const picker = button.closest('.wpc-builder-repeater__image-picker')
    const hidden = picker.querySelector('input[type="hidden"]')
    const preview = picker.querySelector('.wpc-builder-repeater__image-preview')
    const removeButton = picker.querySelector('.wpc-builder-repeater__image-remove')

    openMediaFrame((attachment) => {
      cacheImageMeta({ id: attachment.id, url: attachment.url, filename: attachment.filename ?? '' })
      hidden.value = String(attachment.id)
      renderImagePreview(doc, preview, {
        id: attachment.id,
        url: attachment.url,
        filename: attachment.filename ?? '',
      })
      button.textContent = translate('Change image')
      removeButton.hidden = false
      hidden.dispatchEvent(new EventCtor('change', { bubbles: true }))
    })
  }

  function handleImageRemove (button) {
    const picker = button.closest('.wpc-builder-repeater__image-picker')
    const hidden = picker.querySelector('input[type="hidden"]')
    const preview = picker.querySelector('.wpc-builder-repeater__image-preview')
    const addButton = picker.querySelector('.wpc-builder-repeater__image-add')

    hidden.value = ''
    renderImagePreview(doc, preview, { id: '', url: '', filename: '' })
    addButton.textContent = translate('Add image')
    button.hidden = true
    hidden.dispatchEvent(new EventCtor('change', { bubbles: true }))
  }

  function onListClick (event) {
    if (!(event.target instanceof ElementCtor)) return

    const removeButton = event.target.closest('.wpc-builder-repeater__remove')

    if (removeButton) {
      event.preventDefault()
      removeRow(Number(removeButton.closest('.wpc-builder-repeater__row').dataset.index))

      return
    }

    const imageAddButton = event.target.closest('.wpc-builder-repeater__image-add')

    if (imageAddButton) {
      event.preventDefault()
      handleImageAdd(imageAddButton)

      return
    }

    const imageRemoveButton = event.target.closest('.wpc-builder-repeater__image-remove')

    if (imageRemoveButton) {
      event.preventDefault()
      handleImageRemove(imageRemoveButton)

      return
    }

    const header = event.target.closest('.wpc-builder-repeater__row-header')

    if (!header) return

    const rowElement = header.closest('.wpc-builder-repeater__row')

    if (!rowElement || !rowElement.hasAttribute('open')) return

    const rowBody = rowElement.querySelector('.wpc-builder-repeater__row-body')

    event.preventDefault()
    rowBody.style.height = `${rowBody.scrollHeight}px`
    void rowBody.offsetHeight // force reflow before animation
    rowBody.style.height = '0'

    rowBody.addEventListener('transitionend', (transitionEvent) => {
      if (transitionEvent.propertyName !== 'height') return
      rowElement.removeAttribute('open')
      rowBody.style.height = ''
    }, { once: true })
  }

  function onRowMouseDown (event) {
    if (!(event.target instanceof ElementCtor)) return

    const rowElement = event.target.closest('.wpc-builder-repeater__row')

    if (!rowElement) return

    rowElement.draggable = Boolean(event.target.closest('.wpc-builder-repeater__row-header'))
  }

  function onDragStart (event) {
    if (!(event.target instanceof ElementCtor)) return

    const rowElement = event.target.closest('.wpc-builder-repeater__row')

    if (!rowElement || !rowElement.draggable) {
      event.preventDefault()

      return
    }

    event.dataTransfer.setData('text/plain', rowElement.dataset.index)
    draggedIndex = Number(rowElement.dataset.index)

    if (typeof event.dataTransfer.setDragImage !== 'function') {
      rowElement.classList.add('wpc-builder-repeater__row--dragging')

      return
    }

    const header = rowElement.querySelector('.wpc-builder-repeater__row-header')
    const dragImage = header.cloneNode(true)

    dragImage.classList.add('wpc-builder-repeater__drag-image')
    doc.body.appendChild(dragImage)
    event.dataTransfer.setDragImage(dragImage, 12, 12)
    setTimeout(() => dragImage.remove(), 0)

    rowElement.classList.add('wpc-builder-repeater__row--dragging')
  }

  function onDragOver (event) {
    if (!(event.target instanceof ElementCtor)) return

    if (draggedIndex === null) return

    const rowElement = event.target.closest('.wpc-builder-repeater__row')

    if (!rowElement) {
      clearDropIndicator()

      return
    }

    event.preventDefault()

    if (rowElement === dropIndicatorRow) return

    clearDropIndicator()

    const targetIndex = Number(rowElement.dataset.index)

    if (targetIndex === draggedIndex) return

    rowElement.classList.add(targetIndex < draggedIndex ? 'wpc-builder-repeater__row--drop-before' : 'wpc-builder-repeater__row--drop-after')
    dropIndicatorRow = rowElement
  }

  function onDrop (event) {
    if (!(event.target instanceof ElementCtor)) return

    if (draggedIndex === null) return

    const rowElement = event.target.closest('.wpc-builder-repeater__row')

    if (!rowElement) return

    event.preventDefault()

    moveRow(draggedIndex, Number(rowElement.dataset.index))

    clearDropIndicator()
    draggedIndex = null
  }

  function onDragEnd (event) {
    if (!(event.target instanceof ElementCtor)) return

    const rowElement = event.target.closest('.wpc-builder-repeater__row')

    if (rowElement) {
      rowElement.classList.remove('wpc-builder-repeater__row--dragging')
    }

    clearDropIndicator()
    draggedIndex = null
  }

  function onDragLeave (event) {
    if (event.relatedTarget instanceof ElementCtor && list.contains(event.relatedTarget)) return

    clearDropIndicator()
  }

  if (list) {
    list.addEventListener('input', onFieldEvent)
    list.addEventListener('change', onFieldEvent)
    list.addEventListener('click', onListClick)
    list.addEventListener('mousedown', onRowMouseDown)
    list.addEventListener('dragstart', onDragStart)
    list.addEventListener('dragover', onDragOver)
    list.addEventListener('drop', onDrop)
    list.addEventListener('dragend', onDragEnd)
    list.addEventListener('dragleave', onDragLeave)
  }

  const addButton = container.querySelector('.wpc-builder-repeater__add')
  let limitNotice = null

  if (addButton) {
    addButton.textContent = buttonLabel

    if (limit !== null) {
      const controlTitle = container.querySelector('.customize-control-title')

      if (controlTitle) {
        limitNotice = renderElement(doc, LIMIT_NOTICE_MARKUP)
        controlTitle.insertAdjacentElement('afterend', limitNotice)
      }
    }
  }

  function atLimit () {
    return limit !== null && rows.length >= limit
  }

  function persist () {
    hiddenInput.value = encode(rows)
    hiddenInput.dispatchEvent(new EventCtor('change', { bubbles: true }))
  }

  function commitAndRender () {
    persist()
    render()
  }

  function moveRow (from, to) {
    if (from === to || from < 0 || from >= rows.length || to < 0 || to >= rows.length) {
      return
    }

    const [row] = rows.splice(from, 1)

    rows.splice(to, 0, row)
    commitAndRender()
  }

  function removeRow (index) {
    rows.splice(index, 1)
    commitAndRender()
  }

  function addRow () {
    if (atLimit()) {
      render()

      return
    }

    const row = {}

    fieldIds.forEach((id) => {
      row[id] = fieldDefs[id].default || ''
    })

    rows.push(row)
    commitAndRender()
  }

  function updateField (index, fieldId, value) {
    rows[index] = rows[index] || {}
    rows[index][fieldId] = value
    persist()
  }

  function render () {
    if (!list) {
      return
    }

    list.innerHTML = ''

    rows.forEach((row, index) => {
      const rowElement = createRowElement(doc, row, index, rowLabel)
      const body = rowElement.querySelector('.wpc-builder-repeater__row-body')

      fieldIds.forEach((fieldId) => {
        const fieldDef = fieldDefs[fieldId]
        const value = imageFieldIds.includes(fieldId) ? hydrateImageValue(row[fieldId]) : row[fieldId]
        const inputElement = createSubfieldInput(doc, fieldDef, value)
        const wrapper = createFieldWrapper(doc, fieldDef, inputElement)

        if (fieldDef.type === 'wpc-builder-link') {
          const urlInput = inputElement.dataset?.linkGroup === '1'
            ? inputElement.querySelector('.wpc-builder-link__url')
            : inputElement

          if (urlInput) {
            validateUrlInputElement(urlInput)
          }
        } else if (isUrlFieldType(fieldDef.type)) {
          validateUrlInputElement(inputElement)
        }

        body.appendChild(wrapper)
      })

      list.appendChild(rowElement)
    })

    if (limitNotice) {
      /* translators: %s: maximum number of rows. */
      limitNotice.textContent = format(translatePlural('Limit: %s row', 'Limit: %s rows', limit), limit)
      limitNotice.classList.toggle('wpc-builder-repeater__limit-notice--reached', atLimit())
    }
  }

  if (addButton) {
    addButton.addEventListener('click', (event) => {
      event.preventDefault()
      addRow()
    })
  }

  render()
}

function bindWhenEmbedded (control) {
  if (control.params.type !== 'wpc-builder-repeater') {
    return
  }

  if (control._wpcBuilderRepeaterInit) {
    return
  }

  control._wpcBuilderRepeaterInit = true

  control.deferred.embedded.done(() => {
    const container = control.container && control.container[0]

    if (!container) {
      return
    }

    const hiddenInput = container.querySelector('input[type="hidden"]')
    const listContainer = container.querySelector('[data-repeater]')

    if (!hiddenInput || !listContainer) {
      return
    }

    createRepeaterUi(control, container, hiddenInput)
  })
}

function init () {
  if (!window.wp?.customize) {
    return
  }

  window.wp.customize.control.each(bindWhenEmbedded)

  window.wp.customize.control.bind('add', bindWhenEmbedded)
}

if (typeof window !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
  } else {
    init()
  }
}
