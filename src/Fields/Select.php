<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use Itineris\WpcBuilder\Controls\Select as SelectControl;

use function array_slice;
use function is_array;
use function is_scalar;
use function sanitize_text_field;

class Select extends AbstractChoiceField
{
    /**
     * Stays 'select': Fields\Repeater's per-sub-field coercion switch
     * matches this exact string, and it's also the control `type` core
     * reports to the browser, where 'select' is safely unmapped.
     */
    protected const string CONTROL_TYPE = 'select';

    /**
     * A dedicated control class, rather than letting WordPress resolve
     * `type => 'select'` to its own built-in rendering, which never
     * calls `input_attrs()`. `setMultiple()` couldn't reach the DOM at
     * all. See Controls\Select's docblock.
     */
    protected const CONTROL = SelectControl::class;

    private bool $multiple = false;
    private int $maxSelectionNumber = 999;

    public function setMultiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;

        return $this;
    }

    /**
     * Read by Fields\Repeater to decide, for a Select sub-field, whether
     * to apply the repeater-specific array-value coercion branch. See
     * that method's own docblock.
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function setMaxSelectionNumber(int $max): self
    {
        $this->maxSelectionNumber = $max;

        return $this;
    }

    protected function defaultSanitizeCallback(): callable|string
    {
        if (! $this->multiple) {
            return 'sanitize_text_field';
        }

        $max = $this->maxSelectionNumber;

        return static function (mixed $values) use ($max): array {
            $values = match (true) {
                is_array($values) => $values,
                null === $values => [],
                default => [$values],
            };

            $values = array_slice($values, 0, $max, true);

            return array_map(
                static fn (mixed $value): string => sanitize_text_field(is_scalar($value) ? (string) $value : ''),
                $values,
            );
        };
    }

    /**
     * `multiple` is passed as a first-class control arg, landing on
     * Controls\Select's own declared `$multiple` property. Pushing it
     * through `input_attrs` instead is inert, since core's `select`
     * branch never calls `input_attrs()`. See Controls\Select.
     *
     * @return array<string, mixed>
     */
    protected function controlArgs(): array
    {
        $args = parent::controlArgs();

        if ($this->multiple) {
            $args['multiple'] = true;
        }

        return $args;
    }
}
