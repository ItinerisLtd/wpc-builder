<?php

declare(strict_types=1);

use Brain\Monkey\Actions;
use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Controls\Image as ImageControl;
use Itineris\WpcBuilder\Enums\SaveAs;
use Itineris\WpcBuilder\Fields\Image;

it('reports wpc-builder-image as the control type, so core does not hijack the control', function (): void {
    $args = Image::make('logo')->buildControlArgs('footer');

    expect($args['type'])->toBe('wpc-builder-image');
});

it('keeps controlType() at image, because the repeater matches on that exact string', function (): void {
    expect(Image::make('logo')->controlType())->toBe('image');
});

it('passes save_as through to the control so it knows which shape to write', function (): void {
    expect(Image::make('logo')->buildControlArgs('footer')['save_as'])->toBe('id')
        ->and(
            Image::make('logo')
                ->setSaveAs(SaveAs::URL)
                ->buildControlArgs('footer')['save_as'],
        )->toBe('url');
});

it('uses this package\'s own image control, not core\'s', function (): void {
    $control = (new ReflectionClassConstant(Image::class, 'CONTROL'))->getValue();

    expect($control)->toBe(ImageControl::class);
});

it('registers an Image field against the customizer manager', function (): void {
    $customizer = Mockery::mock('WP_Customize_Manager');
    $customizer->shouldIgnoreMissing();
    $customizer->selective_refresh = null;

    expect(fn (): mixed => Image::make('logo')->register($customizer, 'image_section', new Config()))
        ->not->toThrow(Throwable::class);
});

it('does not enqueue the media modal for a field merely built, never registered', function (): void {
    Actions\expectAdded('customize_controls_enqueue_scripts')->never();

    expect(Image::make('logo')->buildControlArgs('footer'))
        ->toHaveKey('type');
});
