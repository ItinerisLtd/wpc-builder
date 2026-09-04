#!/usr/bin/env bash
set -euo pipefail

# See docs/testing.md. Same setup as test-e2e.sh, but launches Playwright's
# interactive UI mode instead of a headless run.

cd "$(dirname "${BASH_SOURCE[0]}")/.."

npm run build
npx wp-env start

# See test-e2e.sh for why this reset is needed on local re-runs.
npx wp-env run tests-cli wp theme mod remove --all

npx playwright test --ui
