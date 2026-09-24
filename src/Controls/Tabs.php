<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls;

use Itineris\WpcBuilder\Controls\Concerns\RendersDescription;
use Itineris\WpcBuilder\Controls\Concerns\RendersLabel;
use Itineris\WpcBuilder\Controls\Contracts\HasAssets;
use Itineris\WpcBuilder\Support\Asset;

use function esc_attr;
use function esc_html;

/**
 * @phpstan-import-type EnqueuedAsset from HasAssets
 */
final class Tabs extends AbstractControl implements HasAssets
{
    use RendersLabel;
    use RendersDescription;

    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    public $type = 'wpc-builder-tabs';

    /** @var array<string, string> */
    public array $tabs = [];

    /** @var array<string, string> */
    public array $assignments = [];

    public function renderContent(): void
    {
        $this->renderLabel();
        $this->renderDescription();
        ?>
        <ul class="wpc-builder-tabs__menu">
            <?php foreach ($this->tabs as $id => $label) : ?>
                <li class="wpc-builder-tabs__menu-item">
                    <button
                        type="button"
                        class="wpc-builder-tabs__item"
                        data-tab-id="<?php echo esc_attr($id); ?>"
                    ><?php echo esc_html($label); ?></button>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }

    /**
     * @return array<string, mixed>
     */
    protected function jsonData(): array
    {
        return [
            'tabs' => $this->tabs,
            'assignments' => $this->assignments,
        ];
    }

    /**
     * @return array<int, EnqueuedAsset>
     */
    public static function assets(): array
    {
        $assets = [];

        $cssSrc = Asset::url('css/tabs.css');

        if ('' !== $cssSrc) {
            $assets[] = [
                'type' => 'style',
                'handle' => 'wpc-builder-tabs',
                'src' => $cssSrc,
                'version' => Asset::version('css/tabs.css'),
            ];
        }

        $jsSrc = Asset::url('js/tabs.js');

        if ('' !== $jsSrc) {
            $assets[] = [
                'type' => 'script',
                'handle' => 'wpc-builder-tabs',
                'src' => $jsSrc,
                'dependencies' => ['customize-controls'],
                'version' => Asset::version('js/tabs.js'),
                'args' => ['in_footer' => true],
            ];
        }

        return $assets;
    }
}
