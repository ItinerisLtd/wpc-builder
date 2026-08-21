#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD_DIR="${ROOT_DIR}/build"
DIST_DIR="${ROOT_DIR}/dist/js"
PACKAGE_VERSION="${1:-$(git -C "${ROOT_DIR}" describe --tags --always)}"
COMPOSER_VERSION="${PACKAGE_VERSION#v}"
PACKAGE_NAME="wpc-builder-${PACKAGE_VERSION}.zip"
PACKAGE_PATH="${BUILD_DIR}/${PACKAGE_NAME}"
TMP_DIR="$(mktemp -d)"
STAGING_DIR="${TMP_DIR}/package"

cleanup() {
  rm -rf "${TMP_DIR}"
}
trap cleanup EXIT

mkdir -p "${BUILD_DIR}" "${DIST_DIR}" "${STAGING_DIR}"
rm -f "${PACKAGE_PATH}"

npm --prefix "${ROOT_DIR}" ci
npm --prefix "${ROOT_DIR}" run build

rsync \
  --archive \
  --delete \
  --exclude-from="${ROOT_DIR}/.distignore" \
  "${ROOT_DIR}/" \
  "${STAGING_DIR}/"

COMPOSER_VERSION="${COMPOSER_VERSION}" STAGING_DIR="${STAGING_DIR}" node -e 'const fs=require("fs");const p=process.env.STAGING_DIR + "/composer.json";const j=JSON.parse(fs.readFileSync(p,"utf8"));j.version=process.env.COMPOSER_VERSION;fs.writeFileSync(p, JSON.stringify(j, null, 2) + "\n");'

(
  cd "${STAGING_DIR}"
  zip --recurse-paths --quiet "${PACKAGE_PATH}" .
)

printf 'Created release package: %s\n' "${PACKAGE_PATH}"
