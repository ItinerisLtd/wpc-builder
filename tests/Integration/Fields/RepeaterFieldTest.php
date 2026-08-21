<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\Repeater;
use Itineris\WpcBuilder\Fields\Select;
use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Fields\Url;

test('a Repeater field sanitizes each sub-field via its real callback and persists to theme_mod', function (): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    Repeater::make('testimonials')
        ->setLabel('Testimonials')
        ->setFields([
            Text::make('title'),
            Select::make('layout')->setChoices(['grid' => 'Grid', 'list' => 'List']),
            Url::make('link'),
        ])
        ->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting('testimonials');

    $rows = [
        [
            'title' => '<b>Row 1</b>',
            'layout' => 'grid',
            'link' => 'https://example.test',
            'unknown_subfield' => 'dropped',
        ],
    ];

    expect($setting->sanitize($rows))->toBe([
        [
            'title' => 'Row 1',
            'layout' => 'grid',
            'link' => 'https://example.test',
        ],
    ]);

    $wpCustomize->set_post_value('testimonials', $rows);
    $setting->save();

    expect(get_theme_mod('testimonials'))->toBe([
        [
            'title' => 'Row 1',
            'layout' => 'grid',
            'link' => 'https://example.test',
        ],
    ]);
});
