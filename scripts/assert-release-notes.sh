#!/usr/bin/env bash

set -euo pipefail

TAG="${1:?Usage: assert-release-notes.sh <tag>}"
REPO="${GITHUB_REPOSITORY:?GITHUB_REPOSITORY is not set}"

NOTES_BODY="$(gh api "repos/${REPO}/releases/tags/${TAG}" --jq '.body // ""')"

if [[ -n "${NOTES_BODY//[[:space:]]/}" ]]; then
  exit 0
fi

printf 'Release %s has no notes. Write them at https://github.com/%s/releases/tag/%s, then rerun this job.\n' "${TAG}" "${REPO}" "${TAG}" >&2
exit 1
