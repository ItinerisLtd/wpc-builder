#!/usr/bin/env bash
set -euo pipefail

# See docs/testing.md. Extra args (e.g. --ui, -g "some test") pass through
# to Playwright, e.g. `npm run test:e2e -- --ui`.

cd "$(dirname "${BASH_SOURCE[0]}")/.."

npm run build

# Destroys any existing site first so every full run starts from a
# genuinely fresh WordPress install, same as CI. `|| true`: destroy exits
# non-zero when there's nothing to destroy yet (a first run, or CI's
# always-clean runner), which isn't a failure.
npx wp-env destroy --force || true

# The wp-env WordPress container writes into its mu-plugin mount as root,
# which stamps root ownership on the bind-mounted host files too, so
# destroy's own (non-root) file cleanup above can silently fail to fully
# clear its cache directory - leaving files behind that break the next
# `wp-env start`'s clone step with "destination path already exists". A
# throwaway root container can remove what a root-owned container wrote,
# since this shell's user can't.
wp_env_cache_dir="$(node scripts/wp-env-cache-dir.mjs)"

if [ -d "${wp_env_cache_dir}" ]; then
  # The three globs together match everything including dotfiles/dotdirs
  # (e.g. a cloned WordPress checkout's own .git): `/target/*` alone
  # would skip them, and the checkout is what wp-env's next clone
  # actually needs an empty directory for.
  docker run --rm -v "${wp_env_cache_dir}:/target" alpine sh -c 'rm -rf /target/* /target/.[!.]* /target/..?*'
fi

npx wp-env start

npx playwright test "$@"
