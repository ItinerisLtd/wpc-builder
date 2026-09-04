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
  loader needs `vendor/autoload.php`). `tests/E2E/global-setup.js` resets
  all theme mods before every run, however Playwright is invoked (wp-env's
  WordPress data otherwise persists across local runs, unlike CI's
  always-fresh install, so a field already saved to its test target value
  from a prior local run would look unchanged and never re-trigger a
  save); `npx wp-env destroy` tears the site down entirely.

`docker compose -f .devcontainer/docker-compose.yml down` to tear down the local integration containers.
