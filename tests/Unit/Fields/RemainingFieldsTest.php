<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\Color;
use Itineris\WpcBuilder\Fields\ColorPalette;
use Itineris\WpcBuilder\Fields\Custom;
use Itineris\WpcBuilder\Fields\Dimensions;
use Itineris\WpcBuilder\Fields\RadioButtonset;
use Itineris\WpcBuilder\Fields\Slider;

it('sanitizes slider values as floats', function (): void {
    $sanitize = Slider::make('per_page')->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize('12.5abc'))->toBe('12.5');
});

it('sanitizes dimensions as text', function (): void {
    $args = Dimensions::make('spacing')->buildSettingArgs(new Config());

    expect($args['sanitize_callback'])->toBe('sanitize_text_field');
});

it('passes slider bounds through as control choices', function (): void {
    $args = Slider::make('per_page')
        ->setMin(1)
        ->setMax(100)
        ->setStep(1)
        ->buildControlArgs('footer');

    expect($args['choices'])->toBe(['min' => 1, 'max' => 100, 'step' => 1]);
});

it('uses the radio-buttonset control type but Radio\'s known-choice sanitize behaviour', function (): void {
    $sanitize = RadioButtonset::make('align')
        ->setChoices(['left' => 'Left', 'right' => 'Right'])
        ->setDefaultValue('left')
        ->buildSettingArgs(new Config())['sanitize_callback'];

    $args = RadioButtonset::make('align')
        ->setChoices(['left' => 'Left', 'right' => 'Right'])
        ->buildControlArgs('footer');

    expect($sanitize('right'))->toBe('right')
        ->and($sanitize('centre'))->toBe('left')
        ->and($args['type'])->toBe('wpc-builder-radio-buttonset');
});

it('defaults a color to an alpha-capable regex, not to hex-only', function (): void {
    $sanitize = Color::make('accent')->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize)->not->toBe('sanitize_hex_color')
        ->and($sanitize('RGBA(255, 0, 0, 0.5)'))->toBe('rgba(255, 0, 0, 0.5)')
        ->and($sanitize('#AABBCC'))->toBe('#aabbcc')
        ->and($sanitize('#ABC'))->toBe('#abc')
        ->and($sanitize('not-a-color'))->toBe('')
        ->and($sanitize(['r' => 1]))->toBe('');
});

it('never returns null from the default color sanitizer, so a save is never silently aborted', function (): void {
    $sanitize = Color::make('accent')->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize('rgba(0, 0, 0, 0.4)'))->toBe('rgba(0, 0, 0, 0.4)')
        ->and($sanitize('hsl(10, 50%, 50%)'))->toBe('hsl(10, 50%, 50%)')
        ->and($sanitize('#aabbccdd'))->toBe('#aabbccdd')
        ->and($sanitize('garbage'))->not->toBeNull();
});

it('narrows a color to hex-only on explicit opt-in', function (): void {
    $args = Color::make('accent')->setAlpha(false)->buildSettingArgs(new Config());

    expect($args['sanitize_callback'])->toBe('sanitize_hex_color');
});

it('sanitizes color palette values with the color-palette regex, empty string when no match', function (): void {
    $sanitize = ColorPalette::make('accent')->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize('#ff0000'))->toBe('#ff0000')
        ->and($sanitize('rgba(255, 255, 0, 0.9)'))->toBe('rgba(255, 255, 0, 0.9)')
        ->and($sanitize('var(--wp--preset--color--primary)'))->toBe('var(--wp--preset--color--primary)')
        ->and($sanitize('not-a-color'))->toBe('');
});

it('accepts every hue from 0 to 360 in the hsl, hsv and hsva formats', function (): void {
    $sanitize = ColorPalette::make('accent')->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize('hsl(200, 50%, 50%)'))->toBe('hsl(200, 50%, 50%)')
        ->and($sanitize('hsv(150, 50%, 50%)'))->toBe('hsv(150, 50%, 50%)')
        ->and($sanitize('hsva(250, 50%, 50%, 0.5)'))->toBe('hsva(250, 50%, 50%, 0.5)')
        ->and($sanitize('hsl(20, 50%, 50%)'))->toBe('hsl(20, 50%, 50%)')
        ->and($sanitize('hsl(350, 50%, 50%)'))->toBe('hsl(350, 50%, 50%)')
        ->and($sanitize('hsl(400, 50%, 50%)'))->toBe('');
});

it('passes color palette swatches through as control choices', function (): void {
    $args = ColorPalette::make('accent')->setColors(['#ff0000', '#00ff00'])->buildControlArgs('footer');

    expect($args['choices'])->toBe(['colors' => ['#ff0000', '#00ff00']]);
});

it('passes dimensions choices through to the control like other choice fields', function (): void {
    $args = Dimensions::make('spacing')->setChoices(['px' => 'Pixels'])->buildControlArgs('footer');

    expect($args['choices'])->toBe(['px' => 'Pixels']);
});

it('passes custom html through to the control args, unsanitized', function (): void {
    $args = Custom::make('notice')->setHtml('<p>Hello</p>')->buildControlArgs('footer');

    expect($args['html'])->toBe('<p>Hello</p>');
});
