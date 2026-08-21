<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

class Checkbox extends AbstractField
{
    protected const string CONTROL_TYPE = 'checkbox';

    protected function defaultSanitizeCallback(): callable|string|null
    {
        return static fn (mixed $value): bool => ! ('0' === $value || 'false' === $value) && (bool) $value;
    }

    /**
     * @return array<string, mixed>
     */
    protected function settingArgs(): array
    {
        return [
            'default' => (bool) (
                1 === $this->defaultValue
                || '1' === $this->defaultValue
                || true === $this->defaultValue
                || 'true' === $this->defaultValue
                || 'on' === $this->defaultValue
            ),
        ];
    }
}
