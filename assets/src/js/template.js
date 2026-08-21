const WP_TEMPLATE_SYNTAX = {
  evaluate: /<#([\s\S]+?)#>/g,
  interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
  escape: /\{\{([^}]+?)\}\}(?!\})/g,
  variable: 'data',
}

export function compileTemplate (source) {
  const underscore = globalThis._

  if (!underscore || typeof underscore.template !== 'function') {
    throw new Error('wpc-builder: Underscore.js (window._) is required to compile templates.')
  }

  return underscore.template(source, undefined, WP_TEMPLATE_SYNTAX)
}

export function renderElement (doc, html) {
  const container = doc.createElement('div')

  container.innerHTML = html.trim()

  const element = container.firstElementChild

  if (!element) {
    throw new Error('wpc-builder: renderElement() received markup with no root element.')
  }

  return element
}
