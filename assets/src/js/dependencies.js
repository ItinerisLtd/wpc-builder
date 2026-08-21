import { bindConditions, buildGetValue } from './binding.js'

;(function (api, data) {
  if (!api || !data) {
    return
  }

  const getValue = buildGetValue(api)

  for (const controlId of Object.keys(data)) {
    const conditions = data[controlId]

    api.control(controlId, (control) => {
      const evaluate = bindConditions(api, getValue, conditions, (isVisible) => {
        control.active.set(isVisible)
      })

      control.active.validate = evaluate
    })
  }
})(
  typeof window === 'undefined' ? undefined : window.wp && window.wp.customize,
  typeof window === 'undefined' ? undefined : window.wpcBuilderVisibilityDependencies,
)
