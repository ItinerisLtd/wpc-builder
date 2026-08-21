<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\Multicheck;

test('a Multicheck field sanitizes each value and persists to theme_mod', function (): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    Multicheck::make('enabled_features')
        ->setLabel('Enabled features')
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('enabled_features');

    expect($setting->sanitize(['<b>search</b>', 'share']))->toBe(['search', 'share']);

    $wpCustomize->set_post_value('enabled_features', ['search', 'share']);
    $setting->save();

    expect(get_theme_mod('enabled_features'))->toBe(['search', 'share']);
});
