<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use function filter_var;

use const FILTER_FLAG_ALLOW_FRACTION;
use const FILTER_SANITIZE_NUMBER_FLOAT;

class Number extends AbstractField
{
    protected const string CONTROL_TYPE = 'number';

    protected int|float|null $min = null;
    protected int|float|null $max = null;
    protected int|float|null $step = null;

    final public function setMin(int|float $min): static
    {
        $this->min = $min;

        return $this;
    }

    final public function setMax(int|float $max): static
    {
        $this->max = $max;

        return $this;
    }

    final public function setStep(int|float $step): static
    {
        $this->step = $step;

        return $this;
    }

    protected function defaultSanitizeCallback(): callable|string|null
    {
        $min = $this->min;
        $max = $this->max;

        return static function (mixed $value) use ($min, $max): mixed {
            $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            /**
             * $value came from FILTER_SANITIZE_NUMBER_FLOAT, which only
             * strips disallowed characters, not validates the result:
             * malformed input like "50.1.2" survives as a string PHP's
             * numeric-string comparison rules then treat as non-numeric,
             * falling back to a byte-wise comparison ('5' < '9') that
             * can clamp to the wrong bound. Casting both sides to float
             * for the comparison avoids that; $minimum/$maximum are
             * still returned as the original filter_var() string.
             */
            if (null !== $min) {
                $minimum = filter_var($min, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                if ((float) $value < (float) $minimum) {
                    return $minimum;
                }
            }

            if (null !== $max) {
                $maximum = filter_var($max, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                if ((float) $value > (float) $maximum) {
                    return $maximum;
                }
            }

            return $value;
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function controlArgs(): array
    {
        $attrs = array_filter(
            [
                'min' => $this->min,
                'max' => $this->max,
                'step' => $this->step,
            ],
            static fn (mixed $value): bool => null !== $value,
        );

        return [] === $attrs ? [] : ['input_attrs' => [...$this->inputAttrs, ...$attrs]];
    }
}
