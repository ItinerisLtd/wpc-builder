<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\Custom;

test('a Custom field registers no setting, so it can never abort a real changeset save', function (): void {
    $wpCustomize = wpc_builder_footer_customizer();

    Custom::make('footer_notice')
        ->setHtml('<p>Read-only notice.</p>')
        ->register($wpCustomize, 'footer', new Config());

    expect($wpCustomize->get_setting('footer_notice'))->toBeNull()
        ->and($wpCustomize->get_control('footer_notice'))->toBeInstanceOf(WP_Customize_Control::class);
});
