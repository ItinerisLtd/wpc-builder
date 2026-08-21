<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Support;

final class RowLabel
{
    private function __construct(
        private readonly string $type,
        private readonly string $value,
        private readonly string $field
    ) {
    }

    public static function text(string $value): self
    {
        return new self('text', $value, '');
    }

    public static function fromField(string $fieldId): self
    {
        return new self('field', '', $fieldId);
    }

    /**
     * @return array{type: string, value: string, field?: string}
     */
    public function toArray(): array
    {
        $data = ['type' => $this->type, 'value' => $this->value];

        if ('' !== $this->field) {
            $data['field'] = $this->field;
        }

        return $data;
    }
}
