<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\Number;

test('a Number field clamps to its min/max and persists to theme_mod', function (): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    Number::make('columns')
        ->setLabel('Columns')
        ->setMin(2)
        ->setMax(6)
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('columns');

    // filter_var(..., FILTER_SANITIZE_NUMBER_FLOAT) returns a numeric string.
    expect($setting->sanitize('12'))->toBe('6')
        ->and($setting->sanitize('0'))->toBe('2');

    $wpCustomize->set_post_value('columns', '4');
    $setting->save();

    expect(get_theme_mod('columns'))->toBe('4');
});
