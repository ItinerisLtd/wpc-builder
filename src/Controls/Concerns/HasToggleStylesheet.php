<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls\Concerns;

use Itineris\WpcBuilder\Controls\Contracts\HasAssets;
use Itineris\WpcBuilder\Support\Asset;

/**
 * Shared by every control that renders a toggle-switch checkbox using
 * `dist/css/toggle.css`'s `.wpc-builder-toggle__*` classes:
 * `Controls\Toggle` and `Controls\Link`'s "open in a new tab" checkbox.
 * See `HasControlsStylesheet` for the same pattern applied to
 * `controls.css`.
 *
 * @phpstan-import-type EnqueuedAsset from HasAssets
 */
trait HasToggleStylesheet
{
    /**
     * @return array<int, EnqueuedAsset>
     */
    private static function toggleStylesheetAssets(): array
    {
        $src = Asset::url('dist/css/toggle.css');

        if ('' === $src) {
            return [];
        }

        return [
            [
                'type' => 'style',
                'handle' => 'wpc-builder-toggle',
                'src' => $src,
                'version' => Asset::version('dist/css/toggle.css'),
            ],
        ];
    }
}
