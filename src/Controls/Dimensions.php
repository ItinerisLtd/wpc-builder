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
final class Dimensions extends AbstractControl implements HasAssets
{
    use HasControlsStylesheet;
    use RendersLabel;
    use RendersDescription;
    use StringifiesMixed;

    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    public $type = 'wpc-builder-dimensions';

    public function renderContent(): void
    {
        $this->renderLabel();
        $this->renderDescription();

        $listId = '_wpc-builder-dimensions-list-' . $this->id;
        $hasChoices = [] !== $this->choices;
        ?>
        <input
            class="wpc-builder-dimensions__input"
            type="text"
            value="<?php echo esc_attr(self::stringify($this->value())); ?>"
            <?php if ($hasChoices) : ?>
                list="<?php echo esc_attr($listId); ?>"
            <?php endif; ?>
            <?php $this->link(); ?>
        >
        <?php if ($hasChoices) : ?>
            <datalist id="<?php echo esc_attr($listId); ?>">
                <?php foreach ($this->choices as $value => $label) : ?>
                    <option value="<?php echo esc_attr((string) $value); ?>">
                        <?php echo esc_html(self::stringify($label)); ?>
                    </option>
                <?php endforeach; ?>
            </datalist>
        <?php endif; ?>
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
