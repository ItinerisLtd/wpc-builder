<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls\Concerns;

use Itineris\WpcBuilder\Controls\Contracts\HasAssets;
use Itineris\WpcBuilder\Support\Asset;

/**
 * Shared by every control whose only stylesheet is the package-wide
 * `dist/css/controls.css` (one CSS file, one block per control,
 * see that file). Enqueuing the same handle from multiple controls is
 * harmless; WordPress' asset APIs are idempotent per handle.
 *
 * @phpstan-import-type EnqueuedAsset from HasAssets
 */
trait HasControlsStylesheet
{
    /**
     * @return array<int, EnqueuedAsset>
     */
    private static function controlsStylesheetAssets(): array
    {
        $src = Asset::url('dist/css/controls.css');

        if ('' === $src) {
            return [];
        }

        return [
            [
                'type' => 'style',
                'handle' => 'wpc-builder',
                'src' => $src,
                'version' => Asset::version('dist/css/controls.css'),
            ],
        ];
    }
}
