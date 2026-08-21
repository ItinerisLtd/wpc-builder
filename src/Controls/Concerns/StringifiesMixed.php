<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls\Concerns;

use function is_scalar;

/**
 * WP_Customize_Control's own value()/$choices are untyped at runtime, so
 * PHPStan sees them as `mixed`. A bare `(string)` cast is unsafe (arrays
 * and non-Stringable objects can't be cast), so render methods go
 * through this is_scalar()-guarded coercion instead.
 */
trait StringifiesMixed
{
    private static function stringify(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
