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

test('a Number field with only a min set still clamps values below it', function (): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    Number::make('quantity')
        ->setLabel('Quantity')
        ->setMin(1)
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('quantity');

    expect($setting->sanitize('-5'))->toBe('1')
        ->and($setting->sanitize('99'))->toBe('99');
});

test('a Number field with only a max set still clamps values above it', function (): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    Number::make('discount')
        ->setLabel('Discount')
        ->setMax(100)
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('discount');

    expect($setting->sanitize('150'))->toBe('100')
        ->and($setting->sanitize('50'))->toBe('50');
});

test('a Number field clamps a malformed multi-dot value by its leading numeric portion', function (): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    Number::make('score')
        ->setLabel('Score')
        ->setMin(1)
        ->setMax(9)
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('score');

    // "50.1.2" is not a valid numeric string, so PHP's numeric-string
    // comparison rules fall back to a byte-wise compare ('5' < '9')
    // unless both sides are cast to float first; confirms the clamp
    // reads it as ~50.1 (above the max), not as "less than 9".
    expect($setting->sanitize('50.1.2'))->toBe('9');
});
