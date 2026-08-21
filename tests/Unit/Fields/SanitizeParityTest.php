<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\DropdownPages;
use Itineris\WpcBuilder\Fields\Editor;
use Itineris\WpcBuilder\Fields\Number;
use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Fields\Textarea;
use Itineris\WpcBuilder\Fields\Url;

dataset('sanitize defaults', [
    'text' => [Text::class, 'wp_kses_post'],
    'textarea' => [Textarea::class, 'wp_kses_post'],
    'editor' => [Editor::class, 'wp_kses_post'],
    'url' => [Url::class, 'wp_kses_post'],
    'dropdown-pages' => [DropdownPages::class, 'sanitize_text_field'],
]);

it('matches the documented sanitize defaults', function (string $class, string $expected): void {
    $args = $class::make('field_id')->buildSettingArgs(new Config());

    expect($args['sanitize_callback'])->toBe($expected);
})->with('sanitize defaults');

it('clamps numbers to the configured range', function (): void {
    $sanitize = Number::make('per_page')
        ->setMin(1)
        ->setMax(100)
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize('250'))->toBe('100')
        ->and($sanitize('0'))->toBe('1')
        ->and($sanitize('42'))->toBe('42');
});

it('does not clamp a number without a configured range', function (): void {
    $sanitize = Number::make('per_page')->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize('250'))->toBe('250');
});

it('keeps Number on its own float filter, not Generic\'s wp_kses_post', function (): void {
    $sanitize = Number::make('per_page')->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize)->not->toBe('wp_kses_post')
        ->and($sanitize)->toBeInstanceOf(Closure::class);
});
