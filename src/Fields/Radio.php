<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use function array_key_exists;
use function is_string;

class Radio extends AbstractChoiceField
{
    protected const string CONTROL_TYPE = 'radio';

    protected function defaultSanitizeCallback(): callable|string|null
    {
        $choices = $this->choices;
        $default = $this->defaultValue;

        return static function (mixed $value) use ($choices, $default): mixed {
            if (is_string($value) && array_key_exists($value, $choices)) {
                return $value;
            }

            return $default ?? '';
        };
    }
}
