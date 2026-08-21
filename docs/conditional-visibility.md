# Conditional visibility (`visibleWhen()`)

```php
Text::make('alert_message')
    ->setVisibleWhen([
        ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
    ]);
```

`setVisibleWhen()` takes an array of condition rows. Each row is either a
**leaf**, `['setting' => <id>, 'operator' => <op>, 'value' => <expected>, 'choice' => <optional key>]`,
or itself a nested **array of rows** (a sub-group). The relation between
sibling rows starts at `AND` at depth 0 and **flips between `AND` and `OR`
one level per nesting depth** (depth 0 = AND, depth 1 = OR, depth 2 = AND,
…).

### Array-form `active_callback`

`setActiveCallback()` accepts **either** a callable **or** an array of
condition rows, converting the array form into `visibleWhen`. Both forms
are accepted because array-form callbacks are common in the wild, so
existing code can pass one straight through:

```php
CheckboxToggle::make('scripts_google_fonts_async')
    ->setActiveCallback([
        ['setting' => 'scripts_google_fonts_enable', 'operator' => '==', 'value' => true],
    ]);
// identical to
CheckboxToggle::make('scripts_google_fonts_async')
    ->setVisibleWhen([
        ['setting' => 'scripts_google_fonts_enable', 'operator' => '==', 'value' => true],
    ]);
```

The exact rules:

- An array that **is itself callable** (`[$object, 'method']`,
  `['Class', 'method']`) stays a callback.
- Otherwise, individually-callable entries are stripped, and the remainder
  becomes `visibleWhen`, but **only if index `0` survived the strip**. If
  the callable was at index `0`, the conditions are dropped entirely.
- Once a field has any conditions, from either route, the PHP
  `active_callback` is discarded and the JS dependency engine owns
  visibility outright. This holds regardless of the order
  `setVisibleWhen()` and `setActiveCallback()` are called in.

The surviving conditions are reindexed with `array_values()`, since a
sparse PHP array left behind by the strip would `json_encode` to a JS
object rather than an array, and the JS dependency engine's `forEach`
can't iterate an object. For a plain list, which every real usage is,
reindexing changes nothing observable.

### Operator table

| Operator           | Aliases                                | Comparison                   |
| ------------------ | -------------------------------------- | ---------------------------- |
| `===`              |                                        | `expected === actual`        |
| `==`               | `=`, `equals`, `equal`                 | `expected == actual`         |
| `!==`              |                                        | `expected !== actual`        |
| `!=`               | `not equal`                            | `expected != actual`         |
| `>=`               | `greater or equal`, `equal or greater` | `actual >= expected`         |
| `<=`               | `smaller or equal`, `equal or smaller` | `actual <= expected`         |
| `>`                | `greater`                              | `actual > expected`          |
| `<`                | `smaller`                              | `actual < expected`          |
| `contains`         | `in`                                   | see below                    |
| `does not contain` | `not in`                               | negation of `contains`/`in`  |
| _(anything else)_  |                                        | `expected == actual` (loose) |

`choice`: when set and `actual` is a non-`null` object or array, `actual`
is replaced with `actual[choice]` **before** any operator runs (this
includes indexing into an array, not just a plain object).

`contains`/`in` (checked in this order): both operands arrays → true if any
`actual` item is in `expected`; `actual` an array (expected isn't) → true
if any item loosely equals `expected`; `actual` a plain object → true if
`expected` is a key of it, or loosely-strict-equals one of its values;
`actual` a string and `expected` a string → true if each contains the
other; `actual` a string and `expected` an array → true if `expected`
contains `actual` (the common "single setting checked against a list of
allowed values" shape); nothing above matched → falls back to loose
equality, **not** `false`.

### Fail-open and negation behaviour

- **An unresolvable condition fails OPEN.** If a condition's `setting`
  names a control that doesn't exist (renamed, dropped, or, see below,
  the wrong id under option storage), that condition is treated as passing
  rather than failing, so the field stays visible instead of vanishing
  with no way to reach it.
- **`not in`/`does not contain` applies `choice` indexing once.** Negation
  is implemented as a single `contains()` check that gets negated, not a
  second full evaluation pass, so a composite `actual` with a `choice`
  key set is indexed exactly once, not twice.

### Under `option_type => option`

A condition's `setting` value must already be the **formatted** setting id
(e.g. `'my_theme[alert_enabled]'`), not the bare field id. This package
does not rewrite a condition's inner `setting` reference; only the
payload's _outer_ key (`Support\collect_visible_when_conditions()`'s array key) gets
formatted via `settingId()`. Getting this wrong doesn't error; per the
fail-open rule above, it just silently disables that one condition.

