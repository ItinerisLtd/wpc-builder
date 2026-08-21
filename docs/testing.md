# Testing

- `composer test:unit`: `tests/Unit/`, brain/monkey-mocked WordPress.
- `composer test:integration`: `tests/Integration/`, a real WordPress
  core install and a real MySQL/MariaDB database, run inside
  `.devcontainer/` (PHP 8.4 + mariadb). Nothing PHP-related needs to be
  installed on the host; CI uses the same devcontainer via `devcontainers/ci`.
- `npm run test:e2e`: `tests/E2E/`, a real browser (Chromium, via
  Playwright) driving a real WordPress admin (via `@wordpress/env`), for
  JS-only Customizer behaviour a PHP-only test can't see: the Repeater's
  row add/remove/reorder and its own media modal, the standalone `Image`
  field's media modal, `Fields\Color`'s rgba/hsla-drop limitation (see
  `docs/known-limitations.md`), and live selective-refresh preview.
  Requires Docker and `composer install` to already have run (the mu-plugin
  loader needs `vendor/autoload.php`); `npx wp-env destroy` tears the site
  down.

`docker compose -f .devcontainer/docker-compose.yml down` to tear down the local integration containers.
