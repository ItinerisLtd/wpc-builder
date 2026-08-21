<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\Slider;

test('a Slider field sanitizes with the same numeric filter as Number and persists to theme_mod', function (): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    Slider::make('opacity')
        ->setLabel('Opacity')
        ->setMin(0)
        ->setMax(1)
        ->setStep(0.1)
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('opacity');

    // Unlike Number, Slider does not clamp: it's a bare numeric filter.
    expect($setting->sanitize('0.5'))->toBe('0.5');

    $wpCustomize->set_post_value('opacity', '0.7');
    $setting->save();

    expect(get_theme_mod('opacity'))->toBe('0.7');
});
