# Testing

- `composer test:unit`: `tests/Unit/`, brain/monkey-mocked WordPress.
- `composer test:integration`: `tests/Integration/`, a real WordPress
  core install and a real MySQL/MariaDB database, run inside
  `.devcontainer/` (PHP 8.4 + mariadb). Nothing PHP-related needs to be
  installed on the host; CI uses the same devcontainer via `devcontainers/ci`.
- `npm run test:e2e` (or `npm run test:e2e:ui` for Playwright's interactive
  UI mode): `tests/E2E/`, a real browser (Chromium, via
  Playwright) driving a real WordPress admin (via `@wordpress/env`), for
  JS-only Customizer behaviour a PHP-only test can't see: the Repeater's
  row add/remove/reorder and its own media modal, the standalone `Image`
  field's media modal, `Fields\Color`'s rgba/hsla-drop limitation (see
  `docs/known-limitations.md`), live selective-refresh preview, a section
  registered inside a custom panel, extending a WordPress core section
  (Site Identity), and that every field type in `src/Fields/` actually
  renders and saves against a real Customizer screen and its own enqueued
  assets (control-registration/enqueue regressions a brain/monkey-stubbed
  unit test can't catch).
  Requires Docker and `composer install` to already have run (the mu-plugin
  loader needs `vendor/autoload.php`). `scripts/test-e2e.sh` destroys and
  recreates the wp-env site on every full run, so it always starts from a
  genuinely fresh WordPress install, same as CI (wp-env's WordPress data
  otherwise persists across local runs, so a field already saved to its
  test target value from a prior local run would look unchanged to the
  Customizer's dirty-check and never re-trigger a save). The script also
  force-clears wp-env's cache directory itself via a throwaway Docker
  container: the WordPress container writes into its mu-plugin mount as
  root, which stamps root ownership on those bind-mounted host files, so
  `wp-env destroy`'s own cleanup can't always remove them and a stale
  leftover would otherwise break the next run's WordPress checkout.
  `tests/E2E/global-setup.js` additionally resets theme mods before every
  Playwright invocation, including one that bypasses the wrapper script
  (a direct `-g` filter, or UI mode) and so skips the full destroy.

`docker compose -f .devcontainer/docker-compose.yml down` to tear down the local integration containers.
