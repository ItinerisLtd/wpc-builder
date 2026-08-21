<?php

declare(strict_types=1);

namespace App\Sections;

use Itineris\WpcBuilder\Fields\AbstractField;
use Itineris\WpcBuilder\Fields\Editor;
use Itineris\WpcBuilder\Fields\Image;
use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Sections\AbstractSection;

use function esc_attr__;
use function get_theme_mod;
use function is_string;

/**
 * A migrated section, rewritten against this package's fluent API, a worked
 * before/after example for the migration workflow in
 * docs/migrating-from-kirki.md.
 *
 * Behaviour kept identical to the original: FOOTER_LOGO_TEXT's
 * partial_refresh implicitly forces postMessage transport;
 * FOOTER_COPYRIGHT is a plain text field.
 *
 * The original render_callback pulled a rendered fragment out of a
 * theme-specific helper; this version reads the setting's own sanitized
 * value back with get_theme_mod() instead, since the theme's own data
 * layer isn't part of what this package demonstrates.
 */
final class Footer extends AbstractSection
{
    public const FOOTER_LOGO_TEXT = 'footer_logo_text';
    public const FOOTER_LOGO_IMAGE = 'footer_logo_image';
    public const FOOTER_COPYRIGHT = 'footer_copyright';

    protected string $id = 'footer';
    protected ?string $title = 'Footer';
    protected ?string $description = 'Footer settings';

    /**
     * @return array<int, AbstractField>
     */
    protected function fields(): array
    {
        return [
            Editor::make(self::FOOTER_LOGO_TEXT)
                ->setLabel(esc_attr__('Footer Logo Text', 'theme'))
                ->setDefaultValue(esc_attr__('Site Name', 'theme'))
                ->setPartialRefresh(
                    '#footer .footer-logo a',
                    static function (): string {
                        $value = get_theme_mod(self::FOOTER_LOGO_TEXT, '');

                        return is_string($value) ? $value : '';
                    },
                ),

            Image::make(self::FOOTER_LOGO_IMAGE)
                ->setLabel(esc_attr__('Footer Logo Image', 'theme'))
                ->setDescription('This will replace logo text.'),

            Text::make(self::FOOTER_COPYRIGHT)
                ->setLabel(esc_attr__('Footer Copyright', 'theme'))
                ->setDefaultValue(esc_attr__('Copyright 2024 Site Name', 'theme')),
        ];
    }
}
