# Fields in depth

### Per-field storage overrides

`Config`'s three values are defaults, not constraints. Any field may
override any of them:

```php
Text::make('brand_phone')->setCapability('manage_options');
Text::make('legacy_notice')->setOptionType(OptionType::OPTION);
Text::make('legacy_notice')->setOptionName('acme_legacy');
```

One section can therefore mix `theme_mod` and `option` storage. Get it
wrong and an option-stored field reads back empty from theme mods, then the
first save writes that blank over the wrong place and orphans the real row.

`setOptionType()`/`setOptionName()` also move the field's **setting id**,
since the id format follows from the storage.

## Field catalogue

Every field's **stored value shape** is this package's entire promise.
Some are counter-intuitive, so check a field's shape here before relying
on it.

| Class            | `CONTROL_TYPE`                                                                              | Default sanitize                                                                                                                          | Stored value shape                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| ---------------- | ------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Text`           | `text`                                                                                      | `wp_kses_post`                                                                                                                            | `string`, **not** `sanitize_text_field` and **not** `sanitize_textarea_field`. HTML in a Text value (e.g. `<br>` in a copyright line) survives a save. See `src/Fields/Text.php`'s docblock for the rationale.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| `Textarea`       | `textarea`                                                                                  | `wp_kses_post`                                                                                                                            | `string` (post-`wp_kses_post` HTML).                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| `Editor`         | `wpc-builder-editor` (custom control)                                                          | `wp_kses_post`                                                                                                                            | `string`. Renders the Visual/Code tabs and an **Add Media** button. TinyMCE/Quicktags and the media modal are only enqueued when a site actually has an `Editor` field, not for every site with this package installed.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| `Url`            | `url`                                                                                       | `wp_kses_post`                                                                                                                            | `string`. Same behaviour as `Text`; `wp_kses_post` runs unconditionally, so an `esc_url_raw`-style guard never applies. See `src/Fields/Url.php`'s docblock.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| `Link`           | `wpc-builder-link` (custom control)                                                            | `esc_url_raw()`/`sanitize_text_field()`/`Fields\Link::normaliseTarget()`                                                                  | `array{url: string, text: string, target: string}`. A compound value (see [migrating-from-kirki.md](migrating-from-kirki.md) for handling a legacy plain-string value). `target` is the real HTML attribute value, `'_blank'` or `'_self'`; `rel` is derived by the consuming theme at render time, never stored. `Fields\Link::fromStored($value)` (e.g. `Fields\Link::fromStored(get_theme_mod('cta_link'))`) normalises a value from an earlier shape this field has stored (a bare URL string) into the current array shape; the stored value itself is only rewritten the next time it's saved through the Customizer control.                                                                                                                                                                                                                                                        |
| `Number`         | `number`                                                                                    | `filter_var(..., FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)`, clamped to `[setMin(), setMax()]` **only when both are set** | numeric `string`.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| `Select`         | `select` (rendered by `Controls\Select`)                                                    | single: `sanitize_text_field`; multiple (`setMultiple()`): a closure                                                                      | single: `string`. Multiple: `array<int\|string, string>` capped at `setMaxSelectionNumber()` (default `999`), preserving original array keys for input at or under the cap; `null` becomes `[]`. See [migrating-from-kirki.md](migrating-from-kirki.md) for mapping a legacy `multiple` key onto `setMultiple()`/`setMaxSelectionNumber()`.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| `PostSelect`     | `select` (rendered by `Controls\Select`)                                                    | same as `Select` (extends it, only choice population changes)                                                                             | same as `Select`. Choices are queried, not caller-supplied: `setPostType()` (default `'any'`) and `setQueryArgs()` build a `WP_Query`, run once per field and cached, whose ids/titles become the id-string-keyed choices; no `setChoices()` call needed. `setQueryArgs()` merges under the built-in defaults (`post_status`, `posts_per_page`, `orderby`, `order`) but can never override `post_type`/`fields`, which stay pinned to `setPostType()`'s value and `'all'` respectively.                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| `Radio`          | `radio`                                                                                     | value if it's a known `setChoices()` key, else the field's own default, else `''`                                                         | `string`.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| `RadioButtonset` | `wpc-builder-radio-buttonset` (custom control)                                                 | same as `Radio` (extends it, only the control type changes)                                                                               | `string`.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| `DropdownPages`  | `dropdown-pages` (WP core)                                                                  | `sanitize_text_field`                                                                                                                     | `string`, page ids are stored as **strings**, not cast with `absint()`, inheriting `Select`'s single-value sanitizer since only choice population differs.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| `Multicheck`     | `wpc-builder-multicheck` (custom control)                                                      | comma-splits a non-array value, then `array_map('sanitize_text_field', ...)`                                                              | `array<int, string>`. Empty input sanitizes to `[]`, whether it arrives as an empty array, an empty string or `null`. |
| `Checkbox`       | `checkbox`                                                                                  | `'0'`/`'false'` → `false`, else `(bool)`                                                                                                  | `bool`, a real PHP boolean, not `'0'`/`'1'`. The field's own `default` is coerced the same way (`1`, `'1'`, `true`, `'true'`, `'on'` → `true`, everything else → `false`).                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| `CheckboxToggle` | `wpc-builder-toggle` (custom control)                                                          | same as `Checkbox` (extends it)                                                                                                           | `bool`.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| `Toggle`         | `wpc-builder-toggle`                                                                           | same as `Checkbox`                                                                                                                        | `bool`. Zero-override alias of `CheckboxToggle`.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| `CheckboxSwitch` | `wpc-builder-toggle`                                                                           | same as `Checkbox`                                                                                                                        | `bool`. Zero-override alias of `CheckboxToggle`.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| `Image`          | `image` (rendered by `Controls\Image`; reports `wpc-builder-image` to the browser; see below) | `Support\AttachmentValue::sanitize($value, $saveAs)`                                                                                      | depends on `setSaveAs()`, which **defaults to `SaveAs::ID`**. `SaveAs::ID` → `int`; `SaveAs::URL` → `string`; `SaveAs::ARRAY` → `array{id: int\|string, url: string, filename: string}` (the `id` key is `''`, not `0`, when the array has no id).                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| `Color`          | `color` (WP core `WP_Customize_Color_Control`)                                              | a regex sanitizer; `sanitize_hex_color` only with an explicit `setAlpha(false)`                                                            | `string`, lowercased `#rgb`/`#rrggbb`/`#rrggbbaa`, or `rgb()`/`rgba()`/`hsl()`/`hsla()`/`hsv()`/`hsva()`, or `''` if nothing matches. **The regex is the default, deliberately**: a hex-only default would lowercase nothing (`#AABBCC` stays uppercase) and return `null` for `rgba()`/`hsl()`/`#rrggbbaa`, which `WP_Customize_Manager::post_value()` treats as "fall back to the default and abort the write", making the field unchangeable. `setAlpha(false)` keeps the narrowing available as an opt-in. The regex only widens what the sanitizer _accepts_; it can't make the WP core colour picker itself _produce_ those formats.                                                                                                                                                                                                                                                                                 |
| `ColorPalette`   | `wpc-builder-color-palette` (custom control)                                                   | a permissive regex sanitizer                                                                                                              | `string`, or `''` if nothing matches. Accepts the same colour formats as `Color`, plus CSS custom properties (`--foo`, `var(--foo, fallback)`). Case-sensitive, unlike `Color`: an uppercase hex is rejected rather than lowercased. |
| `Slider`         | `wpc-builder-slider` (custom control)                                                          | `filter_var(..., FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)`, **no clamping**                                              | numeric `string`, unclamped: unlike `Number`, this field never clamps server-side (only in the browser). The control shows a numeric readout, and an unset slider reads "not set" with its thumb at `min` rather than parked at the midpoint (which is what an empty `input type=range` defaults to).                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| `Dimensions`     | `wpc-builder-dimensions` (custom control)                                                      | `sanitize_text_field`                                                                                                                     | `string`. `setChoices()` populates a `<datalist>` of suggested values.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| `Custom`         | `wpc-builder-custom` (custom control)                                                          | _(no setting is registered, see below)_                                                                                                  | nothing is ever persisted. A `Custom` field is raw, `setHtml()`-supplied display HTML; there is nothing to type into it.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `Repeater`       | `wpc-builder-repeater` (custom control)                                                        | a per-sub-field-type coercion switch                                                                                                       | a plain PHP **array of row arrays, each row keyed by sub-field id**; see below. Forces `Transport::REFRESH` unconditionally (the control has no `postMessage` support), which wins over `setPartialRefresh()`'s own forced-`postMessage` rule.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |

This package ships 23 concrete field classes; two of them (`Toggle`,
`CheckboxSwitch`) are zero-behaviour-override aliases kept because either
spelling is common. Every class extends `Fields\AbstractField` (directly or
via `AbstractTextField`/`AbstractChoiceField`) and is used the same way:
`SomeField::make($id)->set...()`.

### `Custom` registers no setting

A `Custom` field is display-only, so this package registers **no setting**
for it. The control still renders from its `html` control arg.

Registering one with `sanitize_callback => '__return_null'` would be latent
damage: anything that marks the setting dirty makes WordPress read the null
return as invalid and abort the **entire** changeset save with
`transaction_fail`, writing none of the other settings.

The observable difference from a setting-backed field: the setting id is
absent from `wp.customize` on the client, and no `customize_save_<id>` hook
fires.

### `Image`

`Fields\Image` does **not** use core's `WP_Customize_Image_Control`, which
has two silent failure modes:

- **`SaveAs::ID` never actually stores an id under core's control.** Core's
  JS writes the URL regardless, so every save needs converting back with
  `attachment_url_to_postid()`, which returns `0` for offloaded media
  (S3/Cloudflare), a changed site URL, or a rewritten uploads path, storing
  `0` over a real attachment id. `Controls\Image` writes `attachment.id`
  directly.
- **Core renders no thumbnail for a URL-stored image** (`SaveAs::URL`); it
  shows "No image selected" for every one of them. `Controls\Image` renders
  the thumbnail server-side from the raw value.

The field's `CONTROL_TYPE` stays `image`, matching `Fields\Repeater`'s
per-sub-field coercion switch, but the type reported to the browser is
`wpc-builder-image`. Reporting `image` would make core instantiate
`api.ImageControl`, which re-renders from its own template and discards the
server-rendered markup.

### Multi-value selects

The cap is enforced **server-side only**, in the sanitize callback, so two
independent caps cannot drift apart. An editor who selects more than the cap
sees the excess dropped on save. See
[migrating-from-kirki.md](migrating-from-kirki.md) for mapping a legacy
`multiple` key onto `setMultiple()`/`setMaxSelectionNumber()`.

`Fields\Select` renders through this package's own `Controls\Select` because
core's `select` branch cannot emit a `multiple` attribute at all.

### `Repeater`

```php
Repeater::make('team_members')
    ->setFields([
        Text::make('name'),
        Url::make('link'),
        Checkbox::make('featured'),
    ])
    ->setRowLabel(RowLabel::fromField('name'))
    ->setLimit(4);
```

stores, after sanitizing two submitted rows:

```php
[
    0 => ['name' => 'Jane Doe', 'link' => 'https://example.test/jane', 'featured' => true],
    1 => ['name' => 'John Smith', 'link' => 'https://example.test/john', 'featured' => false],
]
```

Each sub-field's leaf value is coerced by its `CONTROL_TYPE`:
`image`/`cropped_image`/`upload` → `AttachmentValue::sanitize()`;
`dropdown-pages` → `(int)`; `color` → the `Color` alpha regex, only when
the value is truthy; `text`/`tel`/`radio`/`radio-image` →
`sanitize_text_field()`; `url`/`link` → `esc_url_raw()`; `email` →
`filter_var(..., FILTER_SANITIZE_EMAIL)`; `checkbox` → `(bool)`; `select`
→ `sanitize_text_field()` for a single select, or an array of sanitized
strings for a `setMultiple()` select; `textarea` →
`html_entity_decode(wp_kses_post(...))`. A sub-field type this list
doesn't name (including every other `wpc-builder-*` control type) is left
completely untouched: there is no default coercion. An explicit
`setSanitizeCallback()` on a sub-field always wins over this type-based
switch.

Rows are reordered by **dragging the row header**, not the row body, so
selecting text in a row's inputs is never hijacked into a drag. A drop onto
a different repeater instance is ignored rather than corrupting rows.

A `color` sub-field renders a text input holding the stored value, with an
`<input type="color">` swatch beside it. The swatch mirrors the text input
whenever the value parses as hex, and picking from it writes `#rrggbb` back.
A value the swatch cannot represent (empty, `rgba()`) stays editable as text,
subject to the same sanitiser as a standalone `Color` field.


## Partial refresh (selective refresh)

```php
Editor::make('footer_logo_text')
    ->setPartialRefresh('#footer .footer-logo a', function (): string {
        return get_theme_mod('footer_logo_text', '');
    });
```

`setPartialRefresh(string $selector, callable $renderCallback, ?bool $containerInclusive = null)`
registers a `WP_Customize_Selective_Refresh` partial for the field's own
setting. **It forces the field's transport to `Transport::POST_MESSAGE`,
regardless of call order**: an explicit `->setTransport(Transport::REFRESH)`
is overridden either side of it. Under `refresh` transport WordPress never
sends the `postMessage` a partial listens for, so the partial would be
registered but unreachable.

## Assets

Controls that need their own CSS/JS implement
`Controls\Contracts\HasAssets::assets(): array<EnqueuedAsset>` (`EnqueuedAsset` =
`{type: 'style'|'script', handle, src, dependencies?, version?, media?, args?}`).
`Registrars\ControlAssetRegistrar` enqueues them. `Support\Asset::url()`
resolves each `src` from the package's own position under `WP_CONTENT_DIR`
(see [installation](installation.md) for why that matters).

**Assets are conditional.** The control list is derived from the registered
fields' own `CONTROL` constants
(`Customizer::registeredControlClasses()`), so a site with no `Repeater`
field ships no `repeater.js` and no `repeater.css`, a site with no
`CheckboxToggle` ships no `toggle.css`, and a site whose fields all resolve
to core controls ships none of this package's CSS or JS at all. The two
heavyweight WordPress dependencies are gated the same way, one level
further down: `wp_enqueue_editor()` from `Fields\Editor::afterRegister()`
and `wp_enqueue_media()` from `Fields\Image::afterRegister()` /
`Fields\Repeater::afterRegister()` (the latter only when a sub-field is an
Image).

Repeater **sub-fields** do not contribute to that list: `repeater.js`
renders them, not their own control classes.


## Core components enhancement

`Controls\Select` renders a native `<select>`, then enhances it with core's
component packages (`wp-element`, `wp-components`, `wp-i18n`): single mode
mounts `ComboboxControl`, multiple mode mounts `FormTokenField`. If that
cannot initialise, the native control stays active.
