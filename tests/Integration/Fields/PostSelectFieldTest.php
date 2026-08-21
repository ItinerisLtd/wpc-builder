<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\PostSelect;

test('a PostSelect field queries real published pages for its choices and persists a valid id', function (): void {
    wpc_builder_log_in_as_admin();

    $postId = wpc_builder_create_test_page();

    $wpCustomize = wpc_builder_footer_customizer();

    $field = PostSelect::make('featured_page')
        ->setLabel('Featured page')
        ->setPostType('page');

    $field->register($wpCustomize, 'footer', new Config());

    expect($field->choices())->toHaveKey((string) $postId, get_the_title($postId));

    $wpCustomize->set_post_value('featured_page', (string) $postId);
    $wpCustomize->get_setting('featured_page')->save();

    expect(get_theme_mod('featured_page'))->toBe((string) $postId);
});
