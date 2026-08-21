<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls;

use Itineris\WpcBuilder\Controls\Concerns\RendersDescription;
use Itineris\WpcBuilder\Controls\Concerns\RendersLabel;
use Itineris\WpcBuilder\Controls\Contracts\HasAssets;
use Itineris\WpcBuilder\Support\Asset;

use function __;
use function array_filter;
use function array_key_exists;
use function basename;
use function esc_html;
use function get_attached_file;
use function in_array;
use function is_array;
use function is_numeric;
use function wp_get_attachment_url;

/**
 * @phpstan-import-type EnqueuedAsset from HasAssets
 */
final class Repeater extends AbstractControl implements HasAssets
{
    use RendersLabel;
    use RendersDescription;

    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    public $type = 'wpc-builder-repeater';

    /**
     * Unlike $type above, these three properties don't exist on
     * WP_Customize_Control, so they can be natively typed here.
     * WP_Customize_Control::__construct()'s $args-copy only assigns a key
     * when a same-named property is already declared, so declaring these
     * with these exact (snake_case) names is what makes
     * 'fields'/'row_label'/'button_label' land on the control at all. An
     * undeclared property is silently dropped, not just deprecated. The
     * row limit reuses the inherited `$choices` property
     * (`choices['limit']`) the same way Multicheck/Dimensions do, so it
     * isn't redeclared here.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $fields = [];

    /** @var array{type?: string, value?: string, field?: string} */
    public array $row_label = [];

    public string $button_label = '';

    /**
     * Does not render the current value into this hidden input's
     * `value=` attribute. WordPress's own `api.Control` initialisation
     * runs synchronously before this control is embedded and does
     * `element.set(setting())` on every `[data-customize-setting-link]`
     * element. For a repeater, `setting()` is a PHP array, so jQuery's
     * `.val(array)` stringifies it, overwriting whatever this method
     * rendered, so a real value here would be corrupted immediately.
     * This control avoids the hazard by never reading the linked
     * element's DOM value at all; see `Controls\Multicheck` for the
     * general shape of it.
     *
     * `repeater.js`'s `seedRows()` instead treats `control.params.value`
     * as primary, which `Controls\AbstractControl::to_json()` populates;
     * `control.setting()` and this input's own DOM value are ordered
     * fallbacks.
     */
    public function renderContent(): void
    {
        $this->renderLabel();
        $this->renderDescription();
        ?>
        <div class="wpc-builder-repeater" data-repeater></div>
        <button type="button" class="button wpc-builder-repeater__add">
            <?php
            echo esc_html(
                '' !== $this->button_label ? $this->button_label : __('Add row', 'wpc-builder'),
            );
            ?>
        </button>
        <input type="hidden" <?php $this->link(); ?>>
        <?php
    }

    /**
     * Rehydrates an image-type sub-field's value from a bare attachment
     * id into `{id, url, filename}` for display. Without it, repeater.js's
     * image picker has no thumbnail to show for an already-saved value.
     *
     * Only attempts rehydration when the stored value is numeric, and
     * only substitutes when `wp_get_attachment_url()` returns something
     * truthy, narrower than `Support\AttachmentValue::sanitize()`, which
     * has no such guard and would silently wipe a still-recorded
     * attachment id whose url comes back empty
     * (`AttachmentValue::toUrl()` treats an empty `url` key as "use it
     * verbatim"). An id whose attachment has since been deleted is
     * therefore left as the raw id, not rewritten into a zero-value
     * array.
     *
     * Applies to 'image', 'cropped_image' and 'upload' sub-field types,
     * matching `Fields\Repeater::sanitizeSubfieldValue()`'s own grouping.
     * `$this->fields` here is the flat JS-facing descriptor array built
     * by `Fields\Repeater::controlArgs()`, not Field objects, so the
     * image-type check is a string comparison.
     *
     * An untouched, rehydrated row survives a later save unchanged:
     * `repeater.js`'s `seedRows()` seeds from `control.params.value`
     * first, and `Support\AttachmentValue::sanitize()`'s three `SaveAs`
     * branches all accept a `{id, url, filename}` array directly, which,
     * per the guard above, this method only ever produces with a
     * non-empty `url`.
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function to_json(): void
    {
        parent::to_json();

        $imageFieldIds = [];

        foreach ($this->fields as $id => $descriptor) {
            if (in_array($descriptor['type'] ?? null, ['image', 'cropped_image', 'upload'], true)) {
                $imageFieldIds[] = $id;
            }
        }

        if ([] === $imageFieldIds || ! is_array($this->json['value'] ?? null)) {
            return;
        }

        $rows = $this->json['value'];

        foreach ($rows as $rowId => $row) {
            if (! is_array($row)) {
                continue;
            }

            foreach ($imageFieldIds as $id) {
                if (! array_key_exists($id, $row) || ! is_numeric($row[$id])) {
                    continue;
                }

                $attachmentId = (int) $row[$id];
                $url = wp_get_attachment_url($attachmentId);

                if ($url) {
                    $row[$id] = [
                        'id' => $attachmentId,
                        'url' => $url,
                        'filename' => basename((string) get_attached_file($attachmentId)),
                    ];
                }
            }

            $rows[$rowId] = $row;
        }

        $this->json['value'] = $rows;
    }

    /**
     * @return array<string, mixed>
     */
    protected function jsonData(): array
    {
        $data = [
            'fields' => $this->fields,
            'row_label' => [] === $this->row_label ? null : $this->row_label,
            'limit' => $this->choices['limit'] ?? null,
            'button_label' => '' === $this->button_label ? null : $this->button_label,
        ];

        return array_filter($data, static fn (mixed $value): bool => null !== $value);
    }

    /**
     * @return array<int, EnqueuedAsset>
     */
    public static function assets(): array
    {
        $assets = [];

        $cssSrc = Asset::url('dist/css/repeater.css');

        if ('' !== $cssSrc) {
            $assets[] = [
                'type' => 'style',
                'handle' => 'wpc-builder-repeater',
                'src' => $cssSrc,
                'version' => Asset::version('dist/css/repeater.css'),
            ];
        }

        $jsSrc = Asset::url('dist/js/repeater.js');

        if ('' !== $jsSrc) {
            $assets[] = [
                'type' => 'script',
                'handle' => 'wpc-builder-repeater',
                'src' => $jsSrc,
                'dependencies' => [
                    'customize-controls',
                    'underscore',
                    'wp-i18n',
                    'wpc-builder-url-validation-core',
                ],
                'version' => Asset::version('dist/js/repeater.js'),
                'args' => ['in_footer' => true],
            ];
        }

        return $assets;
    }
}
