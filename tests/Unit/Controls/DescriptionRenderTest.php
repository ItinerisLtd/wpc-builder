<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use Itineris\WpcBuilder\Controls\ColorPalette;
use Itineris\WpcBuilder\Controls\Custom;
use Itineris\WpcBuilder\Controls\Dimensions;
use Itineris\WpcBuilder\Controls\Editor;
use Itineris\WpcBuilder\Controls\Image;
use Itineris\WpcBuilder\Controls\Link;
use Itineris\WpcBuilder\Controls\Multicheck;
use Itineris\WpcBuilder\Controls\RadioButtonset;
use Itineris\WpcBuilder\Controls\Repeater;
use Itineris\WpcBuilder\Controls\Select;
use Itineris\WpcBuilder\Controls\Slider;
use Itineris\WpcBuilder\Controls\Toggle;

require_once __DIR__ . '/../../Fixtures/Controls/wp-customize-control-double.php';

$render = function (string $controlClass): string {
    Functions\stubTranslationFunctions();
    Functions\when('esc_attr')->alias(
        static fn (string $raw): string => htmlspecialchars($raw, ENT_QUOTES),
    );
    Functions\when('esc_html')->alias(
        static fn (string $raw): string => htmlspecialchars($raw, ENT_QUOTES),
    );
    Functions\when('esc_textarea')->alias(
        static fn (string $raw): string => htmlspecialchars($raw, ENT_QUOTES),
    );
    Functions\when('esc_url')->returnArg();
    Functions\when('wp_kses_post')->returnArg();
    Functions\when('checked')->justReturn(null);
    Functions\when('selected')->justReturn(null);

    $control = new $controlClass(null, 'acme_field', [
        'label' => 'Label',
        'description' => 'See the <a href="https://example.test">docs</a>.',
        'choices' => ['min' => 0, 'max' => 10, 'step' => 1],
        'settings' => ['default' => (object) ['id' => 'acme_field']],
    ]);

    ob_start();
    $control->renderContent();

    return (string) ob_get_clean();
};

it('renders a control description as HTML, not escaped text', function (string $controlClass) use ($render): void {
    expect($render($controlClass))->toContain('<a href="https://example.test">docs</a>');
})->with([
    ColorPalette::class,
    Custom::class,
    Dimensions::class,
    Editor::class,
    Image::class,
    Link::class,
    Multicheck::class,
    RadioButtonset::class,
    Repeater::class,
    Select::class,
    Slider::class,
    Toggle::class,
]);
