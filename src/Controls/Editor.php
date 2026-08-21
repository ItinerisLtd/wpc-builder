<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls;

use Itineris\WpcBuilder\Controls\Concerns\HasControlsStylesheet;
use Itineris\WpcBuilder\Controls\Concerns\RendersDescription;
use Itineris\WpcBuilder\Controls\Concerns\RendersLabel;
use Itineris\WpcBuilder\Controls\Concerns\StringifiesMixed;
use Itineris\WpcBuilder\Controls\Contracts\HasAssets;
use Itineris\WpcBuilder\Support\Asset;
use function esc_attr;
use function esc_textarea;
use function Itineris\WpcBuilder\Support\editor_id_from_setting_id;

/**
 * @phpstan-import-type EnqueuedAsset from HasAssets
 */
final class Editor extends AbstractControl implements HasAssets
{
    use HasControlsStylesheet;
    use RendersLabel;
    use RendersDescription;
    use StringifiesMixed;

    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    public $type = 'wpc-builder-editor';

    public function renderContent(): void
    {
        $this->renderLabel();
        $this->renderDescription();

        $inputId = '_customize-input-' . $this->id;

        $editorId = editor_id_from_setting_id($this->id);
        $value = self::stringify($this->value());
        ?>
        <input
            type="hidden"
            id="<?php echo esc_attr($inputId); ?>"
            class="wpc-builder-editor__input"
            value="<?php echo esc_attr($value); ?>"
            <?php $this->link(); ?>
        >
        <div class="wpc-builder-editor" data-wpc-builder-editor-target="<?php echo esc_attr($inputId); ?>">
            <textarea
                id="<?php echo esc_attr($editorId); ?>"
                class="wpc-builder-editor__textarea"
                rows="10"
            ><?php echo esc_textarea($value); ?></textarea>
        </div>
        <?php
    }

    /**
     * A pure descriptor: it returns handle/src metadata, it doesn't call
     * wp_editor() or enqueue anything itself (wp_editor() can't be used
     * from a Customizer control at all; see renderContent()'s comment).
     * Only invoked when Editor is one of the control classes a site's
     * configured fields actually use
     * (Customizer::registeredControlClasses() filters
     * ControlAssetRegistrar to that list), not for every control type
     * this package defines.
     *
     * The heavier TinyMCE/Quicktags bootstrap, wp_enqueue_editor() plus
     * wp.editor.initialize() in editor.js, is enqueued separately from
     * Fields\Editor::afterRegister(),
     * which runs only once an Editor field is actually registered
     * against a real WP_Customize_Manager, a stricter condition than
     * merely being configured.
     *
     * @return array<int, EnqueuedAsset>
     */
    public static function assets(): array
    {
        $assets = self::controlsStylesheetAssets();

        $jsSrc = Asset::url('dist/js/editor.js');

        if ('' !== $jsSrc) {
            $assets[] = [
                'type' => 'script',
                'handle' => 'wpc-builder-editor',
                'src' => $jsSrc,
                'dependencies' => ['customize-controls', 'editor'],
                'version' => Asset::version('dist/js/editor.js'),
                'args' => ['in_footer' => true],
            ];
        }

        return $assets;
    }
}
