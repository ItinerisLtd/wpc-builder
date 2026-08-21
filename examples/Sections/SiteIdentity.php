<?php

declare(strict_types=1);

namespace App\Sections;

use Itineris\WpcBuilder\Fields\AbstractField;
use Itineris\WpcBuilder\Fields\Image;
use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Fields\Textarea;
use Itineris\WpcBuilder\Sections\AbstractSection;

use function esc_html__;

/**
 * Extends WordPress's own core "title_tagline" section instead of
 * adding a new one. register() still walks fields() normally, but
 * automatically skips add_section() because the section id already
 * exists.
 */
final class SiteIdentity extends AbstractSection
{
    public const SECTION_ID = 'title_tagline';
    public const PLACEHOLDER_IMAGE = 'placeholder_image';
    public const BRAND_LOGO = 'brand_logo';
    public const BRAND_PHONE = 'brand_phone';
    public const BRAND_SUMMARY = 'brand_summary';
    public const BRAND_MAIL = 'brand_mail';
    public const BRAND_ADDRESS = 'brand_address';
    public const COPYRIGHT_TEXT = 'copyright_text';

    protected string $id = self::SECTION_ID;

    /**
     * @return array<int, AbstractField>
     */
    protected function fields(): array
    {
        return [
            Image::make(self::PLACEHOLDER_IMAGE)
                ->setLabel(esc_html__('Placeholder image', 'sage'))
                ->setDefaultValue(''),

            Image::make(self::BRAND_LOGO)
                ->setLabel(esc_html__('Brand Logo', 'sage'))
                ->setDefaultValue(0),

            Text::make(self::BRAND_PHONE)
                ->setLabel(esc_html__('Brand phone', 'sage')),

            Textarea::make(self::BRAND_SUMMARY)
                ->setLabel(esc_html__('Short information about brand', 'sage')),

            Text::make(self::BRAND_MAIL)
                ->setLabel(esc_html__('Brand mail', 'sage')),

            Textarea::make(self::BRAND_ADDRESS)
                ->setLabel(esc_html__('Brand address', 'sage')),

            Textarea::make(self::COPYRIGHT_TEXT)
                ->setLabel(esc_html__('Copyright text', 'sage')),
        ];
    }
}
