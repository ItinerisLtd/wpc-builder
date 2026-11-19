# Releasing

Maintainer notes. Consuming the package needs none of this, just
`composer require itinerisltd/wpc-builder`.

Releases are driven by [release-please](https://github.com/googleapis/release-please)
from conventional commits on `main`. Tags are unprefixed (`1.2.3`).

## Cutting a release

1. Merge work to `main`. release-please keeps a `chore(release): <version>`
   pull request open, with the next version and the `CHANGELOG.md` entry
   generated from the commits.
2. Merge that pull request by hand when you want to release. Kodiak does not
   merge it.
3. The `release` workflow creates the draft release and tag, builds the zip,
   attaches it, and publishes the release.
4. Approve the `packagist` deployment in the Actions tab. Nothing reaches
   Private Packagist until you do, and GitHub cancels a run left unapproved
   for 30 days.

## Pre-releases

Create a GitHub Release by hand, tagged `1.2.3-beta1` or `1.2.3-RC1`, and tick
"Set as a pre-release". The same workflow builds, attaches and publishes it to
Private Packagist after the same approval. A tag outside the documented forms
fails the workflow before anything is built.
A stable release created by hand is not built or published; stable releases come
only from the release pull request.

## If a step fails

Fix the cause and re-run the failed jobs. A full re-run does nothing once the
draft release exists, so to build and publish a tag again, run the `Release
package` workflow manually (Actions, Run workflow) with the tag, for example
`1.2.3`.

## One-time setup

Already done, recorded in case it ever needs rebuilding. Publishing itself
needs no secrets; it uses Trusted Publishing over GitHub OIDC.

- The `itineris-releaser` GitHub App, installed on this repository, with the
  `RELEASE_BOT_CLIENT_ID` variable and `RELEASE_BOT_PRIVATE_KEY` secret (both
  set at organisation level). release-please uses a token minted from it to
  open the release pull request, so that CI runs on it. A pull request opened
  with the workflow's own token never triggers CI, so the required `php` and
  `assets` checks would never report.
- A Private Packagist package of type **Artifact**, not a VCS mirror. The two
  types cannot coexist for one package name.
- Its **Trusted publisher**: owner `ItinerisLtd`, repository `wpc-builder`,
  CI file `release.yml`, CI environment name `packagist`.
- A GitHub Environment named `packagist` with required reviewers, matching the
  `environment:` key on the workflow's `publish` job. The OIDC subject claim
  includes the environment name, so change both sides together or publishing
  stops matching.
- `.github/workflows/release.yml` must be on `main` for the `push` and
  `release` events to trigger it.
