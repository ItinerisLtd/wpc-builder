<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls;

use Itineris\WpcBuilder\Controls\Concerns\HasControlsStylesheet;
use Itineris\WpcBuilder\Controls\Concerns\RendersDescription;
use Itineris\WpcBuilder\Controls\Concerns\RendersLabel;
use Itineris\WpcBuilder\Controls\Concerns\StringifiesMixed;
use Itineris\WpcBuilder\Controls\Contracts\HasAssets;
use Itineris\WpcBuilder\Support\Asset;
use function array_map;
use function esc_attr;
use function esc_html;
use function in_array;
use function is_array;

/**
 * Unlike every other control in this package, this one does not use
 * `$this->link()` at all: `data-customize-setting-link` binding
 * (`api.Element`) supports only one scalar per linked element, with no
 * way to aggregate several checkboxes into one array-valued setting.
 * JS instead listens for checkbox changes and calls
 * `wp.customize(settingId).set(array)` directly, keeping the
 * live-preview and saved value types identical; a comma-string
 * transport would desync them under Transport::POST_MESSAGE.
 *
 * @phpstan-import-type EnqueuedAsset from HasAssets
 */
final class Multicheck extends AbstractControl implements HasAssets
{
    use HasControlsStylesheet;
    use RendersLabel;
    use RendersDescription;
    use StringifiesMixed;

    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    public $type = 'wpc-builder-multicheck';

    public function renderContent(): void
    {
        $this->renderLabel();
        $this->renderDescription();

        if ([] === $this->choices) {
            return;
        }

        $rawValue = $this->value();
        $values = array_map(self::stringify(...), is_array($rawValue) ? $rawValue : []);
        ?>
        <ul class="wpc-builder-multicheck" data-wpc-builder-multicheck-setting="<?php echo esc_attr($this->id); ?>">
            <?php foreach ($this->choices as $value => $label) : ?>
                <?php $value = (string) $value; ?>
                <li>
                    <label class="wpc-builder-multicheck__label">
                        <input
                            class="wpc-builder-multicheck__checkbox"
                            type="checkbox"
                            value="<?php echo esc_attr($value); ?>"
                            <?php checked(in_array($value, $values, true)); ?>
                        >
                        <?php echo esc_html(self::stringify($label)); ?>
                    </label>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }

    /**
     * @return array<int, EnqueuedAsset>
     */
    public static function assets(): array
    {
        $assets = self::controlsStylesheetAssets();

        $jsSrc = Asset::url('dist/js/multicheck.js');

        if ('' !== $jsSrc) {
            $assets[] = [
                'type' => 'script',
                'handle' => 'wpc-builder-multicheck',
                'src' => $jsSrc,
                'dependencies' => ['customize-controls'],
                'version' => Asset::version('dist/js/multicheck.js'),
                'args' => ['in_footer' => true],
            ];
        }

        return $assets;
    }
}
