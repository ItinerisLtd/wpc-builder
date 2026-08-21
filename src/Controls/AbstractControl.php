<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls;

use WP_Customize_Control;

/**
 * `renderContent()` runs inside `to_json()`, not before or after it:
 * WP_Customize_Control::to_json() sets $this->json['content'] via
 * get_content() -> render() -> render_content() -> (our) renderContent().
 * The rendered HTML ships to the browser as JSON and is parsed into a
 * detached DOM fragment, only appended to the page once its section is
 * embedded, never at DOMContentLoaded.
 *
 * Two consequences for a subclass's renderContent(): never call
 * $this->json()/$this->to_json() from within it (it closes an unguarded
 * recursive loop back into render_content()), and inline `<script>` that
 * expects the markup already in `document` will find nothing. Bind
 * through the Customizer's own JS lifecycle
 * (`control.deferred.embedded.done(...)`) or a delegated `document`
 * listener instead.
 */
abstract class AbstractControl extends WP_Customize_Control
{
    abstract public function renderContent(): void;

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    final public function render_content(): void
    {
        $this->renderContent();
    }

    /**
     * @return array<string, mixed>
     */
    protected function jsonData(): array
    {
        return [];
    }

    /**
     * WordPress core's own `WP_Customize_Control::to_json()` sets nine
     * keys and none of them is `value`. This line adds it
     * unconditionally, since every control in this package relies on
     * `control.params.value` being populated.
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function to_json(): void
    {
        parent::to_json();

        $this->json['value'] = $this->value();

        $data = $this->jsonData();

        if ([] !== $data) {
            $this->json['field'] = $data;
        }
    }
}
