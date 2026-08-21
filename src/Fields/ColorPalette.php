<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use Itineris\WpcBuilder\Controls\ColorPalette as ColorPaletteControl;

use function is_string;
use function preg_match;

final class ColorPalette extends AbstractField
{
    protected const string CONTROL_TYPE = 'wpc-builder-color-palette';
    protected const CONTROL = ColorPaletteControl::class;

    /** @var array<int, string> */
    protected array $colors = [];

    /**
     * @param array<int, string> $colors
     */
    public function setColors(array $colors): self
    {
        $this->colors = $colors;

        return $this;
    }

    protected function defaultSanitizeCallback(): callable
    {
        return static fn (mixed $value): string => self::sanitize($value);
    }

    /**
     * Accepts the same colour formats as `Fields\Color`, plus CSS custom
     * properties (`--foo`, `var(--foo, fallback)`). Case-sensitive: an
     * uppercase hex is rejected rather than normalised.
     */
    private static function sanitize(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $pattern = '/^(\#[\da-f]{3}|\#[\da-f]{6}|\#[\da-f]{8}|rgba\(((\d{1,2}|1\d\d|2([0-4]\d|'
            . '5[0-5]))\s*,\s*){2}((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*)(,\s*(0\.\d+|1))\)|'
            . 'rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)|hsla\(\s*((\d{1,2}|[1-2]\d{2}|'
            . '3([0-5]\d|60)))\s*,\s*((\d{1,2}|100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)(,\s*(0\.\d+|'
            . '1))\)|hsl\(\s*((\d{1,2}|[1-2]\d{2}|3([0-5]\d|60)))\s*,\s*((\d{1,2}|'
            . '100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)\)|hsva\(\s*((\d{1,2}|[1-2]\d{2}|3([0-5]\d|'
            . '60)))\s*,\s*((\d{1,2}|100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)(,\s*(0\.\d+|1))\)|'
            . 'hsv\(\s*((\d{1,2}|[1-2]\d{2}|3([0-5]\d|60)))\s*,\s*((\d{1,2}|'
            . '100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)\)|--[\w-]+|var\(--[\w-]+(,\s*.+)?\))$/';

        $matches = [];
        preg_match($pattern, $value, $matches);

        return $matches[0] ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    protected function controlArgs(): array
    {
        return [] === $this->colors ? [] : ['choices' => ['colors' => $this->colors]];
    }
}
