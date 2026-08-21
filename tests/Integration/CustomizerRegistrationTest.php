<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Customizer;
use Itineris\WpcBuilder\Tests\Fixtures\Sections\FooterSectionFixture;

test('a registered section and its fields land on a real WP_Customize_Manager', function (): void {
    $wpCustomize = new WP_Customize_Manager();

    Customizer::make()
        ->addSections([FooterSectionFixture::class])
        ->register();

    do_action('customize_register', $wpCustomize);

    $section = $wpCustomize->get_section('footer');

    expect($section)->toBeInstanceOf(WP_Customize_Section::class)
        ->and($section->title)->toBe('Footer')
        ->and($section->description)->toBe('Footer settings')
        ->and($section->priority)->toBe(160);

    $setting = $wpCustomize->get_setting('footer_copyright');

    expect($setting)->toBeInstanceOf(WP_Customize_Setting::class)
        ->and($setting->type)->toBe('theme_mod')
        ->and($setting->capability)->toBe('edit_theme_options');

    $control = $wpCustomize->get_control('footer_copyright');

    expect($control)->toBeInstanceOf(WP_Customize_Control::class)
        ->and($control->section)->toBe('footer')
        ->and($control->label)->toBe('Copyright');
});
