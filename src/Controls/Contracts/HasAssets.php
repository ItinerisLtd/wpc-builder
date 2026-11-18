<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls\Contracts;

/**
 * @phpstan-type EnqueuedAsset array{
 *     type: 'style'|'script',
 *     handle: non-empty-string,
 *     src: string,
 *     dependencies?: array<non-empty-string>,
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
