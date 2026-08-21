<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Enums\SaveAs;
use Itineris\WpcBuilder\Fields\Image;

test('a SaveAs::ID Image field sanitizes a real attachment URL back to its id and persists it', function (): void {
    wpc_builder_log_in_as_admin();

    $attachmentId = wpc_builder_create_test_attachment(DIR_TESTDATA . '/images/canola.jpg');
    $attachmentUrl = wp_get_attachment_url($attachmentId);

    $wpCustomize = wpc_builder_footer_customizer();

    Image::make('logo')
        ->setLabel('Logo')
        ->setSaveAs(SaveAs::ID)
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('logo');

    // attachment_url_to_postid() only resolves a URL that really exists.
    expect($setting->sanitize($attachmentUrl))->toBe($attachmentId);

    $wpCustomize->set_post_value('logo', (string) $attachmentId);
    $setting->save();

    expect(get_theme_mod('logo'))->toBe($attachmentId);
});
