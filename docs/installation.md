# Installation, asset URLs and symlinks

The one hard requirement is that the package ends up **somewhere under
`WP_CONTENT_DIR`**, which on Bedrock is `web/app` and on a stock install
`wp-content`. A theme, child theme, plugin or mu-plugin all satisfy it.
`Support\Asset::url()` derives every asset URL this package enqueues
(control CSS/JS, the conditional-visibility engine, the repeater UI) from
the package's own filesystem position relative to `WP_CONTENT_DIR`,
rendered against `content_url()`.

`WP_CONTENT_DIR` resolution is layout-independent: theme, child theme,
plugin and mu-plugin all work the same, including layouts like **Sage 9**
where WordPress's theme directory (`themes/<theme>/resources`) is a
_sibling_ of the Composer `vendor/` directory rather than an ancestor,
a layout theme-relative resolution cannot answer.

If the package genuinely does sit outside `WP_CONTENT_DIR` (e.g. a Bedrock
**root** `composer.json`, which installs into `vendor/` at the site root, a
sibling of `web/`), there is no URL for it, because the web server does not
serve that tree. `Asset::url()` still returns `''` and the
affected controls still enqueue nothing, since an unreachable URL is worse
than no URL. **It is not silent**, though: the package calls
`_doing_it_wrong()` naming both the install path and `WP_CONTENT_DIR`, so
the condition is visible on any `WP_DEBUG` site and capturable via the
`doing_it_wrong_run` action anywhere else.

### Symlinks: what resolves, and what does not

`Asset::url()` normalises **both** sides of the `WP_CONTENT_DIR` comparison
through `realpath()`, so a symlink is transparent, **as long as what it
points AT is still under `WP_CONTENT_DIR`.** That is the whole rule, and it
cuts both ways:

- **Trellis-style deploys resolve.** `web/app` (or a parent of it) is a
  `current` → `releases/<timestamp>/…` symlink, so both sides resolve into
  the same real `releases/<timestamp>` tree and match.
- **A Composer `path` repository with `"symlink": true` does NOT resolve**,
  if the checkout it points at lives outside `WP_CONTENT_DIR`. This is the
  normal case when developing this package: `web/app/themes/<theme>/vendor/itinerisltd/wpc-builder`
  is a symlink to, say, `~/Code/wpc-builder`, `realpath()`
  resolves the package root to _that_ path, and it is not under
  `web/app`, so there is no URL for it, `Asset::url()` returns `''`,
  and you get the silent no-assets failure with a working Customizer. The
  package announces it via `_doing_it_wrong()` (see above), but it is easy
  to miss while developing.

  **The working arrangement for local development** is either:

  ```jsonc
  // In the THEME's composer.json: mirrors real files into vendor/
  // instead of symlinking, so the package genuinely lives under
  // WP_CONTENT_DIR.
  {
    "type": "path",
    "url": "../../../../wpc-builder",
    "options": { "symlink": false },
  }
  ```

  (re-run `composer update itinerisltd/wpc-builder` after each
  edit), **or** put the checkout itself somewhere under `WP_CONTENT_DIR`
  and symlink freely from there.

`realpath()` also fails when a _parent_ directory lacks traverse permission
(`open_basedir`, or a restrictive PHP-FPM pool user), in which case a
symlink mismatch that would otherwise have resolved comes back. The package
writes an `error_log()` line naming the path when it detects that.

### Translations

Every UI string uses the `wpc-builder` text domain, in PHP and in JS
(`wp.i18n`). The package ships no translation files and loads no textdomain
of its own, so a site that wants translated strings supplies them with
`load_textdomain()` and `wp_set_script_translations()` against the
`wpc-builder-*` script handles.

### Testing an unreleased branch

`dist/*` is not committed, and CI uploads no package artifact, so
installing directly from a VCS branch leaves the runtime JS and CSS
missing. Generate a zip with `composer package`, or run `npm run build`
inside the checkout.
