<?php

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects

define('ABSPATH', dirname(__DIR__, 2) . '/wordpress/');

// Uploads get written here (see the Image field test); stays inside the
// checkout (gitignored /uploads/) since it's a shared git worktree.
define('WP_CONTENT_DIR', dirname(__DIR__, 2));

// Matches .devcontainer/docker-compose.yml's database service.
define('DB_NAME', 'wordpress_test');
define('DB_USER', 'root');
define('DB_PASSWORD', 'password');
define('DB_HOST', 'database');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
$table_prefix = 'wptests_';

define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Test Blog');

define('WP_PHP_BINARY', PHP_BINARY);

define('WPLANG', '');
