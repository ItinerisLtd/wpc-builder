<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls;

use Itineris\WpcBuilder\Controls\Concerns\HasControlsStylesheet;
use Itineris\WpcBuilder\Controls\Concerns\RendersDescription;
use Itineris\WpcBuilder\Controls\Concerns\RendersLabel;
use Itineris\WpcBuilder\Controls\Concerns\StringifiesMixed;
use Itineris\WpcBuilder\Controls\Contracts\HasAssets;
use Itineris\WpcBuilder\Support\Asset;
use function esc_attr;
use function esc_html__;
use function esc_url;
use function is_array;
use function is_numeric;
use function wp_get_attachment_url;

/**
 * Replaces core's `WP_Customize_Image_Control`, which writes the URL
 * regardless of `save_as` (`api.UploadControl.select()`) and renders no
 * thumbnail for a URL-stored value. This control writes `attachment.id`
 * directly and resolves the preview URL server-side.
 *
 * Like `Controls\Multicheck` and multiple-mode `Controls\Select`, this
 * control doesn't call `$this->link()`: there's no linked `<input>` in
 * its markup, so all three `save_as` shapes write the same way, via
 * `dist/js/image.js` calling `wp.customize(id).set(...)`.
 *
 * @phpstan-import-type EnqueuedAsset from HasAssets
 */
final class Image extends AbstractControl implements HasAssets
{
    use HasControlsStylesheet;
    use RendersLabel;
    use RendersDescription;
    use StringifiesMixed;

    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    public $type = 'wpc-builder-image';

    /**
     * Which shape this control must write onto the setting: one of
     * Enums\SaveAs's string values ('url', 'id', 'array'). Declared here
     * (natively typed) so __construct()'s $args copy will populate it
     * from Fields\Image's matching control arg; an undeclared property
     * is silently dropped. Named snake_case to match the `save_as`
     * control arg key.
     */
    public string $save_as = 'id'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    public function renderContent(): void
    {
        $this->renderLabel();
        $this->renderDescription();

        $url = $this->previewUrl();
        ?>
        <div
            class="wpc-builder-image"
            data-wpc-builder-image-setting="<?php echo esc_attr($this->id); ?>"
            data-wpc-builder-image-save-as="<?php echo esc_attr($this->save_as); ?>"
        >
            <div class="wpc-builder-image__preview">
                <?php if ('' !== $url) : ?>
                    <img class="wpc-builder-image__thumbnail" src="<?php echo esc_url($url); ?>" alt="">
                <?php else : ?>
                    <span class="wpc-builder-image__placeholder">
                        <?php echo esc_html__('No image selected', 'wpc-builder'); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="wpc-builder-image__actions">
                <button type="button" class="button wpc-builder-image__select">
                    <?php
                    echo '' === $url
                        ? esc_html__('Select image', 'wpc-builder')
                        : esc_html__('Change image', 'wpc-builder');
                    ?>
                </button>
                <button
                    type="button"
                    class="button-link wpc-builder-image__remove"
                    <?php echo '' === $url ? 'hidden' : ''; ?>
                ><?php echo esc_html__('Remove', 'wpc-builder'); ?></button>
            </div>
        </div>
        <?php
    }

    /**
     * @return array<int, EnqueuedAsset>
     */
    public static function assets(): array
    {
        $assets = self::controlsStylesheetAssets();

        $jsSrc = Asset::url('dist/js/image.js');

        if ('' !== $jsSrc) {
            $assets[] = [
                'type' => 'script',
                'handle' => 'wpc-builder-image',
                'src' => $jsSrc,
                'dependencies' => ['customize-controls', 'underscore', 'wp-i18n'],
                'version' => Asset::version('dist/js/image.js'),
                'args' => ['in_footer' => true],
            ];
        }

        return $assets;
    }

    /**
     * A displayable URL for the currently-stored value, whatever shape it
     * is in: an array resolves via its `.url` key, else the raw value is
     * used as the URL, plus a numeric value is resolved through
     * `wp_get_attachment_url()`, since here the id is resolved once,
     * server-side, rather than via the client's own `data.value`
     * plumbing. Returns '' when nothing can be resolved, which is what
     * makes the placeholder render.
     */
    private function previewUrl(): string
    {
        $value = $this->value();

        if (is_array($value)) {
            return self::stringify($value['url'] ?? '');
        }

        $string = self::stringify($value);

        if ('' === $string || '0' === $string) {
            return '';
        }

        if (is_numeric($string)) {
            $resolved = wp_get_attachment_url((int) $string);

            return false === $resolved ? '' : $resolved;
        }

        return $string;
    }
}
