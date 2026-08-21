const isArray = Array.isArray

export const NOT_FOUND = Symbol('wpc-builder/setting-not-found')

const isObject = (value) => value !== null && typeof value === 'object' && !isArray(value)

const isIndexable = (value) => value !== null && typeof value === 'object'

export function evaluate (expected, actual, operator, choice) {
  if (choice && isIndexable(actual)) {
    actual = actual[choice]
  }

  switch (operator) {
    case '===':
      return expected === actual
    case '==':
    case '=':
    case 'equals':
    case 'equal':
      return expected == actual
    case '!==':
      return expected !== actual
    case '!=':
    case 'not equal':
      return expected != actual
    case '>=':
    case 'greater or equal':
    case 'equal or greater':
      return actual >= expected
    case '<=':
    case 'smaller or equal':
    case 'equal or smaller':
      return actual <= expected
    case '>':
    case 'greater':
      return actual > expected
    case '<':
    case 'smaller':
      return actual < expected
    case 'contains':
    case 'in':
      return contains(expected, actual)
    case 'does not contain':
    case 'not in':
      return !contains(expected, actual)
    default:
      return expected == actual
  }
}

function contains (expected, actual) {
  if (isArray(expected) && isArray(actual)) {
    return actual.some((item) => expected.includes(item))
  }

  if (isArray(actual)) {
    return actual.some((item) => item == expected)
  }

  if (isObject(actual)) {
    if (actual[expected] !== undefined) {
      return true
    }

    return Object.values(actual).some((item) => item === expected)
  }

  if (typeof actual === 'string') {
    if (typeof expected === 'string') {
      return expected.indexOf(actual) > -1 && actual.indexOf(expected) > -1
    }

    return isArray(expected) && expected.indexOf(actual) > -1
  }

  return expected == actual
}

export function passes (conditions, getValue, relation = 'AND') {
  if (!conditions || conditions.length === 0) {
    return true
  }

  const results = conditions.map((condition) => {
    if (isArray(condition)) {
      return passes(condition, getValue, relation === 'AND' ? 'OR' : 'AND')
    }

    const value = getValue(condition.setting)

    if (value === NOT_FOUND) {
      return true
    }

    return evaluate(condition.value, value, condition.operator, condition.choice)
  })

  return relation === 'OR' ? results.includes(true) : !results.includes(false)
}
