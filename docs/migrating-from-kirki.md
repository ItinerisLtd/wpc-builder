# Migrating from Kirki

### A partial migration is safe. Deactivating Kirki is a separate, larger job.

Migrating section by section works: with a section's Kirki registration
removed and an equivalent section from this package registered in its place,
Kirki-written values still display correctly, a no-edit save writes nothing, and a
force-dirty resave of every setting in the section leaves the database
byte-identical.

**That byte-identical guarantee does not hold for a Kirki `link` field.**
Kirki's `link` type is a `class_alias` of `Kirki\Field\URL` and stores a
plain string. This package's `Fields\Link` doesn't mirror that. It stores
a compound `['url' => string, 'text' => string, 'target' => string]` array,
so a mechanical `kirki-link` → `Fields\Link` mapping isn't parity, and a
force-dirty resave writes an array where a string used to be. Move a plain
Kirki `link`/`url` field to this package's `Fields\Url` instead, which
still stores a plain string, **unless** you're deliberately moving to the
compound `url`/`text`/`target` shape, in which case update every front-end
template reading that setting at the same time, since they can no longer
assume a string.

**Kirki must stay active while that is true.** `Kirki::get_option()` keeps
resolving fields Kirki no longer registers, falling through to
`get_theme_mod()`, which is what makes a partial migration safe:

```
Kirki::get_option( $configId, 'footer_copyright' )  = 'Copyright 2026 …'
Kirki::get_option( $configId, 'footer_logo_image' ) = 15340
```

But that also means **the plugin can't simply be deactivated once the last
section has moved**. Front-end templates commonly read Customizer values
through `Kirki::get_option()`, and removing the plugin fatally errors the
front end with `Class "Kirki" not found`. Rewriting every
`Kirki::get_option($configId, $key)` call site to `get_theme_mod($key)` (or
`get_option()`, per the field's storage) is a mandatory, per-site step, and
it's what actually lets Kirki go. Budget for it separately from the sections
themselves.

### Mapping a legacy `multiple` key

Kirki packs two independent things into one `multiple` key, and the mapping
isn't intuitive. `ReactSelect::get_multiple_and_max()` reads it as:

| Kirki `'multiple' => …` | Means                                                       | Write as                                    |
| ----------------------- | ----------------------------------------------------------- | ------------------------------------------- |
| `true`                  | multiple, cap 999                                           | `->setMultiple()`                           |
| `0`                     | multiple, cap 999 (0 is treated as "unlimited")             | `->setMultiple()`                           |
| `-1`                    | multiple, cap 999 (as in `get_posts()`)                     | `->setMultiple()`                           |
| **`1`**                 | **SINGLE**, "1 itself will disable the multiple selection" | `->setMultiple(false)` (i.e. just omit it)  |
| `n > 1`                 | multiple, cap `n`                                           | `->setMultiple()->setMaxSelectionNumber(n)` |

So one Kirki key becomes up to two calls, and the `1` row is a trap: a
mechanical conversion that reads `'multiple' => 1` as "multiple" turns a
single-value setting into an array-valued one.

The cap is enforced server-side only, in `Select`'s sanitize callback,
exactly where Kirki enforces it. An editor who selects more than the cap
sees the excess dropped on save, the same outcome Kirki's own server-side
`array_slice` produces.

### The workflow

`examples/Sections/Footer.php` and `examples/Sections/SiteIdentity.php` are
a before/after reference pair for migrating a section.

