<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

abstract class AbstractChoiceField extends AbstractField
{
    /** @var array<int|string, string> */
    protected array $choices = [];

    /**
     * @param array<int|string, string> $choices
     */
    final public function setChoices(array $choices): static
    {
        $this->choices = $choices;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function controlArgs(): array
    {
        return [] === $this->choices ? [] : ['choices' => $this->choices];
    }

    /**
     * @return array<int|string, string>
     */
    public function choices(): array
    {
        return $this->choices;
    }
}
