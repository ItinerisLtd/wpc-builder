# Releasing

Maintainer notes. Consuming the package needs none of this, just
`composer require itinerisltd/wpc-builder`.

## Cutting a release

1. Create a GitHub Release on `main`, tagged `v1.2.3`, `v1.2.3-beta1` or
   `v1.2.3-RC1`. Write the release notes: they become the `CHANGELOG.md`
   entry verbatim.
2. The `release` workflow builds the zip, attaches it to the release, and
   opens a `chore/changelog-<tag>` PR.
3. Approve the `packagist` deployment in the Actions tab. Nothing reaches
   Private Packagist until you do, and GitHub cancels a run left unapproved
   for 30 days.
4. Nothing to do for the changelog PR: Kodiak approves and merges it once CI
   passes.

A tag outside those forms, or a release with no notes, fails the workflow
before anything is built or published.

## If a step fails

Fix the cause and re-run the job. Nothing is created before the checks, so a
re-run is clean. To run the changelog step by hand:

```bash
GITHUB_REPOSITORY=ItinerisLtd/wpc-builder bash scripts/update-changelog.sh v1.2.3
```

## One-time setup

Already done, recorded in case it ever needs rebuilding. Publishing itself
needs no secrets; it uses Trusted Publishing over GitHub OIDC.

- The `itineris-changelog` GitHub App, installed on this repository only, with
  Pull requests and Issues write access. Its Client ID and private key are the
  `CHANGELOG_BOT_CLIENT_ID` and `CHANGELOG_BOT_PRIVATE_KEY` repository secrets.
  The release job mints a short-lived token from it to open the changelog PR,
  so that CI runs and Kodiak can merge it. A PR opened with the workflow's own
  token never triggers CI, so the required `php` and `assets` checks would
  never report and only someone who can bypass the ruleset could merge it.
  The App needs no Contents permission: the branch and the CHANGELOG.md commit
  are written with GITHUB_TOKEN so GitHub signs the commit. If minting the
  token fails, the release job stops before the changelog step and the package
  is not published to Private Packagist. Fix the App or its secrets and re-run
  the job.
- A Private Packagist package of type **Artifact**, not a VCS mirror. The two
  types cannot coexist for one package name.
- Its **Trusted publisher**: owner `ItinerisLtd`, repository `wpc-builder`,
  CI file `release.yml`, CI environment name `packagist`.
- A GitHub Environment named `packagist` with required reviewers, matching the
  `environment:` key on the workflow's `publish` job. The OIDC subject claim
  includes the environment name, so change both sides together or publishing
  stops matching.
- `.github/workflows/release.yml` must be on `main` for `release` events to
  trigger it.
