<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\Editor;
use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Fields\Textarea;

dataset('wp_kses_post family', [
    'Text' => [Text::class, 'kses_family_text'],
    'Editor' => [Editor::class, 'kses_family_editor'],
    'Textarea' => [Textarea::class, 'kses_family_textarea'],
]);

test('each wp_kses_post-sanitized field strips disallowed markup and persists to theme_mod', function (
    string $class,
    string $id
): void {
    wpc_builder_log_in_as_admin();

    $wpCustomize = wpc_builder_footer_customizer();

    $class::make($id)->setLabel('Body')->register($wpCustomize, 'footer', new Config());

    $setting = $wpCustomize->get_setting($id);

    expect($setting->sanitize('<script>alert(1)</script><p>Hello</p>'))->toBe('alert(1)<p>Hello</p>');

    $wpCustomize->set_post_value($id, '<p>Hello</p>');
    $setting->save();

    expect(get_theme_mod($id))->toBe('<p>Hello</p>');
})->with('wp_kses_post family');
