<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use Itineris\WpcBuilder\Controls\Tabs as TabsControl;

require_once __DIR__ . '/../../Fixtures/Controls/wp-customize-control-double.php';

$renderTabs = function (array $args): string {
    Functions\when('esc_html')->alias(
        static fn (string $raw): string => htmlspecialchars($raw, ENT_QUOTES),
    );
    Functions\when('esc_attr')->alias(
        static fn (string $raw): string => htmlspecialchars($raw, ENT_QUOTES),
    );

    $control = new TabsControl(null, 'header_tabs', $args);

    ob_start();
    $control->renderContent();

    return (string) preg_replace('/\s+/', ' ', trim((string) ob_get_clean()));
};

it('renders one menu item per declared tab, in order', function () use ($renderTabs): void {
    $html = $renderTabs(['tabs' => ['global' => 'Global', 'landing' => 'Landing']]);

    expect($html)->toContain('data-tab-id="global"')
        ->and($html)->toContain('>Global<')
        ->and($html)->toContain('data-tab-id="landing"')
        ->and($html)->toContain('>Landing<')
        ->and(strpos($html, 'global'))->toBeLessThan(strpos($html, 'landing'));
});

it('renders no menu items for an empty tab set', function () use ($renderTabs): void {
    $html = $renderTabs(['tabs' => []]);

    expect($html)->not->toContain('data-tab-id')
        ->and($html)->toContain('wpc-builder-tabs__menu');
});

it('escapes a tab label containing markup', function () use ($renderTabs): void {
    $html = $renderTabs(['tabs' => ['global' => '<em>Global</em>']]);

    expect($html)->toContain('&lt;em&gt;Global&lt;/em&gt;')
        ->and($html)->not->toContain('<em>');
});
