<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use Itineris\WpcBuilder\Controls\Multicheck as MulticheckControl;

use function array_map;
use function explode;
use function is_array;
use function is_scalar;
use function sanitize_text_field;

final class Multicheck extends AbstractChoiceField
{
    protected const string CONTROL_TYPE = 'wpc-builder-multicheck';
    protected const CONTROL = MulticheckControl::class;

    protected function defaultSanitizeCallback(): callable
    {
        return static function (mixed $value): array {
            if (! is_array($value)) {
                $string = is_scalar($value) ? (string) $value : '';
                $value = '' === $string ? [] : explode(',', $string);
            }

            return array_map(
                static fn (mixed $item): string => sanitize_text_field(is_scalar($item) ? (string) $item : ''),
                $value,
            );
        };
    }
}
