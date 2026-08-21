#!/usr/bin/env bash
set -euo pipefail

# See docs/testing.md.

cd "$(dirname "${BASH_SOURCE[0]}")/.."

npm run build
npx wp-env start
npx playwright test
