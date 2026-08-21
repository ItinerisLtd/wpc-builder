<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use Itineris\WpcBuilder\Controls\Repeater as RepeaterControl;

require_once __DIR__ . '/../../Fixtures/Controls/wp-customize-control-double.php';

$renderRepeater = function (array $args): string {
    Functions\stubTranslationFunctions();
    Functions\when('esc_html')->alias(
        static fn (string $raw): string => htmlspecialchars($raw, ENT_QUOTES),
    );

    $control = new RepeaterControl(null, 'acme_repeater', $args);

    ob_start();
    $control->renderContent();

    return (string) preg_replace('/\s+/', ' ', trim((string) ob_get_clean()));
};

it('labels the add button from button_label', function () use ($renderRepeater): void {
    $html = $renderRepeater(['button_label' => 'Add a link']);

    expect($html)->toContain('wpc-builder-repeater__add"> Add a link <');
});

it('falls back to "Add row" when no button label is set', function () use ($renderRepeater): void {
    $html = $renderRepeater([]);

    expect($html)->toContain('wpc-builder-repeater__add"> Add row <');
});

it('falls back to "Add row" for an empty label, not a blank button', function () use ($renderRepeater): void {
    $html = $renderRepeater(['button_label' => '']);

    expect($html)->toContain('wpc-builder-repeater__add"> Add row <');
});

it('escapes a button label containing markup', function () use ($renderRepeater): void {
    $html = $renderRepeater(['button_label' => '<em>Add</em>']);

    expect($html)->toContain('&lt;em&gt;Add&lt;/em&gt;')
        ->and($html)->not->toContain('<em>');
});
