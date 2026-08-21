# itinerisltd/wpc-builder

[![GitHub License](https://img.shields.io/github/license/itinerisltd/wpc-builder.svg?style=flat-square)](https://github.com/ItinerisLtd/wpc-builder/blob/main/LICENSE)
[![Hire Itineris](https://img.shields.io/badge/Hire-Itineris-ff69b4.svg?style=flat-square)](https://www.itineris.co.uk/contact/)
[![Twitter Follow @itineris_ltd](https://img.shields.io/twitter/follow/itineris_ltd?style=flat-square&color=1da1f2)](https://twitter.com/itineris_ltd)

A typed, fluent PHP library for registering WordPress Customizer panels,
sections, fields and controls.

Write sections and fields against this API, then call
`Customizer::make()->...->register()` once from your theme or plugin's setup
code.

- [Documentation](#documentation)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Fields](#fields)
- [Storage](#storage)
- [Conditions](#conditions)
- [Partial refresh](#partial-refresh)
- [Migrating from Kirki](#migrating-from-kirki)
- [Contributing](#contributing)
- [FAQ](#faq)
- [Feedback](#feedback)
- [Security](#security)
- [Changelog](#changelog)
- [Credits](#credits)
- [License](#license)

## Documentation

- [Installation, asset URLs and symlinks](docs/installation.md)
- [Fields in depth](docs/fields.md): the catalogue, stored value shapes, sanitize callbacks, partial refresh, assets
- [Conditional visibility](docs/conditional-visibility.md): `visibleWhen()`, `active_callback`, the operator table
- [Conditionally required values](docs/conditionally-required.md): `requiredWhen()`, server-side `value_required` validation
- [Migrating from Kirki](docs/migrating-from-kirki.md): the partial-migration strategy
- [Out of scope and known limitations](docs/known-limitations.md)
- [Testing](docs/testing.md): unit vs integration suites
- [Releasing](docs/releasing.md): maintainers only

## Requirements

- PHP `^8.4`
- WordPress `>= 7.0`

## Installation

```bash
cd web/app/themes/your-theme
composer require itinerisltd/wpc-builder
```

The package must end up **somewhere under `WP_CONTENT_DIR`**: a theme, child
theme, plugin or mu-plugin all qualify. Asset URLs are derived from its
filesystem position, so installing outside that tree (e.g. a Bedrock **root**
`composer.json`) ships no CSS or JS and says so via `_doing_it_wrong()`.
Symlinked installs have sharp edges, covered in
[installation](docs/installation.md).

## Quick start

`examples/` is a working reference.

```php
namespace App;

use Itineris\WpcBuilder\Customizer;

Customizer::make()
    ->addSections([
        Sections\Footer::class,
        Sections\SiteIdentity::class,
    ])
    ->register();
```

Everything hooks onto `customize_register` (priority 20) and
`customize_controls_enqueue_scripts`; nothing runs at class-load time.

A section declares `$id` and `$title` as property defaults and returns its
fields:

```php
namespace App\Sections;

use Itineris\WpcBuilder\Fields\Editor;
use Itineris\WpcBuilder\Fields\Image;
use Itineris\WpcBuilder\Sections\AbstractSection;

final class Footer extends AbstractSection
{
    protected string $id = 'footer';
    protected ?string $title = 'Footer';

    protected function fields(): array
    {
        return [
            Editor::make('footer_logo_text')
                ->setLabel('Footer Logo Text')
                ->setPartialRefresh(
                    '#footer .footer-logo a',
                    static function (): string {
                        $value = get_theme_mod('footer_logo_text', '');

                        return is_string($value) ? $value : '';
                    },
                ),

            Image::make('footer_logo_image')
                ->setLabel('Footer Logo Image'),
        ];
    }
}
```

To extend a WordPress core section such as Site Identity, set `$id` to the
core section's id (`title_tagline`). Existing sections are detected and
`add_section()` is skipped.

## Fields

23 field classes: `Text`, `Textarea`, `Editor`, `Url`, `Link`, `Number`,
`Slider`, `Select`, `PostSelect`, `Radio`, `RadioButtonset`,
`DropdownPages`, `Multicheck`, `Checkbox`, `CheckboxToggle`,
`CheckboxSwitch`, `Toggle`, `Image`, `Color`, `ColorPalette`, `Dimensions`,
`Custom` and `Repeater`.

Stored value shapes vary per field, so check [fields.md](docs/fields.md)
before relying on one.

## Storage

Settings are theme mods by default. Pass a `Config` to store them in
`wp_options` instead:

```php
use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Customizer;
use Itineris\WpcBuilder\Enums\OptionType;

Customizer::make()
    ->setConfig(new Config(optionType: OptionType::OPTION, optionName: 'my_prefix'))
    ->register();
```

Setting ids then become `"my_prefix[fieldId]"`. `capability`, `optionType`
and `optionName` are all overridable per field via `setCapability()`,
`setOptionType()` and `setOptionName()`, so one section can mix storages.

## Conditions

```php
use Itineris\WpcBuilder\Fields\Text;

Text::make('alert_message')
    ->setVisibleWhen([
        ['setting' => 'my_prefix[alert_enabled]', 'operator' => '==', 'value' => true],
    ]);
```

`setRequiredWhen()` takes the same condition rows but is independent of
`setVisibleWhen()`: it hides nothing, and instead fails the changeset save
with a `value_required` `WP_Error` when the conditions pass and the value is
blank. The asterisk and `required` attribute on the control are a hint;
server-side validation is the enforcement.

Operators, nesting and the fail-open rule:
[conditional-visibility.md](docs/conditional-visibility.md) and
[conditionally-required.md](docs/conditionally-required.md).

## Partial refresh

`setPartialRefresh($selector, $renderCallback)` registers a selective-refresh
partial and forces the field's transport to `postMessage`, which the partial
needs to be reachable. See [fields.md](docs/fields.md).

## Migrating from Kirki

Migrating section by section is safe, but Kirki must stay active until every
`Kirki::get_option()` call site is rewritten. Read
[migrating-from-kirki.md](docs/migrating-from-kirki.md) before starting.

## Contributing

```bash
composer style:check  # PHPCS
composer stan         # PHPStan, level max, zero baseline entries
composer test:unit    # Pest, brain/monkey-mocked WordPress
npm run lint          # stylelint (assets/src/css) + eslint (assets/src/js)
npm test              # Vitest (assets/src/js/**/*.test.js)
npm run build         # Vite: assets/src/{js,css}/ -> dist/{js,css}/
shellcheck scripts/*.sh   # release and packaging scripts
composer test:integration # Pest, real WordPress + database; see docs/testing.md
```

The first seven commands are the commit gate. `dist/` is generated and never
committed; sources live in `assets/src/`.

## FAQ

### It looks awesome. Where can I find some more goodies like this?

- Articles on [Itineris' blog](https://www.itineris.co.uk/blog/)
- More projects on [Itineris' GitHub profile](https://github.com/itinerisltd)
- Follow [@itineris_ltd](https://twitter.com/itineris_ltd) on Twitter
- Hire [Itineris](https://www.itineris.co.uk/services/) to build your next awesome site

### This isn't on wp.org. Where can I give a review?

Thanks! Glad you like it. It's important to let people know somebody is using
this project. Consider:

- tweeting something good with a mention of [@itineris_ltd](https://twitter.com/itineris_ltd)
- starring this [GitHub repo](https://github.com/ItinerisLtd/wpc-builder)
- watching this [GitHub repo](https://github.com/ItinerisLtd/wpc-builder)
- submitting pull requests
- [hiring Itineris](https://www.itineris.co.uk/services/)

## Feedback

**Please provide feedback!** We want to make this library useful in as many
projects as possible. Please submit an
[issue](https://github.com/ItinerisLtd/wpc-builder/issues/new) and point out
what you do and don't like, or fork the project and make suggestions.
**No issue is too small.**

## Security

If you discover any security related issues, please email
[hello@itineris.co.uk](mailto:hello@itineris.co.uk) instead of using the
issue tracker.

## Changelog

Please see [CHANGELOG](./CHANGELOG.md) for more information on what has
changed recently.

## Credits

[wpc-builder](https://github.com/ItinerisLtd/wpc-builder) is an
[Itineris Limited](https://www.itineris.co.uk/) project created by
[Lee Hanbury-Pickett](https://github.com/codepuncher).

Full list of contributors can be found
[here](https://github.com/ItinerisLtd/wpc-builder/graphs/contributors).

## License

[wpc-builder](https://github.com/ItinerisLtd/wpc-builder) is released under
the [MIT License](https://opensource.org/licenses/MIT).
