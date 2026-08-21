<?php

declare(strict_types=1);

/**
 * The only file wp-env auto-loads (a plain WP_CONTENT_DIR/mu-plugins/*.php
 * file, not a subdirectory). .wp-env.json separately maps this package's
 * repo root to wp-content/mu-plugins/wpc-builder so vendor/autoload.php is
 * reachable from mu-plugin.php's normal, repo-relative path.
 */
require __DIR__ . '/wpc-builder/tests/E2E/mu-plugin.php';
