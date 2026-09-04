#!/usr/bin/env bash
set -euo pipefail

# See docs/testing.md.

cd "$(dirname "${BASH_SOURCE[0]}")/.."

npm run build
npx wp-env start

# wp-env's WordPress data persists across local runs (only npx wp-env destroy
# wipes it), so a field a prior local run already saved to its test target
# value looks unchanged to the Customizer's dirty-check on this run, and
# Save never enables. CI always starts from a fresh install, so this only
# bites local re-runs; resetting here makes local runs behave the same way.
npx wp-env run cli wp theme mod remove --all

npx playwright test
