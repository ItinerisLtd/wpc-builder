<?php

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects

/**
 * Loaded by wp-env (see .wp-env.json's mappings) as a real mu-plugin on a
 * real WordPress request lifecycle. Registers this package's Customizer
 * fixture section so tests/E2E/specs can drive the actual admin UI.
 */

namespace Itineris\WpcBuilder\Tests\E2E;

use Itineris\WpcBuilder\Customizer;
use Itineris\WpcBuilder\Tests\Fixtures\Sections\E2eSectionFixture;
use Itineris\WpcBuilder\Tests\Fixtures\Sections\FooterSectionFixture;

use function add_action;
use function esc_html;
use function get_theme_mod;
use function sprintf;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

/**
 * Selective-refresh render callback for the 'live_message' field:
 * re-renders the exact markup wp_footer() below prints on first load, so
 * the partial's selector matches whether it's the initial render or a
 * live refresh. Already escaped internally.
 */
function render_live_message(): string
{
    return sprintf(
        '<div data-wpc-builder-e2e="live_message">%s</div>',
        esc_html((string) get_theme_mod('live_message', '')),
    );
}

add_action('customize_register', static function (): void {
    Customizer::make()
        ->addSections([new FooterSectionFixture(), new E2eSectionFixture()])
        ->register();
});

add_action('wp_footer', static function (): void {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo render_live_message();
});
