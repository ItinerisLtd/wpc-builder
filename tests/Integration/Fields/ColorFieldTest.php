<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\Color;

test('a Color field accepts rgba() by default and persists it to theme_mod', function (): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    Color::make('accent_color')
        ->setLabel('Accent colour')
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('accent_color');

    // docs/known-limitations.md: a *stored* rgba() is safe once written.
    expect($setting->sanitize('rgba(12,34,56,0.5)'))->toBe('rgba(12,34,56,0.5)')
        ->and($setting->sanitize('not-a-colour'))->toBe('');

    $wpCustomize->set_post_value('accent_color', 'rgba(12,34,56,0.5)');
    $setting->save();

    expect(get_theme_mod('accent_color'))->toBe('rgba(12,34,56,0.5)');
});

test('a Color field with setAlpha(false) only accepts hex, via sanitize_hex_color', function (): void {
    $wpCustomize = wpc_builder_footer_customizer();

    Color::make('border_color')
        ->setLabel('Border colour')
        ->setAlpha(false)
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('border_color');

    expect($setting->sanitize('#AABBCC'))->toBe('#AABBCC')
        ->and($setting->sanitize('rgba(12,34,56,0.5)'))->toBeNull();
});
