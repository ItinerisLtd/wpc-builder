<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use Itineris\WpcBuilder\Controls\Custom as CustomControl;

final class Custom extends AbstractField
{
    protected const string CONTROL_TYPE = 'wpc-builder-custom';
    protected const CONTROL = CustomControl::class;

    protected string $html = '';

    public function setHtml(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    /**
     * A `Custom` field's control is display-only, with no input for a
     * user to edit. Registering a setting for it anyway, with
     * `sanitize_callback` pointed at `__return_null`, would be harmless
     * under the display-only control itself (nothing ever marks the
     * setting dirty), but latent: anything that does (a force-dirty
     * resave, a programmatic `set()`) makes WordPress read the null
     * sanitize result as invalid and abort the entire changeset save,
     * not only this field.
     *
     * This package therefore registers no setting for a `Custom` field
     * (see AbstractField::registersSetting()). A `Custom` field stores
     * nothing either way, so no existing row changes shape.
     */
    protected function registersSetting(): bool
    {
        return false;
    }

    /**
     * Kept even though registersSetting() means it's never passed to
     * add_setting(). buildSettingArgs() is public API and should still
     * show what the field would have registered.
     */
    protected function defaultSanitizeCallback(): string
    {
        return '__return_null';
    }

    /**
     * @return array<string, mixed>
     */
    protected function controlArgs(): array
    {
        return ['html' => $this->html];
    }
}
