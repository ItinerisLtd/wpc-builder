<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use Itineris\WpcBuilder\Controls\Link as LinkControl;
use Itineris\WpcBuilder\Controls\Repeater as RepeaterControl;
use Itineris\WpcBuilder\Enums\SaveAs;
use Itineris\WpcBuilder\Enums\Transport;
use Itineris\WpcBuilder\Registrars\ControlAssetRegistrar;
use Itineris\WpcBuilder\Support\AttachmentValue;
use Itineris\WpcBuilder\Support\RowLabel;
use WP_Error;
use function __;
use function add_action;
use function array_keys;
use function call_user_func;
use function esc_url_raw;
use function filter_var;
use function html_entity_decode;
use function in_array;
use function is_array;
use function is_string;
use function Itineris\WpcBuilder\Support\is_valid_or_empty_url;
use function json_decode;
use function rawurldecode;
use function sanitize_text_field;
use function wp_dropdown_pages;
use function wp_kses_post;
use const FILTER_SANITIZE_EMAIL;

final class Repeater extends AbstractField
{
    protected const string CONTROL_TYPE = 'wpc-builder-repeater';
    protected const CONTROL = RepeaterControl::class;

    /** @var array<int, AbstractField> */
    private array $fields = [];

    private ?RowLabel $rowLabel = null;
    private ?int $limit = null;
    private ?string $buttonLabel = null;

    /**
     * @param array<int, AbstractField> $fields
     */
    public function setFields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function setRowLabel(RowLabel $rowLabel): self
    {
        $this->rowLabel = $rowLabel;

        return $this;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function setButtonLabel(string $label): self
    {
        $this->buttonLabel = $label;

        return $this;
    }

    /**
     * `wp.media()` (repeater.js's image sub-field picker) needs
     * wp_enqueue_media() specifically. A plain script dependency would
     * load the scripts but not the underscore.js templates, and the
     * picker would silently do nothing. Only enqueued when this
     * repeater has an Image sub-field.
     *
     * A Link sub-field needs the same conditional treatment: repeater
     * sub-fields are excluded from
     * Customizer::registeredControlClasses()'s scan, so
     * Controls\Link::assets() would otherwise never be enqueued for a
     * repeater-only Link sub-field. Each condition is checked
     * independently so a repeater with both gets both hooks.
     */
    protected function afterRegister(): void
    {
        $hasImage = false;
        $hasLink = false;

        foreach ($this->fields as $field) {
            $hasImage = $hasImage || $field instanceof Image;
            $hasLink = $hasLink || 'wpc-builder-link' === $field->controlType();
        }

        if ($hasImage) {
            add_action('customize_controls_enqueue_scripts', 'wp_enqueue_media');
        }

        if ($hasLink) {
            // Not a `static` closure: Brain\Monkey's static-closure
            // detection hack raises a PHP warning PHPUnit surfaces as a
            // test failure. See Fields\Editor::afterRegister()'s own
            // footer-scripts closure for the same reason.
            add_action('customize_controls_enqueue_scripts', function (): void {
                (new ControlAssetRegistrar([LinkControl::class]))->register();
            });
        }
    }

    protected function defaultTransport(): Transport
    {
        return Transport::REFRESH;
    }

    /**
     * A repeater is "blank" only when it has no rows at all; the
     * per-subfield value shapes underneath aren't inspected.
     */
    protected function isRequiredWhenValueBlank(mixed $value): bool
    {
        $value = self::decodeRows($value);

        return ! is_array($value) || [] === $value;
    }

    /**
     * The raw posted/stored value is the JSON+URL-encoded wire format
     * written by the hidden input, or already an array when read back
     * from a real WP_Customize_Setting. A string that fails to decode to
     * an array becomes [], not left as the failed decode's null/scalar
     * result, so every caller can assume "string in, array out".
     * Anything that wasn't a string in the first place (including an
     * already-decoded array) passes through untouched.
     */
    private static function decodeRows(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode(rawurldecode($value), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function settingArgs(): array
    {
        $fieldsById = [];

        foreach ($this->fields as $field) {
            $fieldsById[$field->id] = $field;
        }

        return [
            'transport' => Transport::REFRESH->value,
            'validate_callback' => static function (mixed $validity, mixed $value) use ($fieldsById): mixed {
                $value = self::decodeRows($value);

                if (! is_array($value) || [] === $fieldsById) {
                    return $validity;
                }

                foreach ($value as $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    foreach ($row as $subfieldId => $subfieldValue) {
                        if (! isset($fieldsById[$subfieldId])) {
                            continue;
                        }

                        $field = $fieldsById[$subfieldId];

                        if ('wpc-builder-link' === $field->controlType()) {
                            if (is_string($subfieldValue) && '' !== $subfieldValue) {
                                if (! is_valid_or_empty_url($subfieldValue)) {
                                    $error = $validity instanceof WP_Error ? $validity : new WP_Error();
                                    $error->add('invalid_url', __('Please enter a valid URL.', 'wpc-builder'));

                                    return $error;
                                }

                                continue;
                            }

                            $errorCode = Link::validateValue($subfieldValue);

                            if (null !== $errorCode) {
                                $error = $validity instanceof WP_Error ? $validity : new WP_Error();
                                $error->add(
                                    $errorCode,
                                    'invalid_url' === $errorCode
                                        ? __('Please enter a valid URL.', 'wpc-builder')
                                        : __('Please enter link text.', 'wpc-builder'),
                                );

                                return $error;
                            }

                            continue;
                        }

                        if (! in_array($field->controlType(), ['url', 'link'], true)) {
                            continue;
                        }

                        if (! is_valid_or_empty_url($subfieldValue)) {
                            $error = $validity instanceof WP_Error ? $validity : new WP_Error();
                            $error->add('invalid_url', __('Please enter a valid URL.', 'wpc-builder'));

                            return $error;
                        }
                    }
                }

                return $validity;
            },
        ];
    }

    /**
     * A failed/non-array decode becomes `[]` here, since every step
     * below assumes an array and this closure's `array` return type must
     * stay honest for PHPStan. A subfield id absent from the configured
     * fields is dropped from the row. See sanitizeSubfieldValue() below
     * for the per-type coercion.
     */
    protected function defaultSanitizeCallback(): callable
    {
        $fieldsById = [];

        foreach ($this->fields as $field) {
            $fieldsById[$field->id] = $field;
        }

        return static function (mixed $value) use ($fieldsById): array {
            $value = self::decodeRows($value);

            if (! is_array($value)) {
                return [];
            }

            if ([] === $fieldsById) {
                return $value;
            }

            foreach ($value as $rowId => $row) {
                if (! is_array($row)) {
                    $value[$rowId] = [];

                    continue;
                }

                foreach (array_keys($row) as $subfieldId) {
                    if (! isset($fieldsById[$subfieldId])) {
                        unset($row[$subfieldId]);

                        continue;
                    }

                    $row[$subfieldId] = self::sanitizeSubfieldValue($fieldsById[$subfieldId], $row[$subfieldId]);
                }

                $value[$rowId] = $row;
            }

            return $value;
        };
    }

    /**
     * An explicit per-sub-field `sanitize_callback` wins outright;
     * otherwise the value is coerced by the sub-field's `type` string.
     * Any type not listed below, including every `wpc-builder-*` control
     * type, is left untouched via the `default => $value` arm.
     *
     * Notable cases:
     * - 'color' reuses `Fields\Color::sanitizeAlpha()`, safe because
     *   the repeater JS persists a 'color' sub-field from a text
     *   input (the swatch beside it only mirrors it), so its value is
     *   always a string or empty/falsy, never an r/g/b or h/s/l object
     *   a colour-picker widget could submit.
     * - 'wpc-builder-link': a non-empty string stays a plain
     *   `esc_url_raw()` string, exactly like 'url'/'link'. A legacy
     *   row never gets silently upgraded to the compound shape by an
     *   unrelated save. Anything else goes through
     *   `Link::sanitizeValue()`.
     * - 'select' has both a load-bearing array branch and one real
     *   judgment call, documented on its own further down.
     * - 'email'/'tel' have no matching Field class in this package
     *   today (unreachable via this package's own API), but are kept
     *   for fidelity: a `default => $value` catch-all can't
     *   distinguish "untouched on purpose" from "forgotten".
     */
    private static function sanitizeSubfieldValue(AbstractField $field, mixed $value): mixed
    {
        $callback = $field->sanitizeCallback();

        if (null !== $callback && is_callable($callback)) {
            return call_user_func($callback, $value);
        }

        return match ($field->controlType()) {
            'image', 'cropped_image', 'upload' => AttachmentValue::sanitize(
                $value,
                $field instanceof Image ? $field->saveAs() : SaveAs::ID,
            ),
            'dropdown-pages' => self::toIntValue($value),
            'color' => $value ? Color::sanitizeAlpha($value) : $value,
            'text', 'tel', 'radio', 'radio-image' => sanitize_text_field(self::toScalarString($value)),
            'url', 'link' => esc_url_raw(self::toScalarString($value)),
            'wpc-builder-link' => is_string($value) && '' !== $value
                ? esc_url_raw($value)
                : Link::sanitizeValue($value),
            'email' => filter_var($value, FILTER_SANITIZE_EMAIL),
            'checkbox' => (bool) $value,
            'select' => $field instanceof Select && $field->isMultiple()
                ? self::sanitizeMultiSelectValue($value)
                : sanitize_text_field(self::toScalarString($value)),
            'textarea' => html_entity_decode(wp_kses_post(self::toScalarString($value))),
            default => $value,
        };
    }

    /**
     * A non-scalar into e.g. `sanitize_text_field()` under this
     * package's `strict_types=1` would be a fatal TypeError, so a
     * non-scalar coerces to `''` here instead, matching the same
     * guard-then-cast pattern used elsewhere (Fields\Select,
     * Support\AttachmentValue).
     */
    private static function toScalarString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * Same rationale as toScalarString() above, for the bare `(int)`
     * cast on 'dropdown-pages', not `absint()`, so a negative numeric
     * string stays negative.
     */
    private static function toIntValue(mixed $value): int
    {
        return is_scalar($value) ? (int) $value : 0;
    }

    /**
     * A bare `(array)` cast on `mixed` is PHPStan-forbidden, so the
     * guarded match below reproduces the intended coercion instead: an
     * already-array value stays as-is with keys preserved; anything else
     * becomes a single-element array.
     *
     * @return array<int|string, string>
     */
    private static function sanitizeMultiSelectValue(mixed $value): array
    {
        $values = match (true) {
            is_array($value) => $value,
            null === $value => [],
            default => [$value],
        };

        foreach ($values as $key => $item) {
            $values[$key] = sanitize_text_field(self::toScalarString($item));
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    protected function controlArgs(): array
    {
        $fields = [];

        foreach ($this->fields as $field) {
            $descriptor = [
                'id' => $field->id,
                'type' => $field->controlType(),
                'label' => $field->label(),
                'description' => $field->description() ?? '',
                'default' => $field->defaultValue() ?? '',
                'choices' => $field->choices(),
            ];

            if ($field instanceof Select && $field->isMultiple()) {
                $descriptor['multiple'] = true;
            }

            if ('dropdown-pages' === $descriptor['type']) {
                $descriptor['dropdown'] = wp_dropdown_pages([
                    'name' => '',
                    'echo' => 0,
                    'show_option_none' => 'Select a page',
                    'option_none_value' => '0',
                    'selected' => '',
                ]);
            }

            if ('wpc-builder-link' === $descriptor['type']) {
                $descriptor['link'] = LinkControl::renderFieldsMarkup();
            }

            $fields[$field->id] = $descriptor;
        }

        $args = ['fields' => $fields];

        if (null !== $this->rowLabel) {
            $args['row_label'] = $this->rowLabel->toArray();
        }

        if (null !== $this->limit) {
            $args['choices'] = ['limit' => $this->limit];
        }

        if (null !== $this->buttonLabel) {
            $args['button_label'] = $this->buttonLabel;
        }

        return $args;
    }
}
