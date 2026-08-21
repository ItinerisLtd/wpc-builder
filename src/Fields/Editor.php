<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use _WP_Editors;
use Itineris\WpcBuilder\Controls\Editor as EditorControl;

use function add_action;
use function did_action;

final class Editor extends AbstractField
{
    protected const string CONTROL_TYPE = 'wpc-builder-editor';
    protected const CONTROL = EditorControl::class;

    protected function defaultSanitizeCallback(): callable|string
    {
        return 'wp_kses_post';
    }

    /**
     * Loading TinyMCE/Quicktags (several hundred KB) is only justified
     * when a site actually has an Editor field. This runs once per
     * Editor field actually registered against a real
     * WP_Customize_Manager, a stricter condition than
     * Controls\Editor::assets() below, which only requires the field to
     * be configured.
     *
     * Two hooks are needed: wp_enqueue_editor() alone doesn't work,
     * because nothing in a Customizer screen fires
     * admin_print_footer_scripts unless the 'widgets' component is
     * loaded (commonly removed on block themes). Without it,
     * wp.editor.initialize() silently no-ops. So
     * print_default_editor_scripts() is also hooked directly onto
     * customize_controls_print_footer_scripts, which always fires.
     *
     * That second hook isn't safe to fire unconditionally: when
     * 'widgets' is loaded, WP_Customize_Widgets::print_footer_scripts()
     * already runs print_default_editor_scripts() once via a nested
     * admin_print_footer_scripts action, and this class's own hook
     * would run it a second time. Core's own guards don't cover the
     * inline TinyMCE settings `<script>`, which would print twice. The
     * wrapper guards against this with did_action() rather than trying
     * to infer whether 'widgets' is loaded.
     *
     * Registered at priority 20, not core's default 10: customize.php's
     * own `_wp_footer_scripts` hook runs later still, and at the
     * default priority this class's hook would print the inline
     * settings `<script>` before editor.js's own `<script>` tag is
     * flushed. Priority 20 restores the intended order.
     */
    protected function afterRegister(): void
    {
        add_action('customize_controls_enqueue_scripts', 'wp_enqueue_editor');

        add_action('customize_controls_enqueue_scripts', 'wp_enqueue_media');

        add_action(
            'customize_controls_print_footer_scripts',
            function (): void {
                if (did_action('print_default_editor_scripts')) {
                    return;
                }

                _WP_Editors::print_default_editor_scripts();
            },
            20,
        );
    }
}
