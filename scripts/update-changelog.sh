#!/usr/bin/env bash

set -euo pipefail

TAG="${1:?Usage: update-changelog.sh <tag>}"
REPO="${GITHUB_REPOSITORY:?GITHUB_REPOSITORY is not set}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BASE_BRANCH="main"
HEAD_BRANCH="chore/changelog-${TAG}"
MARKER="<!-- release-notes:insert-after -->"

bash "${SCRIPT_DIR}/assert-semver-tag.sh" "${TAG}"
VERSION="${TAG#v}"

# A previous run for this same tag (e.g. a workflow rerun) may have left the
# branch behind; fail fast with an actionable message rather than a cryptic
# 422 partway through.
if gh api "repos/${REPO}/git/ref/heads/${HEAD_BRANCH}" >/dev/null 2>&1; then
  printf 'Branch %s already exists. Delete it (and its PR, if any) from a previous run before retrying.\n' "${HEAD_BRANCH}" >&2
  exit 1
fi

# Kodiak keys automerge off this label (.github/.kodiak.toml), and
# `gh pr create --label` fails outright on a label the repository does not
# have. Created up here with the other checks, before anything is pushed, so
# a failure cannot strand a branch that the guard above then refuses to reuse.
# `--force` makes the call idempotent.
#
# Uses CHANGELOG_PR_TOKEN, not GITHUB_TOKEN: the label and the PR below need
# a real account behind them so the PR triggers `on: pull_request` CI: a PR
# opened with the workflow's own token never does. GITHUB_TOKEN stays the
# default for every other call in this script so the CHANGELOG.md commit
# comes from GitHub's own signed identity, not an unsigned personal PAT.
GH_TOKEN="${CHANGELOG_PR_TOKEN:-${GITHUB_TOKEN}}" gh label create changelog \
  --repo "${REPO}" \
  --color 0e8a16 \
  --description 'Automated CHANGELOG.md update' \
  --force \
  >/dev/null

RELEASE_JSON="$(gh api "repos/${REPO}/releases/tags/${TAG}")"
NOTES_BODY="$(jq -r '.body // ""' <<<"${RELEASE_JSON}")"
PUBLISHED_AT="$(jq -r '.published_at // ""' <<<"${RELEASE_JSON}")"

if ! [[ "${PUBLISHED_AT}" =~ ^[0-9]{4}-[0-9]{2}-[0-9]{2}T ]]; then
  printf 'Release %s has no usable published_at timestamp ("%s"); refusing to proceed.\n' "${TAG}" "${PUBLISHED_AT}" >&2
  exit 1
fi
RELEASE_DATE="${PUBLISHED_AT:0:10}"

NOTES_BODY="${NOTES_BODY//$'\r\n'/$'\n'}"

while [[ "${NOTES_BODY}" == *$'\r' ]]; do
  NOTES_BODY="${NOTES_BODY%$'\r'}"
done

NOTES_BODY="$(printf '%s' "${NOTES_BODY}" | awk '
  /^```/ { fenced = !fenced }
  !fenced && /^<!-- Release notes generated using configuration/ { next }
  !fenced && /^##? / { sub(/^##? /, "### ") }
  { print }
')"

while [[ "${NOTES_BODY}" == $'\n'* ]]; do
  NOTES_BODY="${NOTES_BODY#$'\n'}"
done

if [[ -z "${NOTES_BODY//[[:space:]]/}" ]]; then
  printf 'Release %s has no notes. Write them at https://github.com/%s/releases/tag/%s, then rerun this step.\n' "${TAG}" "${REPO}" "${TAG}" >&2
  exit 1
fi

NEW_SECTION="$(printf '## [%s] - %s\n\n%s' "${VERSION}" "${RELEASE_DATE}" "${NOTES_BODY}")"

# Pinned once and reused for both the CHANGELOG.md read below and the branch
# creation further down, so the two can't observe different states of main
# if another commit lands on it mid-script.
BASE_SHA="$(gh api "repos/${REPO}/git/ref/heads/${BASE_BRANCH}" --jq '.object.sha')"

# The Contents API only round-trips files under 1MB; CHANGELOG.md grows by
# one section per release and will eventually need the Git Data (blob) API
# instead.
CURRENT_FILE="$(gh api "repos/${REPO}/contents/CHANGELOG.md?ref=${BASE_SHA}")"
CURRENT_SHA="$(jq -r '.sha' <<<"${CURRENT_FILE}")"
CURRENT_CONTENT="$(jq -r '.content' <<<"${CURRENT_FILE}" | base64 --decode)"

if ! grep -qxF "${MARKER}" <<<"${CURRENT_CONTENT}"; then
  printf 'CHANGELOG.md is missing the "%s" marker on a line of its own; refusing to update.\n' "${MARKER}" >&2
  exit 1
fi

# Built with a plain `read` loop, not awk -v: awk's -v reinterprets
# backslash escapes in the assigned string, which would silently mangle
# release notes containing sequences like `\d` or `C:\path`.
INSERTED=0
UPDATED_CONTENT=""
while IFS= read -r line || [[ -n "${line}" ]]; do
  UPDATED_CONTENT+="${line}"$'\n'
  if [[ "${INSERTED}" -eq 1 ]]; then
    continue
  fi
  if [[ "${line}" != "${MARKER}" ]]; then
    continue
  fi
  UPDATED_CONTENT+=$'\n'"${NEW_SECTION}"$'\n'
  INSERTED=1
done <<<"${CURRENT_CONTENT}"

# main requires every change to go through a reviewed, status-checked pull
# request (repository ruleset), and the workflow's GITHUB_TOKEN cannot
# bypass that, so this pushes to a fresh branch and opens a PR instead of
# committing straight to main.
gh api "repos/${REPO}/git/refs" \
  -f ref="refs/heads/${HEAD_BRANCH}" \
  -f sha="${BASE_SHA}" \
  >/dev/null

printf '%s' "${UPDATED_CONTENT}" \
  | base64 --wrap=0 \
  | jq -Rs \
    --arg message "chore(changelog): add ${TAG} release notes" \
    --arg sha "${CURRENT_SHA}" \
    --arg branch "${HEAD_BRANCH}" \
    '{message: $message, content: rtrimstr("\n"), sha: $sha, branch: $branch}' \
  | gh api --method PUT "repos/${REPO}/contents/CHANGELOG.md" --input - \
    >/dev/null

# Same reasoning as the label call above: a real account, not the workflow's
# own token, so the PR triggers `on: pull_request` CI.
GH_TOKEN="${CHANGELOG_PR_TOKEN:-${GITHUB_TOKEN}}" gh pr create \
  --repo "${REPO}" \
  --base "${BASE_BRANCH}" \
  --head "${HEAD_BRANCH}" \
  --label changelog \
  --title "chore(changelog): add ${TAG} release notes" \
  --body "$(printf 'Automated changelog update for %s.\n\nSource: https://github.com/%s/releases/tag/%s' "${TAG}" "${REPO}" "${TAG}")" \
  >/dev/null

printf 'Opened PR from %s into %s with %s release notes.\n' "${HEAD_BRANCH}" "${BASE_BRANCH}" "${TAG}"
