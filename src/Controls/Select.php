<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls;

use Itineris\WpcBuilder\Controls\Concerns\HasControlsStylesheet;
use Itineris\WpcBuilder\Controls\Concerns\RendersDescription;
use Itineris\WpcBuilder\Controls\Concerns\StringifiesMixed;
use Itineris\WpcBuilder\Controls\Contracts\HasAssets;
use Itineris\WpcBuilder\Support\Asset;

use function array_map;
use function array_values;
use function esc_attr;
use function esc_html;
use function in_array;
use function is_array;
use function selected;

/**
 * Replaces WordPress core's built-in `select` rendering so that a
 * `setMultiple()` select actually renders as one. Core's own `select`
 * branch never calls `$this->input_attrs()`, so a `multiple` attribute
 * pushed through it never reached the DOM: the control rendered as a
 * plain single-select and the editor's first change collapsed the
 * stored array to one element, a common "show/hide on these pages"
 * multi-select pattern.
 *
 * Multiple mode omits `$this->link()`: a multi-select's value is an
 * array, and `data-customize-setting-link` binding carries only one
 * scalar per element (see `Controls\Multicheck`).
 * `assets/src/js/select.js` writes the array with
 * `wp.customize(id).set(array)` instead; single-select mode keeps
 * `$this->link()`.
 *
 * `max_selection_number` isn't enforced client-side; the authoritative
 * cap is `Fields\Select`'s sanitize callback, and duplicating it here
 * is how the two would drift apart.
 *
 * @phpstan-import-type EnqueuedAsset from HasAssets
 */
final class Select extends AbstractControl implements HasAssets
{
    use HasControlsStylesheet;
    use RendersDescription;
    use StringifiesMixed;

    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    public $type = 'select';

    /**
     * Natively typed, unlike $type above: WP_Customize_Control has no
     * $multiple property to conflict with, so declaring it is what makes
     * Fields\Select's 'multiple' control arg actually land (an
     * undeclared property is silently dropped by __construct()'s $args
     * copy).
     */
    public bool $multiple = false;

    /**
     * Markup-compatible with the core `select` branch this control
     * replaces: the same `_customize-input-{$id}` id scheme, the same
     * early return on empty choices (before anything renders, so a
     * `<label for>` never points at nothing), and a real `<label for>`
     * plus `aria-describedby` for both single and multiple mode. Using
     * Concerns\RendersLabel here instead would emit an unassociated
     * `<span>`, regressing accessibility across every select
     * (`Fields\Select` routes them all through this control). Only the
     * binding differs
     * between modes (see the class docblock).
     */
    public function renderContent(): void
    {
        if ([] === $this->choices) {
            return;
        }

        $inputId = '_customize-input-' . $this->id;
        $descriptionId = '_customize-description-' . $this->id;

        $this->renderLabelFor($inputId);
        $this->renderDescription($descriptionId);

        $this->multiple
            ? $this->renderMultiple($inputId, $descriptionId)
            : $this->renderSingle($inputId, $descriptionId);
    }

    /**
     * @return array<int, EnqueuedAsset>
     */
    public static function assets(): array
    {
        $assets = self::controlsStylesheetAssets();

        $jsSrc = Asset::url('dist/js/select.js');

        if ('' !== $jsSrc) {
            $assets[] = [
                'type' => 'script',
                'handle' => 'wpc-builder-select',
                'src' => $jsSrc,
                'dependencies' => ['customize-controls', 'wp-element', 'wp-components', 'wp-i18n'],
                'version' => Asset::version('dist/js/select.js'),
                'args' => ['in_footer' => true],
            ];
        }

        return $assets;
    }

    /**
     * `<label for>`, matching core's select branch. Concerns\RendersLabel
     * cannot be used here because it emits a bare `<span>` with nothing to
     * associate it with an input.
     */
    private function renderLabelFor(string $inputId): void
    {
        if (empty($this->label)) {
            return;
        }
        ?>
        <label
            for="<?php echo esc_attr($inputId); ?>"
            class="customize-control-title"
        ><?php echo esc_html(self::stringify($this->label)); ?></label>
        <?php
    }

    private function renderSingle(string $inputId, string $descriptionId): void
    {
        ?>
        <select
            id="<?php echo esc_attr($inputId); ?>"
            class="wpc-builder-select wpc-builder-select--single"
            data-wpc-builder-select-ui="enhance"
            data-wpc-builder-select-setting="<?php echo esc_attr($this->id); ?>"
            <?php if (! empty($this->description)) : ?>
                aria-describedby="<?php echo esc_attr($descriptionId); ?>"
            <?php endif; ?>
            <?php $this->link(); ?>
        >
            <?php foreach ($this->choices as $value => $label) : ?>
                <?php $value = (string) $value; ?>
                <option
                    value="<?php echo esc_attr($value); ?>"
                    <?php selected($this->stringValue(), $value); ?>
                ><?php echo esc_html(self::stringify($label)); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    private function renderMultiple(string $inputId, string $descriptionId): void
    {
        $selected = $this->selectedValues();
        ?>
        <select
            id="<?php echo esc_attr($inputId); ?>"
            multiple="multiple"
            class="wpc-builder-select wpc-builder-select--multiple"
            data-wpc-builder-select-ui="enhance"
            data-wpc-builder-select-setting="<?php echo esc_attr($this->id); ?>"
            <?php if (! empty($this->description)) : ?>
                aria-describedby="<?php echo esc_attr($descriptionId); ?>"
            <?php endif; ?>
        >
            <?php foreach ($this->choices as $value => $label) : ?>
                <?php $value = (string) $value; ?>
                <option
                    value="<?php echo esc_attr($value); ?>"
                    <?php echo in_array($value, $selected, true) ? 'selected="selected"' : ''; ?>
                ><?php echo esc_html(self::stringify($label)); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * The stored value as a list of strings to match option values
     * against. A field switched to multiple AFTER a single value was
     * already saved still has a bare scalar in storage; treating it as a
     * one-element selection preserves it instead of showing nothing
     * selected. Array keys are discarded; only the option VALUES decide
     * what is selected.
     *
     * @return array<int, string>
     */
    private function selectedValues(): array
    {
        $value = $this->value();

        if (is_array($value)) {
            return array_map(self::stringify(...), array_values($value));
        }

        return '' === self::stringify($value) ? [] : [self::stringify($value)];
    }

    private function stringValue(): string
    {
        return self::stringify($this->value());
    }
}
