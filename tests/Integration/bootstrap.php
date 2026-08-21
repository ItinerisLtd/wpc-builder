<?php

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

define('WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php');

$wpPhpunitDir = dirname(__DIR__, 2) . '/vendor/wp-phpunit/wp-phpunit';

require_once $wpPhpunitDir . '/includes/functions.php';

tests_add_filter('muplugins_loaded', static function (): void {
    // Only wp-admin/customize.php loads this normally.
    require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
});

require $wpPhpunitDir . '/includes/bootstrap.php';

// No test here extends WP_UnitTestCase, so nothing snapshots/restores
// $wp_filter or rolls back the database between tests. Keep setting/field
// ids either identical-in-behaviour or unique across this suite, and give
// anything written to the database a value a later test won't collide
// with (see the randomised login/email below).

/**
 * @throws RuntimeException When user creation fails.
 */
function wpc_builder_log_in_as_admin(): int
{
    $userId = wp_insert_user([
        'user_login' => 'wpc-builder-test-admin-' . wp_generate_password(8, false),
        'user_pass' => 'password',
        'user_email' => 'admin-' . wp_generate_password(8, false) . '@example.org',
        'role' => 'administrator',
    ]);

    if (is_wp_error($userId)) {
        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        throw new RuntimeException($userId->get_error_message());
    }

    wp_set_current_user($userId);

    return $userId;
}

/**
 * @throws RuntimeException When attachment creation fails.
 */
function wpc_builder_create_test_attachment(string $file): int
{
    // @phpstan-ignore argument.type
    $attachmentId = (new WP_UnitTest_Factory_For_Attachment(null))->create_upload_object($file);

    if (is_wp_error($attachmentId)) {
        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        throw new RuntimeException($attachmentId->get_error_message());
    }

    return $attachmentId;
}

/**
 * Shared setup for every Fields\* integration test: a real manager with
 * the one section every field under test registers its control against.
 */
function wpc_builder_footer_customizer(): WP_Customize_Manager
{
    $wpCustomize = new WP_Customize_Manager();
    $wpCustomize->add_section('footer', ['title' => 'Footer']);

    return $wpCustomize;
}

/**
 * @throws RuntimeException When post creation fails.
 */
function wpc_builder_create_test_page(): int
{
    $postId = wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => 'wpc-builder test page ' . wp_generate_password(8, false),
    ], true);

    if (is_wp_error($postId)) {
        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        throw new RuntimeException($postId->get_error_message());
    }

    return $postId;
}
