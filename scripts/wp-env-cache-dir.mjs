#!/usr/bin/env node

/**
 * Prints @wordpress/env's own resolved cache directory path for this
 * project, mirroring its get-cache-directory.js and load-config.js
 * (WP_ENV_HOME override, ~/wp-env vs ~/.wp-env depending on whether
 * /snap exists, then wp-env-<project dir>-<md5 hash of the resolved
 * .wp-env.json path, first 8 hex chars>).
 *
 * Used by test-e2e.sh to find and forcibly clear the cache directory
 * when `wp-env destroy` can't fully clean it up itself (see that
 * script for why).
 */

import { createHash } from 'node:crypto'
import { existsSync } from 'node:fs'
import { homedir } from 'node:os'
import { basename, dirname, join, resolve } from 'node:path'

const base = process.env.WP_ENV_HOME
  ? resolve(process.env.WP_ENV_HOME)
  : resolve(homedir(), existsSync('/snap') ? 'wp-env' : '.wp-env')

const configFilePath = resolve('.wp-env.json')
const hash = createHash('md5').update(configFilePath).digest('hex').slice(0, 8)

console.log(join(base, `wp-env-${basename(dirname(configFilePath))}-${hash}`))
