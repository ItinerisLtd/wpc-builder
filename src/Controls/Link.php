<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls;

use Itineris\WpcBuilder\Controls\Concerns\HasControlsStylesheet;
use Itineris\WpcBuilder\Controls\Concerns\HasToggleStylesheet;
use Itineris\WpcBuilder\Controls\Concerns\RendersDescription;
use Itineris\WpcBuilder\Controls\Concerns\RendersLabel;
use Itineris\WpcBuilder\Controls\Contracts\HasAssets;
use Itineris\WpcBuilder\Support\Asset;

use function esc_html__;

/**
 * Three visible inputs (link text, URL, "open in a new tab") kept in sync
 * with a hidden JSON-carrying input by assets/src/js/link.js. The URL input
 * reuses Fields\Url's own decoration (icon wrapper, placeholder) via
 * url.js's decorateUrlInput(), and the checkbox reuses Controls\Toggle's
 * switch styling.
 *
 * Fields\Repeater also reuses renderFieldsMarkup() to embed this same UI
 * inside a repeater sub-field; see Repeater::controlArgs().
 *
 * @phpstan-import-type EnqueuedAsset from HasAssets
 */
final class Link extends AbstractControl implements HasAssets
{
    use HasControlsStylesheet;
    use HasToggleStylesheet;
    use RendersLabel;
    use RendersDescription;

    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    public $type = 'wpc-builder-link';

    public function renderContent(): void
    {
        $this->renderLabel();
        $this->renderDescription();
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo self::renderFieldsMarkup();
        ?>
        <input type="hidden" <?php $this->link(); ?>>
        <?php
    }

    public static function renderFieldsMarkup(): string
    {
        ob_start();
        ?>
        <div class="wpc-builder-link">
            <label class="wpc-builder-link__row">
                <span class="wpc-builder-link__label"><?php echo esc_html__('Link text', 'wpc-builder'); ?></span>
                <input type="text" class="wpc-builder-link__text">
            </label>
            <label class="wpc-builder-link__row">
                <span class="wpc-builder-link__label"><?php echo esc_html__('URL', 'wpc-builder'); ?></span>
                <input type="url" class="wpc-builder-link__url">
            </label>
            <label class="wpc-builder-link__row wpc-builder-link__row--checkbox">
                <span class="wpc-builder-toggle__switch">
<?php // phpcs:ignore Generic.Files.LineLength.TooLong ?>
                    <input type="checkbox" class="wpc-builder-link__target wpc-builder-toggle__input" autocomplete="off">
                    <span class="wpc-builder-toggle__slider"></span>
                </span>
<?php // phpcs:ignore Generic.Files.LineLength.TooLong ?>
                <span class="wpc-builder-link__label"><?php echo esc_html__('Open in a new tab', 'wpc-builder'); ?></span>
            </label>
        </div>
        <?php
        return rtrim((string) ob_get_clean());
    }

    /**
     * @return array<int, EnqueuedAsset>
     */
    public static function assets(): array
    {
        $assets = [];

        $cssSrc = Asset::url('dist/css/link.css');

        if ('' !== $cssSrc) {
            $assets[] = [
                'type' => 'style',
                'handle' => 'wpc-builder-link',
                'src' => $cssSrc,
                'version' => Asset::version('dist/css/link.css'),
            ];
        }

        array_push($assets, ...self::toggleStylesheetAssets(), ...self::controlsStylesheetAssets());

        $jsSrc = Asset::url('dist/js/link.js');

        if ('' !== $jsSrc) {
            $assets[] = [
                'type' => 'script',
                'handle' => 'wpc-builder-link',
                'src' => $jsSrc,
                'dependencies' => ['customize-controls', 'wpc-builder-url-validation-core'],
                'version' => Asset::version('dist/js/link.js'),
                'args' => ['in_footer' => true],
            ];
        }

        return $assets;
    }
}
