// wp-i18n is a script dependency, not a bundled import: WordPress
// already loads it, and reading it off the global keeps every entry a
// standalone IIFE. Callers that already have `wp` injected pass it in.
// Untranslated fallbacks keep a control working if the dependency is
// ever missing.
const TEXT_DOMAIN = 'wpc-builder'

export function translate (text, wp = globalThis.wp) {
  const gettext = wp?.i18n?.__

  return 'function' === typeof gettext ? gettext(text, TEXT_DOMAIN) : text
}

export function translatePlural (single, plural, count, wp = globalThis.wp) {
  const ngettext = wp?.i18n?._n

  if ('function' === typeof ngettext) {
    return ngettext(single, plural, count, TEXT_DOMAIN)
  }

  return 1 === count ? single : plural
}

export function format (text, ...values) {
  const sprintf = globalThis.wp?.i18n?.sprintf

  if ('function' === typeof sprintf) {
    return sprintf(text, ...values)
  }

  return values.reduce((carry, value) => carry.replace('%s', String(value)), text)
}
