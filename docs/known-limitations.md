# Out of scope and known limitations

## Out of scope

This package does not implement CSS-generation (`output`/`js_vars`), the
`Typography`, `Background`, `Responsive`, `Multicolor`, `Tabs`, `Date`,
`Code`, `Dashicons`, `FontAwesome` field types, an import/export UI, or a
page builder.

## Known limitations

- **`Support\AttachmentValue::sanitize()` has three storage-shape
  behaviours:** `SaveAs::ARRAY` returns `{id, url, filename}`
  with no `width`/`height` keys; `SaveAs::ID` (via `toId()`) recovers an id via
  `attachment_url_to_postid()` when given a non-numeric string rather than a
  plain `absint()`, a widening, since a URL that would otherwise resolve to
  `0` recovers its real attachment id instead; a non-string value reaching
  the fallback branch is coerced to a string and run through
  `esc_url_raw()`, rather than passed through untouched.

- **`Fields\Color` cannot produce or preserve `rgba()`/`hsla()` once an
  editor touches the picker.** This package uses WordPress core's
  `WP_Customize_Color_Control`, whose input is `maxlength="7"`: hex-only,
  coercing anything else. With `rgba(12,34,56,0.5)` pre-seeded into the
  theme mod:

  | Step                                        | Stored setting                  |
  | ------------------------------------------- | ------------------------------- |
  | on load                                     | `rgba(12,34,56,0.5)`, not dirty |
  | picker opened, then closed without choosing | `rgba(12,34,56,0.5)`, not dirty |
  | after typing `#abcdef`                      | `#abcdef`                       |
  | after typing `rgba(1,2,3,0.4)`              | `#010203`                       |

  A **stored** `rgba()` is safe. It survives load, opening/closing the
  picker, and a full resave. But an editor can never _enter_ one, and the
  first colour change of any kind silently drops the alpha channel. Fixing
  it means shipping a custom alpha-capable colour control, not a tweak to
  the existing one, because core's control cannot be configured into accepting
  alpha. Documented rather than fixed, as a deliberate decision.

- **`AbstractField::CONTROL_TYPE` is a typed class constant**
  (`protected const string CONTROL_TYPE = '...';`). A subclass of
  `AbstractField` that redeclares `CONTROL_TYPE` **without** a `string`
  type will fatal, because PHP requires every override of a typed constant to
  repeat a compatible type.

## Environment troubleshooting

Not defects in this package, but they cost debugging time.

- **On a dev site with `WP_DEBUG_DISPLAY` on, PHP deprecations break
  selective refresh.** WordPress answers `wp_customize_render_partials`
  with a JSON body. If anything prints before that body (a vendor emitting
  PHP 8.4 deprecation notices is the common case), the response looks like:

  ```
  <br /><b>Deprecated</b>: Brain\Hierarchy\Hierarchy::getHierarchy() … <br />
  {"success":true,"data":{…}}
  ```

  jQuery's `dataType: 'json'` can't parse that, the partial silently falls
  back, and the preview does a full iframe reload, indistinguishable from
  "selective refresh is broken". Affects any Customizer library on any PHP
  8.4 dev site, with or without this package. Fix from an mu-plugin:
  `error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', '0');`
  (core's `wp_debug_mode()` runs before mu-plugins, so a later call wins).
  Don't spend the afternoon on `setPartialRefresh()`.
