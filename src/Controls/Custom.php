<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls;

use Itineris\WpcBuilder\Controls\Concerns\HasControlsStylesheet;
use Itineris\WpcBuilder\Controls\Concerns\RendersDescription;
use Itineris\WpcBuilder\Controls\Concerns\RendersLabel;
use Itineris\WpcBuilder\Controls\Contracts\HasAssets;
use function wp_kses_post;

/**
 * @phpstan-import-type EnqueuedAsset from HasAssets
 */
final class Custom extends AbstractControl implements HasAssets
{
    use HasControlsStylesheet;
    use RendersLabel;
    use RendersDescription;

    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    public $type = 'wpc-builder-custom';

    /**
     * Set from the `html` control arg (Fields\Custom::controlArgs()) by
     * WP_Customize_Control::__construct(), the same way Toggle::$type is.
     */
    public string $html = '';

    public function renderContent(): void
    {
        $this->renderLabel();
        $this->renderDescription();

        echo wp_kses_post($this->html);
    }

    /**
     * @return array<int, EnqueuedAsset>
     */
    public static function assets(): array
    {
        return self::controlsStylesheetAssets();
    }
}
