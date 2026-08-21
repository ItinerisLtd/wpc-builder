<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use Itineris\WpcBuilder\Controls\Image as ImageControl;
use Itineris\WpcBuilder\Enums\SaveAs;
use Itineris\WpcBuilder\Support\AttachmentValue;

use function add_action;

final class Image extends AbstractField
{
    /**
     * Stays 'image', not 'wpc-builder-image': Fields\Repeater's
     * per-sub-field coercion switch matches this exact string, as do
     * Controls\Repeater::to_json()'s rehydration pass and
     * assets/src/js/repeater.js's sub-field lookup. The control type
     * reported to WordPress is overridden in controlArgs() below.
     */
    protected const string CONTROL_TYPE = 'image';

    protected const CONTROL = ImageControl::class;

    private SaveAs $saveAs = SaveAs::ID;

    public function setSaveAs(SaveAs $saveAs): self
    {
        $this->saveAs = $saveAs;

        return $this;
    }

    /**
     * Read by Fields\Repeater to apply this field's own save_as default
     * when it's used as a repeater sub-field.
     */
    public function saveAs(): SaveAs
    {
        return $this->saveAs;
    }

    /**
     * The control type reported to WordPress diverges from CONTROL_TYPE
     * here, and this is the only field that does it. A control whose
     * reported type is 'image' is instantiated in the browser as
     * core's `api.ImageControl`, which replaces this package's
     * server-rendered markup with core's own media template wholesale.
     * Under 'wpc-builder-image' there is no such constructor entry, so
     * the browser keeps the server-rendered `params.content` instead.
     * CONTROL_TYPE itself can't change (the repeater matches on it);
     * controlArgs() is spread after the base keys in
     * AbstractField::buildControlArgs(), so returning 'type' here wins.
     * `save_as` lands on Controls\Image's own declared `$save_as`
     * property.
     *
     * @return array<string, mixed>
     */
    protected function controlArgs(): array
    {
        return [
            'type' => 'wpc-builder-image',
            'save_as' => $this->saveAs->value,
        ];
    }

    /**
     * Same reasoning as Fields\Repeater::afterRegister() for a repeater
     * carrying an Image sub-field: `wp_enqueue_media()` pulls in the
     * whole media-modal bundle, only justified on a site that actually
     * registers an Image field.
     */
    protected function afterRegister(): void
    {
        add_action('customize_controls_enqueue_scripts', 'wp_enqueue_media');
    }

    protected function defaultSanitizeCallback(): callable
    {
        $saveAs = $this->saveAs;

        return static fn (mixed $value): mixed => AttachmentValue::sanitize($value, $saveAs);
    }
}
