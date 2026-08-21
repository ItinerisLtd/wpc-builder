<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls;

use Itineris\WpcBuilder\Controls\Concerns\HasToggleStylesheet;
use Itineris\WpcBuilder\Controls\Concerns\RendersDescription;
use Itineris\WpcBuilder\Controls\Concerns\RendersLabel;
use Itineris\WpcBuilder\Controls\Contracts\HasAssets;

/**
 * @phpstan-import-type EnqueuedAsset from HasAssets
 */
final class Toggle extends AbstractControl implements HasAssets
{
    use HasToggleStylesheet;
    use RendersLabel;
    use RendersDescription;

    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    public $type = 'wpc-builder-toggle';

    public function renderContent(): void
    {
        $this->renderLabel();
        $this->renderDescription();
        ?>
        <label class="wpc-builder-toggle">
            <span class="wpc-builder-toggle__switch">
                <input
                    class="wpc-builder-toggle__input"
                    type="checkbox"
                    value="1"
                    <?php $this->link(); ?>
                    <?php checked($this->value()); ?>
                    autocomplete="off"
                >
                <span class="wpc-builder-toggle__slider"></span>
            </span>
        </label>
        <?php
    }

    /**
     * @return array<int, EnqueuedAsset>
     */
    public static function assets(): array
    {
        return self::toggleStylesheetAssets();
    }
}
