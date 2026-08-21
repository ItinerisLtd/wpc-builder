<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use WP_Customize_Color_Control;

use function is_string;
use function preg_match;
use function strtolower;

/**
 * An alpha-capable regex sanitizer is the default here; `setAlpha(false)`
 * is an opt-in narrowing to `sanitize_hex_color`. See the `$alpha`
 * property's docblock for why.
 *
 * `CONTROL` is core's `WP_Customize_Color_Control` (the Iris picker),
 * which has no alpha channel and can only submit a `#rrggbb` string.
 * The alpha-capable regex only widens what this field's
 * sanitize_callback accepts, it can't make the picker itself write an
 * alpha value.
 */
final class Color extends AbstractField
{
    protected const string CONTROL_TYPE = 'color';
    protected const CONTROL = WP_Customize_Color_Control::class;

    /**
     * Defaults to true, i.e. the alpha-capable regex; this polarity must
     * not be flipped back. Under a hex-only default, `#AABBCC` stayed
     * uppercase, and `rgba()`/`hsl()`/`#rrggbbaa` values returned `null`,
     * which WordPress treats as "abort the write", so an editor could not
     * change the field at all, silently. `setAlpha(false)` is kept as an
     * explicit opt-in narrowing for a caller who wants hex-only.
     */
    private bool $alpha = true;

    public function setAlpha(bool $alpha = true): self
    {
        $this->alpha = $alpha;

        return $this;
    }

    protected function defaultSanitizeCallback(): callable|string
    {
        if (! $this->alpha) {
            return 'sanitize_hex_color';
        }

        return static fn (mixed $value): string => self::sanitizeAlpha($value);
    }

    /**
     * Distinct from `Fields\ColorPalette`'s own sanitizer, which is
     * case-sensitive and also accepts CSS custom properties; see that
     * class's docblock. Only handles string input, since
     * `WP_Customize_Color_Control` only ever submits string values.
     *
     * Reused by Fields\Repeater's 'color' sub-field case, which likewise
     * persists from a text input. Made `public` (was `private`) for that
     * reuse.
     */
    public static function sanitizeAlpha(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $value = strtolower($value);

        $pattern = '/^(\#[\da-f]{3}|\#[\da-f]{6}|\#[\da-f]{8}|rgba\(((\d{1,2}|1\d\d|2([0-4]\d|'
            . '5[0-5]))\s*,\s*){2}((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*)(,\s*(0\.\d+|1))\)|'
            . 'rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)|hsla\(\s*((\d{1,2}|[1-2]\d{2}|'
            . '3([0-5]\d|60)))\s*,\s*((\d{1,2}|100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)(,\s*(0\.\d+|'
            . '1))\)|hsl\(\s*((\d{1,2}|[1-2]\d{2}|3([0-5]\d|60)))\s*,\s*((\d{1,2}|'
            . '100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)\)|hsva\(\s*((\d{1,2}|[1-2]\d{2}|3([0-5]\d|'
            . '60)))\s*,\s*((\d{1,2}|100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)(,\s*(0\.\d+|1))\)|'
            . 'hsv\(\s*((\d{1,2}|[1-2]\d{2}|3([0-5]\d|60)))\s*,\s*((\d{1,2}|'
            . '100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)\))$/';

        $matches = [];
        preg_match($pattern, $value, $matches);

        return $matches[0] ?? '';
    }
}
