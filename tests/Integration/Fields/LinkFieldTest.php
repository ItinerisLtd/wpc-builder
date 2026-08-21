<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\Link;

test('a Link field sanitizes its compound value and persists it to theme_mod', function (): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    Link::make('cta_link')
        ->setLabel('Call to action')
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('cta_link');

    $value = ['url' => 'https://example.test', 'text' => '<b>Visit us</b>', 'target' => '_blank'];

    expect($setting->sanitize($value))->toBe([
        'url' => 'https://example.test',
        'text' => 'Visit us',
        'target' => '_blank',
    ]);

    $wpCustomize->set_post_value('cta_link', $value);
    $setting->save();

    expect(get_theme_mod('cta_link'))->toBe([
        'url' => 'https://example.test',
        'text' => 'Visit us',
        'target' => '_blank',
    ]);
});

test('a Link field rejects a url without text via its real validate_callback, so save() is a no-op', function (): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    Link::make('incomplete_link')
        ->setLabel('Incomplete link')
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('incomplete_link');

    $value = ['url' => 'https://example.test', 'text' => '', 'target' => '_self'];

    expect($setting->validate($value))->toBeInstanceOf(WP_Error::class);

    $wpCustomize->set_post_value('incomplete_link', $value);
    $result = $setting->save();

    expect($result)->toBeFalse()
        ->and(get_theme_mod('incomplete_link', false))->toBeFalse();
});
