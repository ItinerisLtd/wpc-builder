<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\Url;

test('a Url field sanitizes with wp_kses_post and persists a valid url to theme_mod', function (): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    Url::make('external_link')
        ->setLabel('External link')
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('external_link');

    expect($setting->sanitize('<script>alert(1)</script>https://example.test'))->toBe('alert(1)https://example.test');

    $wpCustomize->set_post_value('external_link', 'https://example.test');
    $setting->save();

    expect(get_theme_mod('external_link'))->toBe('https://example.test');
});

test('a Url field rejects a disallowed protocol via its real validate_callback, so save() no-ops', function (): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    Url::make('malicious_link')
        ->setLabel('Malicious link')
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('malicious_link');

    expect($setting->validate('javascript:alert(1)'))->toBeInstanceOf(WP_Error::class);

    $wpCustomize->set_post_value('malicious_link', 'javascript:alert(1)');
    $result = $setting->save();

    // check_capabilities() + isset($value) both gate save(); an invalid
    // post value never reaches update(), so nothing is written at all.
    expect($result)->toBeFalse()
        ->and(get_theme_mod('malicious_link', false))->toBeFalse();
});
