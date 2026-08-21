<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls;

use Itineris\WpcBuilder\Controls\Concerns\HasControlsStylesheet;
use Itineris\WpcBuilder\Controls\Concerns\RendersDescription;
use Itineris\WpcBuilder\Controls\Concerns\RendersLabel;
use Itineris\WpcBuilder\Controls\Concerns\StringifiesMixed;
use Itineris\WpcBuilder\Controls\Contracts\HasAssets;
use Itineris\WpcBuilder\Support\Asset;

use function __;
use function esc_attr;
use function esc_html;

/**
 * @phpstan-import-type EnqueuedAsset from HasAssets
 */
final class Slider extends AbstractControl implements HasAssets
{
    use HasControlsStylesheet;
    use RendersLabel;
    use RendersDescription;
    use StringifiesMixed;

    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    public $type = 'wpc-builder-slider';

    /**
     * Two defects are fixed here: no numeric readout (a bare range input
     * only shows position, not value, so this renders an `<output>`
     * alongside the track), and an empty setting looking deliberately
     * set (the HTML default for an unset range's value is the MIDPOINT,
     * so an unset 0-50 slider parked at 25; the markup below renders
     * `min` plus an explicit "not set" readout instead).
     *
     * The server-side render is only half the fix: WordPress's own
     * `linkElements()` resets the range to `''` on embed, putting the
     * thumb back in the middle. `assets/src/js/slider.js` re-applies
     * both behaviours after that and never writes to the setting itself,
     * so an unset slider stays unset.
     */
    public function renderContent(): void
    {
        $this->renderLabel();
        $this->renderDescription();

        $min = $this->choices['min'] ?? 0;
        $max = $this->choices['max'] ?? 100;
        $step = $this->choices['step'] ?? 1;

        $value = self::stringify($this->value());

        $isUnset = '' === $value;

        $readout = $isUnset ? __('not set', 'wpc-builder') : $value;
        ?>
        <div class="wpc-builder-slider" data-wpc-builder-slider>
            <input
                class="wpc-builder-slider__range"
                type="range"
                min="<?php echo esc_attr(self::stringify($min)); ?>"
                max="<?php echo esc_attr(self::stringify($max)); ?>"
                step="<?php echo esc_attr(self::stringify($step)); ?>"
                value="<?php echo esc_attr($isUnset ? self::stringify($min) : $value); ?>"
                <?php $this->link(); ?>
            >
            <output class="wpc-builder-slider__value"><?php echo esc_html($readout); ?></output>
        </div>
        <?php
    }

    /**
     * @return array<int, EnqueuedAsset>
     */
    public static function assets(): array
    {
        $assets = self::controlsStylesheetAssets();

        $jsSrc = Asset::url('dist/js/slider.js');

        if ('' !== $jsSrc) {
            $assets[] = [
                'type' => 'script',
                'handle' => 'wpc-builder-slider',
                'src' => $jsSrc,
                'dependencies' => ['customize-controls', 'wp-i18n'],
                'version' => Asset::version('dist/js/slider.js'),
                'args' => ['in_footer' => true],
            ];
        }

        return $assets;
    }
}
