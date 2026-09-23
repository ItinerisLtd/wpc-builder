# Grouping a section's fields into tabs

```php
final class Header extends AbstractSection
{
    protected string $id = 'header';

    protected function tabs(): array
    {
        return [
            'global' => 'Global',
            'landing' => 'Landing',
        ];
    }

    protected function fields(): array
    {
        return [
            Repeater::make('header_top_links')->setTab('global')->setFields([...]),
            Repeater::make('header_landing_buttons')->setTab('landing')->setFields([...]),
        ];
    }
}
```

A section that overrides `tabs()` gets a client-side tab menu control
automatically registered at the top of the section. Individual fields opt
into a tab via their own `setTab()`. `tabs()` is a method, not a property,
so it works equally for a fixed list and one built at runtime (e.g. from a
theme-specific helper that varies per site).

## What it stores

Nothing. The tab menu is a display-only control, like `Custom`; see
[fields.md](fields.md#tabs-registers-no-setting). Switching tabs only
shows/hides other controls client-side; every field keeps its own real
setting regardless of which tab it's grouped under.

## Fail-open behaviour

- A field's `setTab('some_id')` where `'some_id'` isn't a key in its
  section's `tabs()` is simply ignored: the field is never assigned to any
  tab, so it stays always-visible. This mirrors `setVisibleWhen()`'s own
  fail-open rule for an unresolvable condition.
- A field's `setTab()` on a section that never overrides `tabs()` at all is
  equally inert: there's no tab menu to group it under, so it just renders
  normally.

## Interaction with `setVisibleWhen()`

Tab-hiding and `setVisibleWhen()` are two independent mechanisms: `tabs.js`
toggles a CSS class on the control's own container, while `setVisibleWhen()`
(`dependencies.js`) drives WordPress core's own `control.active` value. On a
field using both, the two correctly intersect visually (a field hidden by
either one stays hidden), but anything that reads `control.active()`
directly to ask "is this field visible" will not see a tab-hidden state
reflected in it, since tabs.js never touches `active` at all. Documented
rather than composed into one mechanism, as a deliberate decision, since
neither real usage this feature was built for combines the two on the same
field.

## Migrating from Kirki

Kirki's section-level `'tabs' => [$id => ['label' => $label]]` maps onto
`tabs()`; a field's `'tab' => 'id'` maps onto `->setTab('id')`. A stray
`'tab'` key on a field whose section never declared a `'tabs'` array was
already inert in Kirki (see the fail-open rule above) and can simply be
dropped when porting that field.
