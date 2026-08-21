<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

abstract class AbstractChoiceField extends AbstractField
{
    /** @var array<string, string> */
    protected array $choices = [];

    /**
     * @param array<string, string> $choices
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
     * @return array<string, string>
     */
    public function choices(): array
    {
        return $this->choices;
    }
}
