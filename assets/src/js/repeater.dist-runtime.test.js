import * as childProcess from 'node:child_process'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import vm from 'node:vm'
import { describe, expect, it } from 'vitest'

const projectRoot = fileURLToPath(new URL('../../../', import.meta.url))
const distPath = fileURLToPath(new URL('../../../dist/js/repeater.js', import.meta.url))

let cachedDistScript

function buildDistScript (
  runBuild = childProcess.execFileSync,
  readScript = () => readFileSync(distPath, 'utf8'),
) {
  if (undefined === cachedDistScript) {
    runBuild('npm', ['run', 'build'], { cwd: projectRoot, stdio: 'ignore' })
    cachedDistScript = readScript()
  }

  return cachedDistScript
}

function resetDistScriptCache () {
  cachedDistScript = undefined
}

function executeDistScript (script, context) {
  vm.createContext(context)
  vm.runInContext(script, context, { timeout: 1000 })
}

describe('dist repeater runtime', () => {
  it('reuses a single dist build within this suite', () => {
    let builds = 0
    const runBuild = () => {
      builds += 1
    }
    const readScript = () => '(function(){/*compiled*/})();'

    resetDistScriptCache()
    buildDistScript(runBuild, readScript)
    buildDistScript(runBuild, readScript)

    expect(builds).toBe(1)
    resetDistScriptCache()
  })

  it('runs dist code with an execution timeout guard', () => {
    expect(() => executeDistScript('while (true) {}', {})).toThrow(/timed out/i)
  })

  it('does not overwrite the global underscore object used by WordPress', () => {
    const script = buildDistScript()
    const underscore = {
      isArray: Array.isArray,
      debounce: () => {},
      extend: Object.assign,
      template: () => () => '',
    }
    const context = {
      _: underscore,
      window: { _: underscore },
      document: {
        readyState: 'loading',
        addEventListener: () => {},
      },
    }

    executeDistScript(script, context)

    expect(context._).toBe(underscore)
    expect(context.window._).toBe(underscore)
  })

  it('wraps runtime code in an IIFE to avoid leaking top-level symbols globally', () => {
    const script = buildDistScript().trimStart()

    expect(script.startsWith('(function(){')).toBe(true)
  })
})
