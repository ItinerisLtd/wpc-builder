<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use Itineris\WpcBuilder\Controls\Slider as SliderControl;

require_once __DIR__ . '/../../Fixtures/Controls/wp-customize-control-double.php';

/**
 * Two defects in the slider's markup:
 *
 * 1. There was no numeric readout at all, so an editor could see the
 *    thumb's approximate position and nothing else.
 *
 * 2. With no stored value the control rendered `value=""`, and the HTML
 *    standard's default value for an `input type=range` whose value is not
 *    a valid floating-point number is `min + (max - min) / 2`. So an unset
 *    0-50 slider parked its thumb halfway and read as a deliberate 25.
 *
 * These cover the server-rendered half. The client half, re-applying
 * both after WordPress's own linkElements() resets the range to the
 * setting's (empty) value on embed, is covered by
 * assets/src/js/slider.test.js.
 */
$renderSlider = function (mixed $value, array $choices): string {
    Functions\stubTranslationFunctions();
    Functions\when('esc_attr')->alias(
        static fn (string $raw): string => htmlspecialchars($raw, ENT_QUOTES),
    );
    Functions\when('esc_html')->alias(
        static fn (string $raw): string => htmlspecialchars($raw, ENT_QUOTES),
    );

    $control = new SliderControl(null, 'acme_slider', [
        'label' => 'Posts per page',
        'choices' => $choices,
        'stubbedValue' => $value,
    ]);

    ob_start();
    $control->renderContent();

    return (string) preg_replace('/\s+/', ' ', trim((string) ob_get_clean()));
};

it('renders a numeric readout of the stored value', function () use ($renderSlider): void {
    $html = $renderSlider('35', ['min' => 0, 'max' => 50, 'step' => 5]);

    expect($html)->toContain('<output class="wpc-builder-slider__value">35</output>')
        ->and($html)->toContain('value="35"');
});

it('does not park an unset thumb at the midpoint, and says it is unset', function () use ($renderSlider): void {
    $html = $renderSlider('', ['min' => 0, 'max' => 50, 'step' => 5]);

    expect($html)->not->toContain('value=""')
        ->and($html)->toContain('value="0"')
        ->and($html)->toContain('<output class="wpc-builder-slider__value">not set</output>');
});

it('treats a stored zero as a real value, not as unset', function () use ($renderSlider): void {
    $html = $renderSlider('0', ['min' => 0, 'max' => 50, 'step' => 5]);

    expect($html)->toContain('<output class="wpc-builder-slider__value">0</output>')
        ->and($html)->not->toContain('not set');
});

it('keeps the range bounds and the wrapper hook the JS binds to', function () use ($renderSlider): void {
    $html = $renderSlider('35', ['min' => 5, 'max' => 25, 'step' => 5]);

    expect($html)->toContain('data-wpc-builder-slider')
        ->and($html)->toContain('min="5"')
        ->and($html)->toContain('max="25"')
        ->and($html)->toContain('step="5"')
        ->and($html)->toContain('class="wpc-builder-slider__range"');
});

it('falls back to core\'s own 0-100-by-1 bounds when none are given', function () use ($renderSlider): void {
    $html = $renderSlider('', []);

    expect($html)->toContain('min="0"')
        ->and($html)->toContain('max="100"')
        ->and($html)->toContain('step="1"')
        ->and($html)->toContain('value="0"');
});
