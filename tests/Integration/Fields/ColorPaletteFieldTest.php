<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\ColorPalette;

test('a ColorPalette field sanitizes with its own case-sensitive regex and persists to theme_mod', function (): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    ColorPalette::make('brand_color')
        ->setLabel('Brand colour')
        ->setColors(['#ff0000', '#00ff00'])
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('brand_color');

    // No case-insensitive flag on the regex; an uppercase hex is rejected,
    // not normalised. Documented on the field, kept intentionally.
    expect($setting->sanitize('#ff0000'))->toBe('#ff0000')
        ->and($setting->sanitize('#FF0000'))->toBe('')
        ->and($setting->sanitize('not-a-colour'))->toBe('');

    $wpCustomize->set_post_value('brand_color', '#00ff00');
    $setting->save();

    expect(get_theme_mod('brand_color'))->toBe('#00ff00');
});
