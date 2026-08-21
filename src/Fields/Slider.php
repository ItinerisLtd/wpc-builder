<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use Itineris\WpcBuilder\Controls\Slider as SliderControl;

use function array_filter;
use function filter_var;

use const FILTER_FLAG_ALLOW_FRACTION;
use const FILTER_SANITIZE_NUMBER_FLOAT;

final class Slider extends AbstractField
{
    protected const string CONTROL_TYPE = 'wpc-builder-slider';
    protected const CONTROL = SliderControl::class;

    protected int|float|null $min = null;
    protected int|float|null $max = null;
    protected int|float|null $step = null;

    public function setMin(int|float $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function setMax(int|float $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function setStep(int|float $step): self
    {
        $this->step = $step;

        return $this;
    }

    protected function defaultSanitizeCallback(): callable
    {
        return static function (mixed $value): mixed {
            return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function controlArgs(): array
    {
        return [
            'choices' => array_filter(
                ['min' => $this->min, 'max' => $this->max, 'step' => $this->step],
                static fn (mixed $value): bool => null !== $value,
            ),
        ];
    }
}
