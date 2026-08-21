<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls;

use Itineris\WpcBuilder\Controls\Concerns\HasControlsStylesheet;
use Itineris\WpcBuilder\Controls\Concerns\RendersDescription;
use Itineris\WpcBuilder\Controls\Concerns\RendersLabel;
use Itineris\WpcBuilder\Controls\Concerns\StringifiesMixed;
use Itineris\WpcBuilder\Controls\Contracts\HasAssets;

/**
 * @phpstan-import-type EnqueuedAsset from HasAssets
 */
final class RadioButtonset extends AbstractControl implements HasAssets
{
    use HasControlsStylesheet;
    use RendersLabel;
    use RendersDescription;
    use StringifiesMixed;

    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    public $type = 'wpc-builder-radio-buttonset';

    public function renderContent(): void
    {
        $this->renderLabel();
        $this->renderDescription();

        if ([] === $this->choices) {
            return;
        }

        $name = '_customize-radio-' . $this->id;
        ?>
        <div class="wpc-builder-radio-buttonset">
            <?php foreach ($this->choices as $value => $label) : ?>
                <?php $value = (string) $value; ?>
                <?php $optionId = $this->id . '-' . $value; ?>
                <input
                    class="wpc-builder-radio-buttonset__input screen-reader-text"
                    type="radio"
                    value="<?php echo esc_attr($value); ?>"
                    name="<?php echo esc_attr($name); ?>"
                    id="<?php echo esc_attr($optionId); ?>"
                    <?php $this->link(); ?>
                    <?php checked($this->value(), $value); ?>
                >
                <label class="wpc-builder-radio-buttonset__label" for="<?php echo esc_attr($optionId); ?>">
                    <?php echo esc_html(self::stringify($label)); ?>
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
