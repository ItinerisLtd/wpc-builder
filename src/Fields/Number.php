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

            if (null === $min || null === $max) {
                return $value;
            }

            $minimum = filter_var($min, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $maximum = filter_var($max, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            if ($value < $minimum) {
                return $minimum;
            }

            if ($value > $maximum) {
                return $maximum;
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
