<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use Itineris\WpcBuilder\Controls\Image as ImageControl;
use Itineris\WpcBuilder\Controls\Select as SelectControl;

require_once __DIR__ . '/../../Fixtures/Controls/wp-customize-control-double.php';

/**
 * Regression tests for the single-select markup. `Fields\Select`
 * routes EVERY select through `Controls\Select`, not only
 * multi-selects, so when that control first landed it silently
 * replaced core's own markup on the most common non-text field in the
 * estate: the description was escaped with `esc_html()` instead of
 * rendered as markup (core echoes it raw, and ~2 dozen real field
 * declarations pass an `<a href>`), and the `<select>` had no `id`,
 * so the `<label for>` and `aria-describedby` associations were both
 * gone.
 */
$renderSelect = function (array $args): string {
    Functions\when('esc_attr')->alias(
        static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES),
    );
    Functions\when('esc_html')->alias(
        static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES),
    );
    Functions\when('wp_kses_post')->returnArg();
    Functions\when('selected')->alias(
        static function (mixed $helper, mixed $current, bool $echo = true): string {
            $isSelected = is_scalar($helper) && is_scalar($current)
                && (string) $helper === (string) $current;
            $html = $isSelected ? ' selected="selected"' : '';

            if ($echo) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $html;
            }

            return $html;
        },
    );

    $control = new SelectControl(null, 'acme_select', $args);

    ob_start();
    $control->renderContent();

    return (string) preg_replace('/\s+/', ' ', trim((string) ob_get_clean()));
};

it('associates the label with the select via for and id, like core', function () use ($renderSelect): void {
    $html = $renderSelect([
        'label' => 'Related Stories Block',
        'choices' => ['0' => 'None', '12' => 'Hero'],
    ]);

    expect($html)->toContain('<label')
        ->and($html)->toContain('for="_customize-input-acme_select"')
        ->and($html)->toContain('class="customize-control-title"')
        ->and($html)->toContain('id="_customize-input-acme_select"');
});

it('renders an HTML description as markup, not as escaped entities', function () use ($renderSelect): void {
    $description = 'Reusable blocks can be edit on this link '
        . "<a href='https://example.test/wp-admin/edit.php?post_type=wp_block'>editing page</a>";

    $html = $renderSelect([
        'label' => 'Related Stories Block',
        'description' => $description,
        'choices' => ['0' => 'None'],
    ]);

    expect($html)->toContain('<a href=')
        ->and($html)->toContain('>editing page</a>')
        ->and($html)->not->toContain('&lt;a href=')
        ->and($html)->not->toContain('&lt;/a&gt;');
});

it('points the select at the description with aria-describedby', function () use ($renderSelect): void {
    $html = $renderSelect([
        'label' => 'Related Stories Block',
        'description' => 'Pick a block',
        'choices' => ['0' => 'None'],
    ]);

    expect($html)->toContain('id="_customize-description-acme_select"')
        ->and($html)->toContain('aria-describedby="_customize-description-acme_select"');
});

it('omits aria-describedby when there is no description', function () use ($renderSelect): void {
    $html = $renderSelect(['label' => 'Related Stories Block', 'choices' => ['0' => 'None']]);

    expect($html)->not->toContain('aria-describedby');
});

it('keeps the setting binding on a single select', function () use ($renderSelect): void {
    $html = $renderSelect([
        'label' => 'Related Stories Block',
        'choices' => ['0' => 'None'],
        'settings' => ['default' => (object) ['id' => 'acme_select']],
    ]);

    expect($html)->toContain('data-customize-setting-link="acme_select"');
});

it('adds enhancement marker attributes to select markup', function () use ($renderSelect): void {
    $html = $renderSelect([
        'choices' => ['home' => 'Home', 'about' => 'About'],
        'settings' => ['default' => (object) ['id' => 'acme_select']],
    ]);

    expect($html)->toContain('data-wpc-builder-select-ui="enhance"')
        ->and($html)->toContain('data-wpc-builder-select-setting="acme_select"');
});

it('marks the stored value as the selected option', function () use ($renderSelect): void {
    $html = $renderSelect([
        'choices' => ['0' => 'None', '12' => 'Hero'],
        'stubbedValue' => '12',
    ]);

    expect($html)->toContain('value="12" selected="selected"');
});

it('renders nothing at all when the select has no choices, matching core', function () use ($renderSelect): void {
    expect($renderSelect(['label' => 'Related Stories Block', 'description' => 'x']))->toBe('');
});

it('gives the multiple-mode select the same id, label and aria treatment', function () use ($renderSelect): void {
    $html = $renderSelect([
        'label' => 'Exclude on these pages',
        'description' => 'Pick <em>several</em>',
        'choices' => ['4' => 'About', '9' => 'Contact'],
        'multiple' => true,
        'stubbedValue' => ['9'],
    ]);

    expect($html)->toContain('for="_customize-input-acme_select"')
        ->and($html)->toContain('id="_customize-input-acme_select"')
        ->and($html)->toContain('aria-describedby="_customize-description-acme_select"')
        ->and($html)->toContain('<em>several</em>')
        ->and($html)->toContain('multiple="multiple"')
        ->and($html)->not->toContain('data-customize-setting-link')
        ->and($html)->toContain('data-wpc-builder-select-setting="acme_select"')
        ->and($html)->toContain('value="9" selected="selected"');
});

it('renders an Image control\'s description as markup too', function (): void {
    Functions\stubTranslationFunctions();
    Functions\when('esc_attr')->alias(
        static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES),
    );
    Functions\when('esc_html')->alias(
        static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES),
    );
    Functions\when('esc_url')->returnArg();
    Functions\when('wp_kses_post')->returnArg();

    $control = new ImageControl(null, 'acme_logo', [
        'label' => 'Logo',
        'description' => 'Upload a logo <a href="https://example.test">here</a>',
    ]);

    ob_start();
    $control->renderContent();
    $html = (string) preg_replace('/\s+/', ' ', trim((string) ob_get_clean()));

    expect($html)->toContain('<a href="https://example.test">here</a>')
        ->and($html)->not->toContain('&lt;a href=');
});

it('select script asset has expected dependencies', function (): void {
    Functions\when('content_url')->justReturn('https://example.test/wp-content');

    $assets = SelectControl::assets();

    $script = null;
    foreach ($assets as $asset) {
        if (
            isset($asset['type'], $asset['handle'])
            && 'script' === $asset['type']
            && 'wpc-builder-select' === $asset['handle']
        ) {
            $script = $asset;
            break;
        }
    }

    expect($script)->not->toBeNull();
    expect($script['dependencies'] ?? null)->toEqual([
        'customize-controls',
        'wp-element',
        'wp-components',
        'wp-i18n',
    ]);
});

it('multiple-mode select markup includes enhancement marker attribute', function () use ($renderSelect): void {
    $html = $renderSelect([
        'label' => 'Exclude on these pages',
        'description' => 'Pick <em>several</em>',
        'choices' => ['4' => 'About', '9' => 'Contact'],
        'multiple' => true,
        'stubbedValue' => ['9'],
    ]);

    expect($html)->toContain('data-wpc-builder-select-ui="enhance"');
});
