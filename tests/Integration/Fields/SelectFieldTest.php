<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\Select;

// Single-select's own sanitize_callback is the bare 'sanitize_text_field'
// string, covered by SanitizeTextFieldFamilyFieldTest's dataset; only the
// multiple-select behaviour below is unique to this field.
test('a multiple Select field caps and sanitizes each value, and persists to theme_mod', function (): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    Select::make('tags')
        ->setLabel('Tags')
        ->setChoices(['a' => 'A', 'b' => 'B', 'c' => 'C'])
        ->setMultiple()
        ->setMaxSelectionNumber(2)
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('tags');

    expect($setting->sanitize(['a', '<b>b</b>', 'c']))->toBe(['a', 'b']);

    $wpCustomize->set_post_value('tags', ['a', 'c']);
    $setting->save();

    expect(get_theme_mod('tags'))->toBe(['a', 'c']);
});
