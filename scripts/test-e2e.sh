#!/usr/bin/env bash
set -euo pipefail

# See docs/testing.md. Extra args (e.g. --ui, -g "some test") pass through
# to Playwright, e.g. `npm run test:e2e -- --ui`.

cd "$(dirname "${BASH_SOURCE[0]}")/.."

npm run build
npx wp-env start

npx playwright test "$@"
