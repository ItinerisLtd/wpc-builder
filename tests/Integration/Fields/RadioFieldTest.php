<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\Radio;
use Itineris\WpcBuilder\Fields\RadioButtonset;

dataset('radio family', [
    'Radio' => [Radio::class, 'radio_family_radio'],
    'RadioButtonset' => [RadioButtonset::class, 'radio_family_buttonset'],
]);

test('each Radio-derived field falls back to its default for an unknown choice and persists a known one', function (
    string $class,
    string $id
): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    $class::make($id)
        ->setLabel('Alignment')
        ->setChoices(['left' => 'Left', 'right' => 'Right'])
        ->setDefaultValue('left')
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting($id);

    expect($setting->sanitize('centre'))->toBe('left')
        ->and($setting->sanitize('right'))->toBe('right');

    $wpCustomize->set_post_value($id, 'right');
    $setting->save();

    expect(get_theme_mod($id))->toBe('right');
})->with('radio family');
