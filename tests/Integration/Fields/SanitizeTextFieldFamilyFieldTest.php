<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\Dimensions;
use Itineris\WpcBuilder\Fields\DropdownPages;
use Itineris\WpcBuilder\Fields\Select;

dataset('sanitize_text_field family', [
    'DropdownPages' => [DropdownPages::class, 'text_family_dropdown_pages'],
    'Dimensions' => [Dimensions::class, 'text_family_dimensions'],
    'Select' => [Select::class, 'text_family_select'],
]);

test('each sanitize_text_field-backed field strips markup and persists to theme_mod', function (
    string $class,
    string $id
): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    $class::make($id)->setLabel('Value')->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting($id);

    expect($setting->sanitize('<b>10px</b>'))->toBe('10px');

    $wpCustomize->set_post_value($id, '10px');
    $setting->save();

    expect(get_theme_mod($id))->toBe('10px');
})->with('sanitize_text_field family');
