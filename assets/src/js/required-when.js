import { bindConditions, buildGetValue } from './binding.js'

const REQUIRED_CLASS = 'wpc-builder-required'

/**
 * A plain control has exactly one input/textarea/select worth marking.
 * Fields\Link, Fields\Repeater and Fields\Editor don't: each renders a
 * hidden `<input type="hidden">` linking element alongside its real
 * field(s): ahead of everything else for Repeater and Editor, mixed in
 * for Link, so the generic "first match" selector lands on that hidden
 * input (HTML5 excludes hidden inputs from constraint validation, so
 * `required` on it is a silent no-op) or on the wrong one of several.
 * Link is required when its URL is blank (matching AbstractField's own
 * isRequiredWhenValueBlank() override), so the URL input is the one true
 * target; Editor's one real field is its textarea. Repeater is required
 * when it has zero rows, a state no single input represents, and its
 * row inputs don't exist in the DOM until repeater.js renders them
 * later, by which point this module has no way to know to re-run, so
 * it deliberately marks no input at all; the container-level asterisk
 * (see apply() below) is still correct.
 */
function requiredInput (container, controlType) {
  if ('wpc-builder-link' === controlType) {
    return container.querySelector('.wpc-builder-link__url')
  }

  if ('wpc-builder-editor' === controlType) {
    return container.querySelector('.wpc-builder-editor__textarea')
  }

  if ('wpc-builder-repeater' === controlType) {
    return null
  }

  return container.querySelector('input, textarea, select')
}

;(function (api, data) {
  if (!api || !data) {
    return
  }

  const getValue = buildGetValue(api)

  for (const controlId of Object.keys(data)) {
    const conditions = data[controlId]

    api.control(controlId, (control) => {
      bindConditions(api, getValue, conditions, (required) => {
        const container = control.container && control.container[0]

        if (!container) {
          return
        }

        container.classList.toggle(REQUIRED_CLASS, required)

        const input = requiredInput(container, control.params && control.params.type)

        if (!input) {
          return
        }

        input.toggleAttribute('required', required)
      })
    })
  }
})(
  typeof window === 'undefined' ? undefined : window.wp && window.wp.customize,
  typeof window === 'undefined' ? undefined : window.wpcBuilderRequiredWhenDependencies,
)
