<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls\Contracts;

/**
 * @phpstan-type EnqueuedAsset array{
 *     type: 'style'|'script',
 *     handle: string,
 *     src: string,
 *     dependencies?: array<string>,
 *     version?: string|int|null,
 *     media?: string,
 *     args?: array<string, mixed>
 * }
 */
interface HasAssets
{
    /**
     * @return array<int, EnqueuedAsset>
     */
    public static function assets(): array;
}
