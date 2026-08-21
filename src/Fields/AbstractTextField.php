<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

abstract class AbstractTextField extends AbstractField
{
    protected ?string $placeholder = null;
    protected ?int $maxLength = null;

    final public function setPlaceholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    final public function setMaxLength(int $length): static
    {
        $this->maxLength = $length;

        return $this;
    }

    protected function setup(): void
    {
        $this->inputAttrs = [
            ...$this->inputAttrs,
            ...array_filter(
                [
                    'placeholder' => $this->placeholder,
                    'maxlength' => $this->maxLength,
                ],
                static fn (mixed $value): bool => null !== $value,
            ),
        ];
    }
}
