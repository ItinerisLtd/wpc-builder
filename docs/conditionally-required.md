# Conditionally required values (`requiredWhen()`)

```php
Text::make('alert_text')
    ->setRequiredWhen([
        ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
    ]);
```

`setRequiredWhen()` is a separate mechanism from
[`setVisibleWhen()`](conditional-visibility.md): visibility controls whether a
control is *shown*, `requiredWhen` controls whether its value must be
*present* before the changeset can save.

The condition-row shape, operator table, `choice` indexing, and AND/OR
nesting-flip are identical to `visibleWhen()`'s. See
[conditional-visibility.md](conditional-visibility.md) for the full spec.

## Server-side enforcement

`requiredWhen` is enforced in `validate_callback`, which WordPress runs
during changeset save. When a field's conditions pass and its value counts
as blank, validation fails with a `WP_Error` code of `value_required`:

```php
$validity = $setting->validate($postedValue);
// $validity->get_error_codes() === ['value_required']
```

This composes with, rather than replaces, whatever `validate_callback` the
field already declares (e.g. `Fields\Url`'s `invalid_url` check): the
field's own validation runs first, and `value_required` is added on top of
its result, never instead of it.

## What counts as "blank"

`AbstractField::isRequiredWhenValueBlank()` defaults to
`null === $value || '' === $value || [] === $value`, deliberately not
`empty()`, so `0`, `'0'`, and `false` stay valid values for fields like
`Number` or `Toggle`. Two field types override it for their own value
shapes:

- `Fields\Link`: blank means an empty `url`, ignoring `text`/`target`.
- `Fields\Repeater`: blank means zero rows; the sub-field values inside an
  existing row aren't inspected.

## Fail-open asymmetry

Like `visibleWhen()`, an unresolvable condition (a `setting` id naming a
field that doesn't exist) fails open, and the condition is treated as passing.
For `visibleWhen()` that's permissive: the control stays visible rather than
vanishing. For `requiredWhen()` the same rule has the opposite practical
effect: a passing condition means "required," so failing open here makes
the field required rather than optional when a sibling setting can't be
resolved. This is deliberate, erring toward stricter validation, not a
silently-skippable field.

## Client-side UX

`Customizer::enqueueRequiredWhen()` enqueues `dist/js/required-when.js` and
`dist/css/required-when.css` whenever at least one registered field declares
`requiredWhen` conditions (skipped entirely otherwise, same convention as
`enqueueDependencies()`). For each such field, it toggles a
`wpc-builder-required` class on the control's container and the native
`required` attribute on its input/textarea/select as the relevant sibling
values change, live and without a page reload, and prints a red asterisk
after the control's title via `::after` on `.customize-control-title`.

**This is a visual/accessibility hint only, not enforcement.** WordPress
Customizer's save path doesn't honour HTML5 `required` (a blank value still
reaches `validate_callback` normally), so the real enforcement is the
`value_required` `WP_Error` described above, which fires regardless of
whether this script has loaded, run, or is even present. A theme could
strip this asset entirely and `requiredWhen` would still work correctly,
just without the live visual cue.
