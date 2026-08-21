<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Registrars;

use Itineris\WpcBuilder\Controls\Contracts\HasAssets;
use WP_Customize_Control;

use function is_int;

/**
 * @phpstan-import-type EnqueuedAsset from HasAssets
 */
final class ControlAssetRegistrar
{
    /**
     * @param array<int, class-string<WP_Customize_Control>> $controlClasses
     */
    public function __construct(
        private readonly array $controlClasses
    ) {
    }

    public function register(): void
    {
        foreach ($this->controlClasses as $controlClass) {
            if (! is_subclass_of($controlClass, HasAssets::class, true)) {
                continue;
            }

            foreach ($controlClass::assets() as $asset) {
                $this->enqueue($asset);
            }
        }
    }

    /**
     * @param array<string, mixed> $asset
     * @phpstan-param EnqueuedAsset $asset
     */
    private function enqueue(array $asset): void
    {
        if ('' === $asset['src']) {
            return;
        }

        match ($asset['type']) {
            'style' => $this->enqueueStyle($asset),
            'script' => $this->enqueueScript($asset),
        };
    }

    /**
     * @param array<string, mixed> $asset
     * @phpstan-param EnqueuedAsset $asset
     */
    private function enqueueStyle(array $asset): void
    {
        wp_enqueue_style(
            $asset['handle'],
            $asset['src'],
            $asset['dependencies'] ?? [],
            self::normaliseVersion($asset['version'] ?? null),
            $asset['media'] ?? 'all',
        );
    }

    /**
     * @param array<string, mixed> $asset
     * @phpstan-param EnqueuedAsset $asset
     */
    private function enqueueScript(array $asset): void
    {
        wp_enqueue_script(
            $asset['handle'],
            $asset['src'],
            $asset['dependencies'] ?? [],
            self::normaliseVersion($asset['version'] ?? null),
            // @phpstan-ignore argument.type
            $asset['args'] ?? [],
        );
    }

    private static function normaliseVersion(int|string|null $version): string|null
    {
        return is_int($version) ? (string) $version : $version;
    }
}
