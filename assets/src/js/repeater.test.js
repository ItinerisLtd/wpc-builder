// @vitest-environment jsdom
import { describe, expect, it } from 'vitest'
import { colorSwatchValue, createRepeaterUi, createSubfieldInput, decode, encode, normaliseImageValue, seedRows } from './repeater.js'

const WPC_BUILDER_LINK_MARKUP = '<div class="wpc-builder-link">'
  + '<label class="wpc-builder-link__row"><span class="wpc-builder-link__label">Link text</span>'
  + '<input type="text" class="wpc-builder-link__text"></label>'
  + '<label class="wpc-builder-link__row"><span class="wpc-builder-link__label">URL</span>'
  + '<input type="url" class="wpc-builder-link__url"></label>'
  + '<label class="wpc-builder-link__row wpc-builder-link__row--checkbox">'
  + '<span class="wpc-builder-toggle__switch">'
  + '<input type="checkbox" class="wpc-builder-link__target wpc-builder-toggle__input" autocomplete="off">'
  + '<span class="wpc-builder-toggle__slider"></span></span>'
  + '<span class="wpc-builder-link__label">Open in a new tab</span></label>'
  + '</div>'

describe('repeater value encoding', () => {
  it('round-trips rows through the hidden input format', () => {
    const rows = [{ title: 'Home', url: 'https://example.test' }]

    expect(decode(encode(rows))).toEqual(rows)
  })

  it('decodes a legacy encoded value', () => {
    const legacyEncodedValue = encodeURIComponent(JSON.stringify([{ title: 'Home' }]))

    expect(decode(legacyEncodedValue)).toEqual([{ title: 'Home' }])
  })

  it('decodes an empty or malformed value to an empty array', () => {
    expect(decode('')).toEqual([])
    expect(decode('not json')).toEqual([])
    expect(decode('%E0%A4%A')).toEqual([])
  })
})

describe('repeater value encoding: non-array JSON', () => {
  it('decodes valid JSON that is not an array to an empty array', () => {
    expect(decode(encodeURIComponent(JSON.stringify({ title: 'Home' })))).toEqual([])
    expect(decode(encodeURIComponent(JSON.stringify(5)))).toEqual([])
  })
})

describe('seedRows', () => {
  it('seeds rows from params.value when it is a non-empty array, ahead of setting()', () => {
    const fromParams = [{ title: 'Home' }, { title: 'About' }]
    const fromSetting = [{ title: 'Stale' }]

    expect(seedRows(fromSetting, fromParams, 'irrelevant')).toBe(fromParams)
  })

  it('an object-shaped params.value wins over a populated setting()', () => {
    const objectShapedParams = { 0: { title: 'Home' }, 2: { title: 'About' } }
    const fromSetting = [{ title: 'Stale' }]

    expect(seedRows(fromSetting, objectShapedParams, 'irrelevant')).toEqual([
      { title: 'Home' },
      { title: 'About' },
    ])
  })

  it('keeps source precedence: params.value object shape beats setting() rows', () => {
    const fromParams = { 0: { id: 1 }, 2: { id: 2 } }
    const fromSetting = [{ id: 3 }]

    expect(seedRows(fromSetting, fromParams, '')).toEqual([{ id: 1 }, { id: 2 }])
  })

  it('an empty params.value array falls through to setting() rather than short-circuiting', () => {
    const fromSetting = [{ title: 'From setting' }]

    expect(seedRows(fromSetting, [], 'irrelevant')).toBe(fromSetting)
  })

  it('does not silently produce [] when params.value holds real rows, even if the hidden input holds WordPress-corrupted garbage', () => {
    const rows = [{ title: 'Home' }]

    expect(seedRows(undefined, rows, '[object Object]')).toEqual(rows)
    expect(seedRows(undefined, rows, '')).toEqual(rows)
  })

  it('falls back to setting() when params.value is unusable', () => {
    const fromSetting = [{ title: 'From setting' }]

    expect(seedRows(fromSetting, undefined, 'irrelevant')).toBe(fromSetting)
    expect(seedRows(fromSetting, null, 'irrelevant')).toBe(fromSetting)
  })

  it('normalises an object-shaped setting() value to an array when params.value is unusable', () => {
    const objectShapedSetting = { 0: { title: 'Home' }, 2: { title: 'About' } }

    expect(seedRows(objectShapedSetting, undefined, '')).toEqual([
      { title: 'Home' },
      { title: 'About' },
    ])
  })

  it('decodes a source that is itself a string (embedded after the setting was already dirtied)', () => {
    const encoded = encode([{ title: 'Home' }])

    expect(seedRows(undefined, encoded, '')).toEqual([{ title: 'Home' }])
    expect(seedRows(encoded, undefined, '')).toEqual([{ title: 'Home' }])
  })

  it('falls back to decoding the hidden input only when neither params.value nor setting() is usable', () => {
    const encoded = encode([{ title: 'Fallback' }])

    expect(seedRows(undefined, undefined, encoded)).toEqual([{ title: 'Fallback' }])
    expect(seedRows(null, null, encoded)).toEqual([{ title: 'Fallback' }])
  })
})

describe('normaliseImageValue', () => {
  it('passes through an already-rehydrated {id, url, filename} object', () => {
    const value = { id: 42, url: 'https://example.test/photo.jpg', filename: 'photo.jpg' }

    expect(normaliseImageValue(value)).toEqual(value)
  })

  it('keeps a bare id/URL as the id, with no known url/filename to preview', () => {
    expect(normaliseImageValue(42)).toEqual({ id: 42, url: '', filename: '' })
    expect(normaliseImageValue('https://example.test/photo.jpg')).toEqual({
      id: 'https://example.test/photo.jpg',
      url: '',
      filename: '',
    })
  })

  it('treats an empty/falsy value as no image selected', () => {
    expect(normaliseImageValue('')).toEqual({ id: '', url: '', filename: '' })
    expect(normaliseImageValue(null)).toEqual({ id: '', url: '', filename: '' })
    expect(normaliseImageValue(undefined)).toEqual({ id: '', url: '', filename: '' })
  })
})

describe('createSubfieldInput: scalar types', () => {
  it('builds a checkbox input carrying the field id and checked state', () => {
    const element = createSubfieldInput(document, { id: 'agree', type: 'checkbox' }, true)

    expect(element.tagName).toBe('INPUT')
    expect(element.type).toBe('checkbox')
    expect(element.checked).toBe(true)
    expect(element.dataset.field).toBe('agree')
  })

  it('builds a single select with the matching option selected', () => {
    const fieldDef = { id: 'colour', type: 'select', choices: { red: 'Red', blue: 'Blue' } }
    const element = createSubfieldInput(document, fieldDef, 'blue')

    expect(element.tagName).toBe('SELECT')
    expect(element.multiple).toBe(false)
    expect(element.value).toBe('blue')
    expect(Array.from(element.options).map((option) => option.textContent)).toEqual(['Red', 'Blue'])
  })

  it('renders select without extra whitespace text nodes', () => {
    const fieldDef = { id: 'colour', type: 'select', choices: { red: 'Red', blue: 'Blue' } }
    const element = createSubfieldInput(document, fieldDef, 'blue')

    const optionCount = Array.from(element.childNodes).filter((node) => node.nodeType === 1).length
    expect(element.childNodes.length).toBe(optionCount)
  })

  it('builds a multiple select with every matching option selected', () => {
    const fieldDef = { id: 'tags', type: 'select', multiple: true, choices: { a: 'A', b: 'B', c: 'C' } }
    const element = createSubfieldInput(document, fieldDef, ['a', 'c'])

    expect(element.multiple).toBe(true)
    expect(Array.from(element.selectedOptions).map((option) => option.value)).toEqual(['a', 'c'])
  })

  it('builds a textarea carrying the current value', () => {
    const element = createSubfieldInput(document, { id: 'notes', type: 'textarea' }, 'hello')

    expect(element.tagName).toBe('TEXTAREA')
    expect(element.value).toBe('hello')
  })

  it('preserves a leading newline in a textarea value (HTML parsers discard exactly one right after the tag)', () => {
    const element = createSubfieldInput(document, { id: 'notes', type: 'textarea' }, '\nhello')

    expect(element.value).toBe('\nhello')
  })

  it('builds a generic text input for unrecognised types', () => {
    const element = createSubfieldInput(document, { id: 'title', type: 'text' }, 'Home')

    expect(element.tagName).toBe('INPUT')
    expect(element.type).toBe('text')
    expect(element.value).toBe('Home')
  })

  it('maps the "link" type to a url input', () => {
    const element = createSubfieldInput(document, { id: 'href', type: 'link' }, 'https://example.test')

    expect(element.type).toBe('url')
    expect(element.value).toBe('https://example.test')
  })

  it('maps a non-empty string value for "wpc-builder-link" to a url input, even with no link markup (see createSubfieldInput: wpc-builder-link below for the compound-UI case)', () => {
    const element = createSubfieldInput(document, { id: 'href', type: 'wpc-builder-link' }, 'https://example.test')

    expect(element.type).toBe('url')
    expect(element.value).toBe('https://example.test')
  })
})

describe('createSubfieldInput: image picker', () => {
  it('renders an existing image preview and a hidden field carrying its id', () => {
    const element = createSubfieldInput(
      document,
      { id: 'logo', type: 'image' },
      { id: 9, url: 'https://example.test/logo.png', filename: 'logo.png' },
    )

    const img = element.querySelector('img')
    const hidden = element.querySelector('input[type="hidden"]')

    expect(img.src).toBe('https://example.test/logo.png')
    expect(hidden.value).toBe('9')
    expect(hidden.dataset.field).toBe('logo')
    expect(element.querySelector('.wpc-builder-repeater__image-remove').hidden).toBe(false)
    expect(element.querySelector('.wpc-builder-repeater__image-add').textContent).toBe('Change image')
  })

  it('renders the empty state when no image is set', () => {
    const element = createSubfieldInput(document, { id: 'logo', type: 'image' }, '')

    expect(element.querySelector('.wpc-builder-repeater__image-preview').textContent).toBe('No image selected')
    expect(element.querySelector('.wpc-builder-repeater__image-remove').hidden).toBe(true)
    expect(element.querySelector('.wpc-builder-repeater__image-add').textContent).toBe('Add image')
  })

  // Click-behavior tests for the image picker's add/remove buttons were removed here. Per-element
  // listeners moved to list-level delegation, so clicks are only handled once the picker is embedded
  // under a [data-repeater] list. See createRepeaterUi's image-subfield integration test below for
  // equivalent coverage through the real delegated path.
})

describe('createSubfieldInput: radio group', () => {
  it('marks the option matching the current value as checked, and shares one group name', () => {
    const fieldDef = { id: 'size', type: 'radio', choices: { s: 'Small', m: 'Medium', l: 'Large' } }
    const element = createSubfieldInput(document, fieldDef, 'm')
    const radios = Array.from(element.querySelectorAll('input[type="radio"]'))

    expect(radios.map((radio) => radio.checked)).toEqual([false, true, false])
    expect(radios.every((radio) => radio.dataset.field === 'size')).toBe(true)
    expect(new Set(radios.map((radio) => radio.name)).size).toBe(1)
    expect(element.textContent).toContain('Small')
    expect(element.textContent).toContain('Medium')
    expect(element.textContent).toContain('Large')
  })
})

describe('colorSwatchValue', () => {
  it('expands a shorthand hex, truncates the alpha pair, and lowercases', () => {
    expect(colorSwatchValue('#ABC')).toBe('#aabbcc')
    expect(colorSwatchValue('#11223344')).toBe('#112233')
    expect(colorSwatchValue('#FF0000')).toBe('#ff0000')
  })

  it('returns an empty string for anything the swatch cannot represent', () => {
    expect(colorSwatchValue('')).toBe('')
    expect(colorSwatchValue('rgba(1,2,3,0.4)')).toBe('')
    expect(colorSwatchValue('var(--brand)')).toBe('')
    expect(colorSwatchValue(null)).toBe('')
  })
})

describe('createSubfieldInput: color group', () => {
  it('seeds the text input and the swatch from a stored hex value', () => {
    const element = createSubfieldInput(document, { id: 'tint', type: 'color', label: 'Tint' }, '#ff0000')

    expect(element.dataset.field).toBe('tint')
    expect(element.querySelector('.wpc-builder-repeater__color-value').value).toBe('#ff0000')
    expect(element.querySelector('.wpc-builder-repeater__color-value').getAttribute('aria-label')).toBe('Tint')
    expect(element.querySelector('input[type="color"]').value).toBe('#ff0000')
    expect(element.classList.contains('wpc-builder-repeater__color--empty')).toBe(false)
  })

  it('marks an empty value as empty and keeps the text input empty', () => {
    const element = createSubfieldInput(document, { id: 'tint', type: 'color' }, '')

    expect(element.querySelector('.wpc-builder-repeater__color-value').value).toBe('')
    expect(element.classList.contains('wpc-builder-repeater__color--empty')).toBe(true)
  })

  it('keeps a stored non-hex value editable as text, and dims the swatch that cannot show it', () => {
    const element = createSubfieldInput(document, { id: 'tint', type: 'color' }, 'rgba(1,2,3,0.4)')

    expect(element.querySelector('.wpc-builder-repeater__color-value').value).toBe('rgba(1,2,3,0.4)')
    expect(element.classList.contains('wpc-builder-repeater__color--empty')).toBe(true)
  })

  it('keeps the swatch out of the tab order and hidden from assistive technology', () => {
    const picker = createSubfieldInput(document, { id: 'tint', type: 'color' }, '#ff0000')
      .querySelector('input[type="color"]')

    expect(picker.getAttribute('tabindex')).toBe('-1')
    expect(picker.getAttribute('aria-hidden')).toBe('true')
  })
})

describe('createSubfieldInput: dropdown-pages', () => {
  it('injects data-field and the current value into the PHP-rendered <select>', () => {
    const fieldDef = {
      id: 'parent_page',
      type: 'dropdown-pages',
      dropdown: '<select name=""><option value="0">Select a page</option><option value="4">About</option></select>',
    }
    const element = createSubfieldInput(document, fieldDef, '4')

    expect(element.tagName).toBe('SELECT')
    expect(element.hasAttribute('name')).toBe(false)
    expect(element.dataset.field).toBe('parent_page')
    expect(element.value).toBe('4')
  })

  it('defaults to "0" when no value is set', () => {
    const fieldDef = {
      id: 'parent_page',
      type: 'dropdown-pages',
      dropdown: '<select name=""><option value="0">Select a page</option></select>',
    }
    const element = createSubfieldInput(document, fieldDef, '')

    expect(element.value).toBe('0')
  })

  it('finds the <select> even when a wp_dropdown_pages filter wraps it in another element', () => {
    const fieldDef = {
      id: 'parent_page',
      type: 'dropdown-pages',
      dropdown: '<div class="wrap"><select name=""><option value="0">Select a page</option><option value="4">About</option></select></div>',
    }
    const element = createSubfieldInput(document, fieldDef, '4')

    expect(element.tagName).toBe('SELECT')
    expect(element.hasAttribute('name')).toBe(false)
    expect(element.dataset.field).toBe('parent_page')
    expect(element.value).toBe('4')
  })

  it('finds the <select> even when it is preceded by a sibling element, not just when wrapped', () => {
    const fieldDef = {
      id: 'parent_page',
      type: 'dropdown-pages',
      dropdown: '<label>Page</label><select name=""><option value="0">Select a page</option><option value="7">About</option></select>',
    }
    const element = createSubfieldInput(document, fieldDef, '7')

    expect(element.tagName).toBe('SELECT')
    expect(element.hasAttribute('name')).toBe(false)
    expect(element.dataset.field).toBe('parent_page')
    expect(element.value).toBe('7')
  })

  it('falls back to a generic text input when the PHP-supplied markup has no root element', () => {
    const fieldDef = {
      id: 'parent_page',
      type: 'dropdown-pages',
      dropdown: '   ',
    }
    const element = createSubfieldInput(document, fieldDef, '')

    expect(element.tagName).toBe('INPUT')
    expect(element.type).toBe('text')
  })
})

describe('createSubfieldInput: wpc-builder-link', () => {
  it('parses the PHP-rendered markup and marks it as a link group', () => {
    const fieldDef = { id: 'cta', type: 'wpc-builder-link', link: WPC_BUILDER_LINK_MARKUP }
    const element = createSubfieldInput(document, fieldDef, { url: 'https://example.test', text: 'Visit us', target: '_blank' })

    expect(element.classList.contains('wpc-builder-link')).toBe(true)
    expect(element.dataset.field).toBe('cta')
    expect(element.dataset.linkGroup).toBe('1')
  })

  it('seeds the three inner inputs from the row value', () => {
    const fieldDef = { id: 'cta', type: 'wpc-builder-link', link: WPC_BUILDER_LINK_MARKUP }
    const element = createSubfieldInput(document, fieldDef, { url: 'https://example.test', text: 'Visit us', target: '_blank' })

    expect(element.querySelector('.wpc-builder-link__url').value).toBe('https://example.test')
    expect(element.querySelector('.wpc-builder-link__text').value).toBe('Visit us')
    expect(element.querySelector('.wpc-builder-link__target').checked).toBe(true)
  })

  it('renders a legacy non-empty string row value as a plain url input, not the compound group', () => {
    const fieldDef = { id: 'cta', type: 'wpc-builder-link', link: WPC_BUILDER_LINK_MARKUP }
    const element = createSubfieldInput(document, fieldDef, 'https://legacy.example.test')

    expect(element.classList.contains('wpc-builder-link')).toBe(false)
    expect(element.tagName).toBe('INPUT')
    expect(element.type).toBe('url')
    expect(element.value).toBe('https://legacy.example.test')
    expect(element.dataset.field).toBe('cta')
    expect(element.dataset.linkGroup).toBeUndefined()
  })

  it('decorates the legacy string fallback input the same way as a plain Fields\\Url input', () => {
    const fieldDef = { id: 'cta', type: 'wpc-builder-link', link: WPC_BUILDER_LINK_MARKUP }
    const element = createSubfieldInput(document, fieldDef, 'https://legacy.example.test')

    expect(element.closest('.wpc-builder-url-field__input-wrap')).not.toBeNull()
  })

  it('renders the compound group, not the legacy fallback, for an empty row value', () => {
    const fieldDef = { id: 'cta', type: 'wpc-builder-link', link: WPC_BUILDER_LINK_MARKUP }
    const element = createSubfieldInput(document, fieldDef, '')

    expect(element.classList.contains('wpc-builder-link')).toBe(true)
    expect(element.dataset.linkGroup).toBe('1')
  })

  it('renders the compound group, not the legacy fallback, for an already-migrated object row value', () => {
    const fieldDef = { id: 'cta', type: 'wpc-builder-link', link: WPC_BUILDER_LINK_MARKUP }
    const element = createSubfieldInput(document, fieldDef, { url: 'https://example.test', text: '', target: '_self' })

    expect(element.classList.contains('wpc-builder-link')).toBe(true)
    expect(element.dataset.linkGroup).toBe('1')
  })

  it('falls back to a generic text input when no link markup is supplied', () => {
    const fieldDef = { id: 'cta', type: 'wpc-builder-link' }
    const element = createSubfieldInput(document, fieldDef, '')

    expect(element.tagName).toBe('INPUT')
    expect(element.type).toBe('text')
  })
})

function buildRepeaterFixture ({ fields = {}, value = [], rowLabel, limit, buttonLabel } = {}) {
  document.body.innerHTML = `
    <div class="control-root">
      <span class="customize-control-title">Rows</span>
      <div class="wpc-builder-repeater" data-repeater></div>
      <button type="button" class="wpc-builder-repeater__add"></button>
      <input type="hidden">
    </div>
  `

  const container = document.querySelector('.control-root')
  const hiddenInput = container.querySelector('input[type="hidden"]')
  const field = { fields }

  if (rowLabel) field.row_label = rowLabel
  if (limit) field.limit = limit
  if (buttonLabel) field.button_label = buttonLabel

  const control = { params: { field, value } }

  createRepeaterUi(control, container, hiddenInput)

  return {
    container,
    hiddenInput,
    list: container.querySelector('[data-repeater]'),
    addButton: container.querySelector('.wpc-builder-repeater__add'),
  }
}

describe('createRepeaterUi', () => {
  it('renders one row per seeded value, using row_label when configured', () => {
    const { list } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text', label: 'Title' } },
      value: [{ title: 'Home' }, { title: 'About' }],
      rowLabel: { type: 'field', field: 'title' },
    })

    const labels = Array.from(list.querySelectorAll('.wpc-builder-repeater__row-label')).map((el) => el.textContent)

    expect(labels).toEqual(['Home', 'About'])
  })

  it('stringifies a non-link array value (e.g. a multi-select row_label field) instead of dropping to the fallback', () => {
    const { list } = buildRepeaterFixture({
      fields: { tags: { id: 'tags', type: 'select', multiple: true } },
      value: [{ tags: ['red', 'blue'] }],
      rowLabel: { type: 'field', field: 'tags' },
    })

    const label = list.querySelector('.wpc-builder-repeater__row-label').textContent

    expect(label).toBe('red,blue')
  })

  it('falls back to "Row N" labels when no row_label is configured', () => {
    const { list } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }, { title: 'About' }],
    })

    const labels = Array.from(list.querySelectorAll('.wpc-builder-repeater__row-label')).map((el) => el.textContent)

    expect(labels).toEqual(['Row 1', 'Row 2'])
  })

  it('adds a row seeded with each field default on "Add row" click', () => {
    const { list, addButton } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text', default: 'Untitled' } },
      value: [],
    })

    addButton.dispatchEvent(new Event('click', { bubbles: true, cancelable: true }))

    expect(list.querySelectorAll('.wpc-builder-repeater__row')).toHaveLength(1)
    expect(list.querySelector('input[data-field="title"]').value).toBe('Untitled')
  })

  it('seeds a new row\'s wpc-builder-link compound UI from the field default on "Add row" click', () => {
    const { list, addButton } = buildRepeaterFixture({
      fields: {
        cta: {
          id: 'cta',
          type: 'wpc-builder-link',
          link: WPC_BUILDER_LINK_MARKUP,
          default: { url: '', text: '', target: '_self' },
        },
      },
      value: [],
    })

    addButton.dispatchEvent(new Event('click', { bubbles: true, cancelable: true }))

    const group = list.querySelector('.wpc-builder-link')

    expect(list.querySelectorAll('.wpc-builder-repeater__row')).toHaveLength(1)
    expect(group).not.toBeNull()
    expect(group.dataset.field).toBe('cta')
    expect(group.dataset.linkGroup).toBe('1')
    expect(group.querySelector('.wpc-builder-link__url').value).toBe('')
    expect(group.querySelector('.wpc-builder-link__text').value).toBe('')
  })

  it('renders a new row\'s wpc-builder-link sub-field as the plain legacy input when the field default is a non-empty string', () => {
    const { list, addButton } = buildRepeaterFixture({
      fields: {
        cta: {
          id: 'cta',
          type: 'wpc-builder-link',
          link: WPC_BUILDER_LINK_MARKUP,
          default: 'https://example.test',
        },
      },
      value: [],
    })

    addButton.dispatchEvent(new Event('click', { bubbles: true, cancelable: true }))

    const input = list.querySelector('input[data-field="cta"]')

    expect(list.querySelector('.wpc-builder-link')).toBeNull()
    expect(input.type).toBe('url')
    expect(input.value).toBe('https://example.test')
  })

  it('removes a row on its remove button click', () => {
    const { list } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }, { title: 'About' }],
      rowLabel: { type: 'field', field: 'title' },
    })

    list.querySelector('.wpc-builder-repeater__row .wpc-builder-repeater__remove')
      .dispatchEvent(new Event('click', { bubbles: true, cancelable: true }))

    const labels = Array.from(list.querySelectorAll('.wpc-builder-repeater__row-label')).map((el) => el.textContent)

    expect(labels).toEqual(['About'])
  })

  it('ignores a delegated click whose target is a non-Element node (e.g. a Text node)', () => {
    const { list } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }],
    })

    const label = list.querySelector('.wpc-builder-repeater__row-label')
    const textNode = label.firstChild

    expect(textNode.nodeType).toBe(Node.TEXT_NODE)

    expect(() => textNode.dispatchEvent(new Event('click', { bubbles: true, cancelable: true }))).not.toThrow()
    expect(list.querySelectorAll('.wpc-builder-repeater__row')).toHaveLength(1)
  })

  it('ignores a delegated drag event whose target is a non-Element node', () => {
    const { list } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }, { title: 'About' }],
      rowLabel: { type: 'field', field: 'title' },
    })

    const label = list.querySelector('.wpc-builder-repeater__row-label')
    const textNode = label.firstChild
    const dataTransfer = { data: {}, setData (key, val) { this.data[key] = val }, getData (key) { return this.data[key] } }

    expect(() => {
      textNode.dispatchEvent(Object.assign(new Event('dragstart', { bubbles: true }), { dataTransfer }))
      textNode.dispatchEvent(Object.assign(new Event('dragover', { bubbles: true, cancelable: true }), { dataTransfer }))
      textNode.dispatchEvent(Object.assign(new Event('drop', { bubbles: true, cancelable: true }), { dataTransfer }))
    }).not.toThrow()

    const labels = Array.from(list.querySelectorAll('.wpc-builder-repeater__row-label')).map((el) => el.textContent)

    expect(labels).toEqual(['Home', 'About'])
  })

  it('persists a text subfield edit to the hidden input as encoded rows', () => {
    const { list, hiddenInput } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }],
    })

    const input = list.querySelector('input[data-field="title"]')

    input.value = 'Updated'
    input.dispatchEvent(new Event('input', { bubbles: true }))

    expect(decode(hiddenInput.value)).toEqual([{ title: 'Updated' }])
  })

  it('reorders rows on drag and drop', () => {
    const { list } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }, { title: 'About' }],
      rowLabel: { type: 'field', field: 'title' },
    })

    const [firstRow, secondRow] = list.querySelectorAll('.wpc-builder-repeater__row')
    const dataTransfer = {
      data: {},
      setData (key, val) { this.data[key] = val },
      getData (key) { return this.data[key] },
      setDragImage () {},
    }

    firstRow.querySelector('.wpc-builder-repeater__row-header')
      .dispatchEvent(new Event('mousedown', { bubbles: true }))
    firstRow.dispatchEvent(Object.assign(new Event('dragstart', { bubbles: true }), { dataTransfer }))
    secondRow.dispatchEvent(Object.assign(new Event('drop', { bubbles: true, cancelable: true }), { dataTransfer }))

    const labels = Array.from(list.querySelectorAll('.wpc-builder-repeater__row-label')).map((el) => el.textContent)

    expect(labels).toEqual(['About', 'Home'])
  })

  it('resets drag state after a completed drop, even without a dragend (the dragged row is detached by render() before the browser can fire it)', () => {
    const { list } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }, { title: 'About' }],
      rowLabel: { type: 'field', field: 'title' },
    })

    const [firstRow, secondRow] = list.querySelectorAll('.wpc-builder-repeater__row')
    const dataTransfer = {
      data: {},
      setData (key, val) { this.data[key] = val },
      getData (key) { return this.data[key] },
      setDragImage () {},
    }

    firstRow.querySelector('.wpc-builder-repeater__row-header')
      .dispatchEvent(new Event('mousedown', { bubbles: true }))
    firstRow.dispatchEvent(Object.assign(new Event('dragstart', { bubbles: true }), { dataTransfer }))
    secondRow.dispatchEvent(Object.assign(new Event('drop', { bubbles: true, cancelable: true }), { dataTransfer }))

    // No dragend dispatched here: render() already replaced `list`'s
    // contents synchronously inside the drop handler, so in a real
    // browser the dragged row is detached before dragend can bubble.
    const foreignDataTransfer = {
      data: {},
      setData (key, val) { this.data[key] = val },
      getData (key) { return this.data[key] ?? '' },
      setDragImage () {},
    }

    // The dragged row started at index 0; if draggedIndex were left stale instead of reset to null,
    // dispatching dragover on the re-rendered row now at index 0 would short-circuit on a
    // coincidental targetIndex === draggedIndex match. Target index 1 instead, which stale state
    // would wrongly decorate.
    const rerenderedSecondRow = list.querySelectorAll('.wpc-builder-repeater__row')[1]

    rerenderedSecondRow.dispatchEvent(Object.assign(
      new Event('dragover', { bubbles: true, cancelable: true }),
      { dataTransfer: foreignDataTransfer },
    ))

    expect(rerenderedSecondRow.classList.contains('wpc-builder-repeater__row--drop-before')).toBe(false)
    expect(rerenderedSecondRow.classList.contains('wpc-builder-repeater__row--drop-after')).toBe(false)
  })

  it('clears a stale drop indicator when the pointer moves over the list but off any row, or leaves the list entirely', () => {
    const { list, addButton } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }, { title: 'About' }, { title: 'Contact' }],
      rowLabel: { type: 'field', field: 'title' },
    })

    const [firstRow, , thirdRow] = list.querySelectorAll('.wpc-builder-repeater__row')
    const dataTransfer = {
      data: {},
      setData (key, val) { this.data[key] = val },
      getData (key) { return this.data[key] },
      setDragImage () {},
    }

    firstRow.querySelector('.wpc-builder-repeater__row-header')
      .dispatchEvent(new Event('mousedown', { bubbles: true }))
    firstRow.dispatchEvent(Object.assign(new Event('dragstart', { bubbles: true }), { dataTransfer }))
    thirdRow.dispatchEvent(Object.assign(new Event('dragover', { bubbles: true, cancelable: true }), { dataTransfer }))

    expect(thirdRow.classList.contains('wpc-builder-repeater__row--drop-after')).toBe(true)

    list.dispatchEvent(Object.assign(new Event('dragover', { bubbles: true, cancelable: true }), { dataTransfer }))

    expect(thirdRow.classList.contains('wpc-builder-repeater__row--drop-after')).toBe(false)

    thirdRow.dispatchEvent(Object.assign(new Event('dragover', { bubbles: true, cancelable: true }), { dataTransfer }))

    expect(thirdRow.classList.contains('wpc-builder-repeater__row--drop-after')).toBe(true)

    list.dispatchEvent(Object.assign(new Event('dragleave', { bubbles: true }), { relatedTarget: addButton }))

    expect(thirdRow.classList.contains('wpc-builder-repeater__row--drop-after')).toBe(false)
  })

  it('shows a custom drag image and dims the source row while dragging, clearing on dragend', () => {
    const { list } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }, { title: 'About' }],
      rowLabel: { type: 'field', field: 'title' },
    })

    const [firstRow] = list.querySelectorAll('.wpc-builder-repeater__row')
    let dragImageArg = null
    const dataTransfer = {
      data: {},
      setData (key, val) { this.data[key] = val },
      getData (key) { return this.data[key] },
      setDragImage (element) { dragImageArg = element },
    }

    firstRow.querySelector('.wpc-builder-repeater__row-header')
      .dispatchEvent(new Event('mousedown', { bubbles: true }))
    firstRow.dispatchEvent(Object.assign(new Event('dragstart', { bubbles: true }), { dataTransfer }))

    expect(firstRow.classList.contains('wpc-builder-repeater__row--dragging')).toBe(true)
    expect(dragImageArg).not.toBeNull()
    expect(dragImageArg.classList.contains('wpc-builder-repeater__drag-image')).toBe(true)
    expect(dragImageArg.querySelector('.wpc-builder-repeater__row-label').textContent).toBe('Home')

    firstRow.dispatchEvent(new Event('dragend', { bubbles: true }))

    expect(firstRow.classList.contains('wpc-builder-repeater__row--dragging')).toBe(false)
  })

  it('does not start a row drag when the gesture begins inside the row body (e.g. selecting text in a subfield input)', () => {
    const { list } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }, { title: 'About' }],
      rowLabel: { type: 'field', field: 'title' },
    })

    const [firstRow, secondRow] = list.querySelectorAll('.wpc-builder-repeater__row')
    const input = firstRow.querySelector('input[data-field="title"]')
    const dataTransfer = {
      data: {},
      setData (key, val) { this.data[key] = val },
      getData (key) { return this.data[key] },
      setDragImage () {},
    }

    input.dispatchEvent(new Event('mousedown', { bubbles: true }))

    const dragStartEvent = Object.assign(new Event('dragstart', { bubbles: true, cancelable: true }), { dataTransfer })

    firstRow.dispatchEvent(dragStartEvent)
    secondRow.dispatchEvent(Object.assign(new Event('drop', { bubbles: true, cancelable: true }), { dataTransfer }))

    expect(dragStartEvent.defaultPrevented).toBe(true)

    const labels = Array.from(list.querySelectorAll('.wpc-builder-repeater__row-label')).map((el) => el.textContent)

    expect(labels).toEqual(['Home', 'About'])
  })

  it('ignores a drop whose transferred index is out of range for this repeater (e.g. dragged from a different repeater instance on the same page)', () => {
    const { list } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }, { title: 'About' }],
      rowLabel: { type: 'field', field: 'title' },
    })

    const secondRow = list.querySelectorAll('.wpc-builder-repeater__row')[1]
    const dataTransfer = {
      data: { 'text/plain': '4' },
      setData (key, val) { this.data[key] = val },
      getData (key) { return this.data[key] },
      setDragImage () {},
    }

    expect(() => {
      secondRow.dispatchEvent(Object.assign(new Event('drop', { bubbles: true, cancelable: true }), { dataTransfer }))
    }).not.toThrow()

    const labels = Array.from(list.querySelectorAll('.wpc-builder-repeater__row-label')).map((el) => el.textContent)

    expect(labels).toEqual(['Home', 'About'])
  })

  it('ignores a drop with no transferred index (e.g. a file or foreign element dragged in from outside the repeater)', () => {
    const { list } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }, { title: 'About' }],
      rowLabel: { type: 'field', field: 'title' },
    })

    const secondRow = list.querySelectorAll('.wpc-builder-repeater__row')[1]
    const dataTransfer = {
      data: {},
      setData (key, val) { this.data[key] = val },
      getData (key) { return this.data[key] ?? '' },
      setDragImage () {},
    }

    secondRow.dispatchEvent(Object.assign(new Event('drop', { bubbles: true, cancelable: true }), { dataTransfer }))

    const labels = Array.from(list.querySelectorAll('.wpc-builder-repeater__row-label')).map((el) => el.textContent)

    expect(labels).toEqual(['Home', 'About'])
  })

  it('ignores a foreign drop even when its payload is in range for this repeater (e.g. a row dragged from another repeater instance)', () => {
    const { list, hiddenInput } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }, { title: 'About' }],
      rowLabel: { type: 'field', field: 'title' },
    })

    const before = hiddenInput.value
    const secondRow = list.querySelectorAll('.wpc-builder-repeater__row')[1]

    // In-range payload, but no local dragstart set this instance's state.
    const dataTransfer = {
      data: { 'text/plain': '0' },
      setData (key, val) { this.data[key] = val },
      getData (key) { return this.data[key] ?? '' },
      setDragImage () {},
    }

    secondRow.dispatchEvent(Object.assign(new Event('drop', { bubbles: true, cancelable: true }), { dataTransfer }))

    const labels = Array.from(list.querySelectorAll('.wpc-builder-repeater__row-label')).map((el) => el.textContent)

    expect(labels).toEqual(['Home', 'About'])
    expect(hiddenInput.value).toBe(before)
  })

  it('does not mark rows as drop targets for a foreign drag', () => {
    const { list } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }, { title: 'About' }],
      rowLabel: { type: 'field', field: 'title' },
    })

    const secondRow = list.querySelectorAll('.wpc-builder-repeater__row')[1]
    const dataTransfer = {
      data: { 'text/plain': '0' },
      setData (key, val) { this.data[key] = val },
      getData (key) { return this.data[key] ?? '' },
      setDragImage () {},
    }
    const dragOverEvent = Object.assign(new Event('dragover', { bubbles: true, cancelable: true }), { dataTransfer })

    secondRow.dispatchEvent(dragOverEvent)

    // Not preventDefault()ed = the browser shows no-drop and won't fire drop.
    expect(dragOverEvent.defaultPrevented).toBe(false)
    expect(secondRow.classList.contains('wpc-builder-repeater__row--drop-before')).toBe(false)
    expect(secondRow.classList.contains('wpc-builder-repeater__row--drop-after')).toBe(false)
  })

  it('keeps an image subfield\'s preview and remove button after a rerender (e.g. adding a row)', () => {
    const selection = { toJSON: () => ({ id: 5, url: 'https://example.test/new.png', filename: 'new.png' }) }
    let selectCallback

    window.wp = {
      media: () => ({
        on: (event, callback) => { selectCallback = callback },
        state: () => ({ get: () => ({ first: () => selection }) }),
        open: () => {},
      }),
    }

    const { list, hiddenInput, addButton } = buildRepeaterFixture({
      fields: { logo: { id: 'logo', type: 'image' } },
      value: [{ logo: '' }],
    })

    list.querySelector('.wpc-builder-repeater__image-add')
      .dispatchEvent(new Event('click', { bubbles: true, cancelable: true }))
    selectCallback()

    addButton.dispatchEvent(new Event('click', { bubbles: true, cancelable: true }))

    const firstPicker = list.querySelector('.wpc-builder-repeater__image-picker')

    expect(firstPicker.querySelector('img').src).toBe('https://example.test/new.png')
    expect(firstPicker.querySelector('.wpc-builder-repeater__image-add').textContent).toBe('Change image')
    expect(firstPicker.querySelector('.wpc-builder-repeater__image-remove').hidden).toBe(false)
    expect(decode(hiddenInput.value)).toEqual([{ logo: '5' }, { logo: '' }])

    delete window.wp
  })

  it('shows and enforces a row limit notice', () => {
    const { list, addButton } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }],
      limit: 1,
    })

    expect(document.querySelector('.wpc-builder-repeater__limit-notice').textContent).toBe('Limit: 1 row')

    addButton.dispatchEvent(new Event('click', { bubbles: true, cancelable: true }))

    expect(list.querySelectorAll('.wpc-builder-repeater__row')).toHaveLength(1)
    expect(document.querySelector('.wpc-builder-repeater__limit-notice').classList.contains('wpc-builder-repeater__limit-notice--reached')).toBe(true)
  })

  it('round-trips a checkbox subfield', () => {
    const { list, hiddenInput } = buildRepeaterFixture({
      fields: { agree: { id: 'agree', type: 'checkbox' } },
      value: [{ agree: false }],
    })

    const checkbox = list.querySelector('input[data-field="agree"]')

    checkbox.checked = true
    checkbox.dispatchEvent(new Event('change', { bubbles: true }))

    expect(decode(hiddenInput.value)).toEqual([{ agree: true }])
  })

  it('round-trips a multi-select subfield', () => {
    const { list, hiddenInput } = buildRepeaterFixture({
      fields: { tags: { id: 'tags', type: 'select', multiple: true, choices: { a: 'A', b: 'B' } } },
      value: [{ tags: ['a'] }],
    })

    const select = list.querySelector('select[data-field="tags"]')

    select.options[1].selected = true
    select.dispatchEvent(new Event('change', { bubbles: true }))

    expect(decode(hiddenInput.value)).toEqual([{ tags: ['a', 'b'] }])
  })

  it('round-trips a radio subfield', () => {
    const { list, hiddenInput } = buildRepeaterFixture({
      fields: { size: { id: 'size', type: 'radio', choices: { s: 'Small', m: 'Medium' } } },
      value: [{ size: 's' }],
    })

    const radios = Array.from(list.querySelectorAll('input[type="radio"]'))

    radios[1].checked = true
    radios[1].dispatchEvent(new Event('change', { bubbles: true }))

    expect(decode(hiddenInput.value)).toEqual([{ size: 'm' }])
  })

  it('round-trips a color subfield picked from the swatch', () => {
    const { list, hiddenInput } = buildRepeaterFixture({
      fields: { tint: { id: 'tint', type: 'color' } },
      value: [{ tint: '' }],
    })

    const picker = list.querySelector('input[type="color"]')

    picker.value = '#00ff00'
    picker.dispatchEvent(new Event('change', { bubbles: true }))

    expect(decode(hiddenInput.value)).toEqual([{ tint: '#00ff00' }])
    expect(list.querySelector('.wpc-builder-repeater__color-value').value).toBe('#00ff00')
  })

  it('does not dirty the setting when the swatch is merely opened and dismissed', () => {
    const { list, hiddenInput } = buildRepeaterFixture({
      fields: { tint: { id: 'tint', type: 'color' } },
      value: [{ tint: '' }],
    })

    list.querySelector('input[type="color"]')
      .dispatchEvent(new Event('click', { bubbles: true, cancelable: true }))

    expect(hiddenInput.value).toBe('')
  })

  it('mirrors a typed hex value onto the swatch', () => {
    const { list, hiddenInput } = buildRepeaterFixture({
      fields: { tint: { id: 'tint', type: 'color' } },
      value: [{ tint: '' }],
    })

    const valueInput = list.querySelector('.wpc-builder-repeater__color-value')

    valueInput.value = '#abc'
    valueInput.dispatchEvent(new Event('input', { bubbles: true }))

    expect(decode(hiddenInput.value)).toEqual([{ tint: '#abc' }])
    expect(list.querySelector('input[type="color"]').value).toBe('#aabbcc')
  })

  it('stores an emptied color subfield as an empty string and leaves the swatch alone', () => {
    const { list, hiddenInput } = buildRepeaterFixture({
      fields: { tint: { id: 'tint', type: 'color' } },
      value: [{ tint: '#ff0000' }],
    })

    const valueInput = list.querySelector('.wpc-builder-repeater__color-value')

    valueInput.value = ''
    valueInput.dispatchEvent(new Event('input', { bubbles: true }))

    expect(decode(hiddenInput.value)).toEqual([{ tint: '' }])
    expect(list.querySelector('.wpc-builder-repeater__color').classList.contains('wpc-builder-repeater__color--empty')).toBe(true)
    expect(list.querySelector('input[type="color"]').value).toBe('#ff0000')
  })

  it('keeps a typed non-hex value verbatim, dimming the swatch left on the last hex it could show', () => {
    const { list, hiddenInput } = buildRepeaterFixture({
      fields: { tint: { id: 'tint', type: 'color' } },
      value: [{ tint: '#ff0000' }],
    })

    const valueInput = list.querySelector('.wpc-builder-repeater__color-value')

    valueInput.value = 'rgba(1,2,3,0.4)'
    valueInput.dispatchEvent(new Event('input', { bubbles: true }))

    expect(decode(hiddenInput.value)).toEqual([{ tint: 'rgba(1,2,3,0.4)' }])
    expect(list.querySelector('input[type="color"]').value).toBe('#ff0000')
    expect(list.querySelector('.wpc-builder-repeater__color').classList.contains('wpc-builder-repeater__color--empty')).toBe(true)
  })

  it('leaves an untouched empty color subfield empty when a sibling field changes', () => {
    const { list, hiddenInput } = buildRepeaterFixture({
      fields: {
        title: { id: 'title', type: 'text' },
        tint: { id: 'tint', type: 'color' },
      },
      value: [{ title: '', tint: '' }],
    })

    const title = list.querySelector('input[data-field="title"]')

    title.value = 'Home'
    title.dispatchEvent(new Event('change', { bubbles: true }))

    expect(decode(hiddenInput.value)).toEqual([{ title: 'Home', tint: '' }])
  })

  it('round-trips an image subfield through the full UI', () => {
    const selection = { toJSON: () => ({ id: 5, url: 'https://example.test/new.png', filename: 'new.png' }) }
    let selectCallback

    window.wp = {
      media: () => ({
        on: (event, callback) => { selectCallback = callback },
        state: () => ({ get: () => ({ first: () => selection }) }),
        open: () => {},
      }),
    }

    const { list, hiddenInput } = buildRepeaterFixture({
      fields: { logo: { id: 'logo', type: 'image' } },
      value: [{ logo: '' }],
    })

    list.querySelector('.wpc-builder-repeater__image-add')
      .dispatchEvent(new Event('click', { bubbles: true, cancelable: true }))
    selectCallback()

    expect(decode(hiddenInput.value)).toEqual([{ logo: '5' }])

    delete window.wp
  })

  it('round-trips a wpc-builder-link subfield through the full UI, reusing Controls\\Link\'s own markup', () => {
    const { list, hiddenInput } = buildRepeaterFixture({
      fields: {
        cta: {
          id: 'cta',
          type: 'wpc-builder-link',
          link: WPC_BUILDER_LINK_MARKUP,
          default: { url: '', text: '', target: '_self' },
        },
      },
      value: [{ cta: { url: '', text: '', target: '_self' } }],
    })

    const group = list.querySelector('[data-field="cta"]')

    expect(group.dataset.linkGroup).toBe('1')

    const urlInput = group.querySelector('.wpc-builder-link__url')
    const textInput = group.querySelector('.wpc-builder-link__text')
    const targetInput = group.querySelector('.wpc-builder-link__target')

    urlInput.value = 'https://example.test'
    urlInput.dispatchEvent(new Event('input', { bubbles: true }))
    textInput.value = 'Visit us'
    textInput.dispatchEvent(new Event('input', { bubbles: true }))
    targetInput.checked = true
    targetInput.dispatchEvent(new Event('change', { bubbles: true }))

    expect(decode(hiddenInput.value)).toEqual([
      { cta: { url: 'https://example.test', text: 'Visit us', target: '_blank' } },
    ])
  })

  it('does not migrate an untouched legacy string row when an unrelated row is edited (the save-blocking bug)', () => {
    const { list, hiddenInput } = buildRepeaterFixture({
      fields: {
        name: { id: 'name', type: 'text' },
        cta: { id: 'cta', type: 'wpc-builder-link', link: WPC_BUILDER_LINK_MARKUP },
      },
      value: [
        { name: 'Twitter', cta: 'https://legacy.example.test' },
        { name: 'Facebook', cta: 'https://legacy.example.test/fb' },
      ],
    })

    const nameInput = list.querySelector('input[data-field="name"]')

    nameInput.value = 'Twitter Updated'
    nameInput.dispatchEvent(new Event('input', { bubbles: true }))

    expect(decode(hiddenInput.value)).toEqual([
      { name: 'Twitter Updated', cta: 'https://legacy.example.test' },
      { name: 'Facebook', cta: 'https://legacy.example.test/fb' },
    ])
  })

  it('keeps a legacy string row rendered as the plain input across a second full re-render', () => {
    const { list, addButton } = buildRepeaterFixture({
      fields: { cta: { id: 'cta', type: 'wpc-builder-link', link: WPC_BUILDER_LINK_MARKUP } },
      value: [{ cta: 'https://legacy.example.test' }],
    })

    // Any full DOM rebuild (here: adding a second row) re-renders every
    // existing row's subfield inputs from scratch via createSubfieldInput().
    addButton.dispatchEvent(new Event('click', { bubbles: true, cancelable: true }))

    const firstRow = list.querySelectorAll('.wpc-builder-repeater__row')[0]
    const input = firstRow.querySelector('input[data-field="cta"]')

    expect(firstRow.querySelector('.wpc-builder-link')).toBeNull()
    expect(input).not.toBeNull()
    expect(input.type).toBe('url')
    expect(input.value).toBe('https://legacy.example.test')
  })

  it('uses a Link sub-field\'s text (or url) as the row label, not "[object Object]"', () => {
    const { list } = buildRepeaterFixture({
      fields: { cta: { id: 'cta', type: 'wpc-builder-link', link: WPC_BUILDER_LINK_MARKUP } },
      value: [
        { cta: { url: 'https://example.test', text: 'Visit us', target: '_self' } },
        { cta: { url: 'https://example.test/about', text: '', target: '_self' } },
      ],
      rowLabel: { type: 'field', field: 'cta' },
    })

    const labels = Array.from(list.querySelectorAll('.wpc-builder-repeater__row-label')).map((el) => el.textContent)

    expect(labels).toEqual(['Visit us', 'https://example.test/about'])
  })

  it('adds a drop-after indicator when dragging over a row below the dragged row, and drop-before when above', () => {
    const { list } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }, { title: 'About' }, { title: 'Contact' }],
      rowLabel: { type: 'field', field: 'title' },
    })

    const [firstRow, secondRow, thirdRow] = list.querySelectorAll('.wpc-builder-repeater__row')
    const dataTransfer = {
      data: {},
      setData (key, val) { this.data[key] = val },
      getData (key) { return this.data[key] },
      setDragImage () {},
    }

    firstRow.querySelector('.wpc-builder-repeater__row-header')
      .dispatchEvent(new Event('mousedown', { bubbles: true }))
    firstRow.dispatchEvent(Object.assign(new Event('dragstart', { bubbles: true }), { dataTransfer }))

    thirdRow.dispatchEvent(Object.assign(new Event('dragover', { bubbles: true, cancelable: true }), { dataTransfer }))

    expect(thirdRow.classList.contains('wpc-builder-repeater__row--drop-after')).toBe(true)
    expect(secondRow.classList.contains('wpc-builder-repeater__row--drop-after')).toBe(false)

    secondRow.dispatchEvent(Object.assign(new Event('dragover', { bubbles: true, cancelable: true }), { dataTransfer }))

    expect(thirdRow.classList.contains('wpc-builder-repeater__row--drop-after')).toBe(false)
    expect(secondRow.classList.contains('wpc-builder-repeater__row--drop-after')).toBe(true)

    thirdRow.querySelector('.wpc-builder-repeater__row-header')
      .dispatchEvent(new Event('mousedown', { bubbles: true }))
    thirdRow.dispatchEvent(Object.assign(new Event('dragstart', { bubbles: true }), { dataTransfer }))

    firstRow.dispatchEvent(Object.assign(new Event('dragover', { bubbles: true, cancelable: true }), { dataTransfer }))

    expect(firstRow.classList.contains('wpc-builder-repeater__row--drop-before')).toBe(true)
    expect(secondRow.classList.contains('wpc-builder-repeater__row--drop-after')).toBe(false)
  })

  it('clears dragging and drop-indicator classes on dragend even without a preceding drop', () => {
    const { list } = buildRepeaterFixture({
      fields: { title: { id: 'title', type: 'text' } },
      value: [{ title: 'Home' }, { title: 'About' }],
      rowLabel: { type: 'field', field: 'title' },
    })

    const [firstRow, secondRow] = list.querySelectorAll('.wpc-builder-repeater__row')
    const dataTransfer = {
      data: {},
      setData (key, val) { this.data[key] = val },
      getData (key) { return this.data[key] },
      setDragImage () {},
    }

    firstRow.querySelector('.wpc-builder-repeater__row-header')
      .dispatchEvent(new Event('mousedown', { bubbles: true }))
    firstRow.dispatchEvent(Object.assign(new Event('dragstart', { bubbles: true }), { dataTransfer }))
    secondRow.dispatchEvent(Object.assign(new Event('dragover', { bubbles: true, cancelable: true }), { dataTransfer }))

    expect(secondRow.classList.contains('wpc-builder-repeater__row--drop-after')).toBe(true)

    firstRow.dispatchEvent(new Event('dragend', { bubbles: true }))

    expect(firstRow.classList.contains('wpc-builder-repeater__row--dragging')).toBe(false)
    expect(secondRow.classList.contains('wpc-builder-repeater__row--drop-after')).toBe(false)
  })
})
