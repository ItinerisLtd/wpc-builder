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

    /**
     * @param string $fieldId       Sub-field id to read the row label from.
     * @param string $fallbackLabel Shown, with the row number appended, when $fieldId is blank.
     */
    public static function fromField(string $fieldId, string $fallbackLabel = ''): self
    {
        return new self('field', $fallbackLabel, $fieldId);
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
