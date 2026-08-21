<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls;

use Itineris\WpcBuilder\Controls\Concerns\HasControlsStylesheet;
use Itineris\WpcBuilder\Controls\Concerns\RendersDescription;
use Itineris\WpcBuilder\Controls\Concerns\RendersLabel;
use Itineris\WpcBuilder\Controls\Concerns\StringifiesMixed;
use Itineris\WpcBuilder\Controls\Contracts\HasAssets;
use function is_array;

/**
 * @phpstan-import-type EnqueuedAsset from HasAssets
 */
final class ColorPalette extends AbstractControl implements HasAssets
{
    use HasControlsStylesheet;
    use RendersLabel;
    use RendersDescription;
    use StringifiesMixed;

    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    public $type = 'wpc-builder-color-palette';

    public function renderContent(): void
    {
        $this->renderLabel();
        $this->renderDescription();

        $colors = $this->choices['colors'] ?? [];

        if (! is_array($colors) || [] === $colors) {
            return;
        }

        $name = '_customize-radio-' . $this->id;
        ?>
        <div class="wpc-builder-color-palette">
            <?php foreach ($colors as $color) : ?>
                <?php $color = self::stringify($color); ?>
                <label
                    class="wpc-builder-color-palette__swatch"
                    style="background-color: <?php echo esc_attr($color); ?>;"
                >
                    <input
                        class="wpc-builder-color-palette__input screen-reader-text"
                        type="radio"
                        value="<?php echo esc_attr($color); ?>"
                        name="<?php echo esc_attr($name); ?>"
                        <?php $this->link(); ?>
                        <?php checked($this->value(), $color); ?>
                    >
                </label>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * @return array<int, EnqueuedAsset>
     */
    public static function assets(): array
    {
        return self::controlsStylesheetAssets();
    }
}
