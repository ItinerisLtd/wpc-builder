<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\Checkbox;

test('a Checkbox field sanitizes to a real boolean and persists to theme_mod', function (): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    Checkbox::make('newsletter_enabled')
        ->setLabel('Enable newsletter')
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('newsletter_enabled');

    expect($setting->sanitize('1'))->toBeTrue()
        ->and($setting->sanitize('0'))->toBeFalse();

    $wpCustomize->set_post_value('newsletter_enabled', '1');
    $setting->save();

    expect(get_theme_mod('newsletter_enabled'))->toBeTrue();
});
