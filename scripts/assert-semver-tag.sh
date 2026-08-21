#!/usr/bin/env bash

set -euo pipefail

TAG="${1:?Usage: assert-semver-tag.sh <tag>}"

if [[ "${TAG}" =~ ^v?[0-9]+\.[0-9]+\.[0-9]+(-(alpha|beta|RC|rc)[0-9]*)?$ ]]; then
  exit 0
fi

printf 'Tag "%s" is not a release tag of the documented form (v1.2.3, v1.2.3-beta1, v1.2.3-RC1); refusing to proceed.\n' "${TAG}" >&2
exit 1
