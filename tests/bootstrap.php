<?php

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects

require_once dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Support\Asset::url() derives every asset URL from the package's own
 * position relative to WP_CONTENT_DIR. WP_CONTENT_DIR is a CONSTANT,
 * so it can't be stubbed per test. Asset::url() takes an optional
 * $contentDir seam instead, used directly by its own tests. Tests that
 * exercise it indirectly (Customizer, the control classes) have
 * no such seam, so the constant is defined here once, as the
 * directory that actually contains this checkout.
 */
if (! defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', dirname(__DIR__, 2));
}
