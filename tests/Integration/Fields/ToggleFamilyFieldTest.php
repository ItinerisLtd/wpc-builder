<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Controls\Toggle as ToggleControl;
use Itineris\WpcBuilder\Fields\CheckboxSwitch;
use Itineris\WpcBuilder\Fields\CheckboxToggle;
use Itineris\WpcBuilder\Fields\Toggle;

dataset('toggle family', [
    'CheckboxToggle' => [CheckboxToggle::class, 'toggle_family_checkbox_toggle'],
    'CheckboxSwitch' => [CheckboxSwitch::class, 'toggle_family_checkbox_switch'],
    'Toggle' => [Toggle::class, 'toggle_family_toggle'],
]);

// CheckboxSwitch and Toggle are bare `extends CheckboxToggle {}` with no
// overrides, so all three genuinely share one control class; this proves
// that shared ToggleControl registers for each and each alias persists.
test('each Checkbox-derived toggle alias registers the shared ToggleControl and persists a real boolean', function (
    string $class,
    string $id
): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    $class::make($id)->setLabel('Toggle')->register($wpCustomize, 'footer', new Config());

    expect($wpCustomize->get_control($id))->toBeInstanceOf(ToggleControl::class);

    $setting = $wpCustomize->get_setting($id);

    expect($setting->sanitize('1'))->toBeTrue()
        ->and($setting->sanitize('0'))->toBeFalse();

    $wpCustomize->set_post_value($id, '1');
    $setting->save();

    expect(get_theme_mod($id))->toBeTrue();
})->with('toggle family');
