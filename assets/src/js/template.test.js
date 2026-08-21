// @vitest-environment jsdom
import { afterEach, beforeEach, expect, it } from 'vitest'
import { compileTemplate, renderElement } from './template.js'

let originalUnderscore

beforeEach(() => {
  originalUnderscore = globalThis._
})

afterEach(() => {
  globalThis._ = originalUnderscore
})

it('throws a clear error when Underscore.js is not loaded', () => {
  delete globalThis._

  expect(() => compileTemplate('<p>{{ data.value }}</p>')).toThrow(/Underscore\.js/)
})

it('escapes interpolated values with {{ }}', () => {
  const template = compileTemplate('<p>{{ data.value }}</p>')

  expect(template({ value: '<script>alert(1)</script>' }))
    .toBe('<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>')
})

it('escapes a single-character expression with {{ }} (matches WP core\'s own regex, not just multi-character ones)', () => {
  const template = compileTemplate('<p>{{data.a}}</p>')

  expect(template({ a: '<b>' })).toBe('<p>&lt;b&gt;</p>')
})

it('outputs raw HTML with {{{ }}}', () => {
  const template = compileTemplate('<div>{{{ data.value }}}</div>')

  expect(template({ value: '<em>hi</em>' })).toBe('<div><em>hi</em></div>')
})

it('supports loops and conditionals with <# #>', () => {
  const template = compileTemplate('<# _.each(data.items, function (item) { #><span>{{ item }}</span><# }); #>')

  expect(template({ items: ['a', 'b'] })).toBe('<span>a</span><span>b</span>')
})

it('renderElement turns an HTML string into a real element', () => {
  const element = renderElement(document, '  <span class="x">hi</span>  ')

  expect(element.tagName).toBe('SPAN')
  expect(element.className).toBe('x')
  expect(element.textContent).toBe('hi')
})

it('renderElement throws a clear error instead of returning null for markup with no root element', () => {
  expect(() => renderElement(document, '')).toThrow(/no root element/)
  expect(() => renderElement(document, '   ')).toThrow(/no root element/)
  expect(() => renderElement(document, 'just text, no element')).toThrow(/no root element/)
})
