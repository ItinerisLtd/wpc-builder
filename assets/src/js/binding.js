import { NOT_FOUND, passes } from './evaluate.js'

export function buildGetValue (api) {
  return (setting) => {
    const control = api.control(setting)

    if (!control) {
      return NOT_FOUND
    }

    if (control.setting) {
      return control.setting.get()
    }

    const wpSetting = api(setting)

    return wpSetting ? wpSetting.get() : NOT_FOUND
  }
}

// Collects the settings named across a nested condition list, preserving
// prior behaviour for sparse arrays (holes) and de-duplicating.
export function collectSettings (conditions, found = []) {
  conditions.forEach((condition) => {
    if (Array.isArray(condition)) {
      collectSettings(condition, found)
    } else if (condition.setting && !found.includes(condition.setting)) {
      found.push(condition.setting)
    }
  })

  return found
}

/**
 * Shared reactive core for both dependencies.js (visibility) and
 * required-when.js (the required-value hint): evaluates `conditions`
 * against `getValue` once immediately, hands the boolean result to
 * `onEvaluate`, then re-evaluates and re-calls `onEvaluate` every time a
 * setting named in `conditions` changes. Returns the evaluator itself so
 * a caller that needs it elsewhere (dependencies.js's
 * `control.active.validate`) doesn't have to duplicate the closure.
 */
export function bindConditions (api, getValue, conditions, onEvaluate) {
  const evaluate = () => passes(conditions, getValue)
  const apply = () => onEvaluate(evaluate())

  apply()

  collectSettings(conditions).forEach((setting) => {
    api(setting, (value) => value.bind(apply))
  })

  return evaluate
}
