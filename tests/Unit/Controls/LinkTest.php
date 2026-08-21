<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use Itineris\WpcBuilder\Controls\Link as LinkControl;

require_once __DIR__ . '/../../Fixtures/Controls/wp-customize-control-double.php';

$renderLink = function (): string {
    Functions\stubTranslationFunctions();
    Functions\when('esc_attr')->alias(
        static fn (string $raw): string => htmlspecialchars($raw, ENT_QUOTES),
    );
    Functions\when('esc_html')->alias(
        static fn (string $raw): string => htmlspecialchars($raw, ENT_QUOTES),
    );

    $control = new LinkControl(null, 'acme_cta', [
        'label' => 'Call to action',
        'settings' => ['default' => (object) ['id' => 'acme_cta']],
    ]);

    ob_start();
    $control->renderContent();

    return (string) preg_replace('/\s+/', ' ', trim((string) ob_get_clean()));
};

it('renders the target checkbox using the shared toggle-switch markup', function () use ($renderLink): void {
    $html = $renderLink();

    preg_match('/<input[^>]*class="wpc-builder-link__target[^>]*>/', $html, $matches);
    $checkboxTag = $matches[0] ?? '';

    expect($html)->toContain('class="wpc-builder-toggle__switch"')
        ->and($html)->toContain('class="wpc-builder-toggle__slider"')
        ->and($html)->toContain('Open in a new tab')
        ->and($checkboxTag)->not->toBe('')
        ->and($checkboxTag)->not->toContain('data-customize-setting-link');
});

it(
    'renders the hidden input without a value attribute, per the linkElements hazard',
    function () use ($renderLink): void {
        $html = $renderLink();

        expect($html)->toContain('type="hidden" data-customize-setting-link="acme_cta"')
        ->and($html)->not->toContain('value=""');
    },
);

it('renders the label', function () use ($renderLink): void {
    $html = $renderLink();

    expect($html)->toContain('Call to action');
});

it('renderFieldsMarkup() returns just the three-input fragment, no label/description/hidden-input', function (): void {
    Functions\stubTranslationFunctions();

    $html = (string) preg_replace('/\s+/', ' ', trim(LinkControl::renderFieldsMarkup()));

    expect($html)->toContain('class="wpc-builder-link__text"')
        ->and($html)->toContain('class="wpc-builder-link__url"')
        ->and($html)->toContain('class="wpc-builder-link__target wpc-builder-toggle__input"')
        ->and($html)->not->toContain('type="hidden"')
        ->and($html)->not->toContain('customize-control-title')
        ->and($html)->not->toContain('customize-control-description');
});

it('renderContent() output starts with renderFieldsMarkup()\'s own output', function () use ($renderLink): void {
    $fromRenderContent = $renderLink();
    $fragment = (string) preg_replace('/\s+/', ' ', trim(LinkControl::renderFieldsMarkup()));

    expect($fromRenderContent)->toContain($fragment);
});

it(
    'renderFieldsMarkup() has no trailing whitespace for a caller\'s own markup to concatenate against',
    function (): void {
        Functions\stubTranslationFunctions();

        $fragment = LinkControl::renderFieldsMarkup();

        expect($fragment)->toBe(rtrim($fragment))
            ->and($fragment)->toEndWith('</div>');
    },
);

it(
    'renderContent() concatenates renderFieldsMarkup() with no stray whitespace between them, byte for byte',
    function (): void {
        Functions\stubTranslationFunctions();
        Functions\when('esc_attr')->alias(
            static fn (string $raw): string => htmlspecialchars($raw, ENT_QUOTES),
        );
        Functions\when('esc_html')->alias(
            static fn (string $raw): string => htmlspecialchars($raw, ENT_QUOTES),
        );

        $control = new LinkControl(null, 'acme_cta', [
            'settings' => ['default' => (object) ['id' => 'acme_cta']],
        ]);

        ob_start();
        $control->renderContent();
        $raw = (string) ob_get_clean();

        // No label/description set above, so renderContent()'s entire output is
        // renderFieldsMarkup() immediately followed by the hidden input. The expected string
        // below is a fixed literal, not built from renderFieldsMarkup(), so this assertion
        // can't pass merely because both sides share a common, possibly-buggy source.
        $expected = '        <div class="wpc-builder-link">' . "\n"
            . '            <label class="wpc-builder-link__row">' . "\n"
            . '                <span class="wpc-builder-link__label">Link text</span>' . "\n"
            . '                <input type="text" class="wpc-builder-link__text">' . "\n"
            . '            </label>' . "\n"
            . '            <label class="wpc-builder-link__row">' . "\n"
            . '                <span class="wpc-builder-link__label">URL</span>' . "\n"
            . '                <input type="url" class="wpc-builder-link__url">' . "\n"
            . '            </label>' . "\n"
            . '            <label class="wpc-builder-link__row wpc-builder-link__row--checkbox">' . "\n"
            . '                <span class="wpc-builder-toggle__switch">' . "\n"
            . '                    <input type="checkbox" class="wpc-builder-link__target wpc-builder-toggle__input"'
            . ' autocomplete="off">' . "\n"
            . '                    <span class="wpc-builder-toggle__slider"></span>' . "\n"
            . '                </span>' . "\n"
            . '                <span class="wpc-builder-link__label">Open in a new tab</span>' . "\n"
            . '            </label>' . "\n"
            . '        </div>'
            . '        <input type="hidden" data-customize-setting-link="acme_cta">' . "\n        ";

        expect($raw)->toBe($expected);
    },
);

it('link assets include its own style, the toggle style, and the url-validation dependency', function (): void {
    Functions\when('content_url')->justReturn('https://example.test/wp-content');

    $assets = LinkControl::assets();

    $script = null;
    $styleHandles = [];

    foreach ($assets as $asset) {
        if ('script' === ($asset['type'] ?? null)) {
            $script = $asset;
        }

        if ('style' === ($asset['type'] ?? null)) {
            $styleHandles[] = $asset['handle'] ?? null;
        }
    }

    expect($script)->not->toBeNull()
        ->and($script['handle'])->toBe('wpc-builder-link')
        ->and($script['dependencies'] ?? null)->toEqual([
            'customize-controls',
            'wpc-builder-url-validation-core',
        ])
        ->and($styleHandles)->toContain('wpc-builder-link')
        ->and($styleHandles)->toContain('wpc-builder-toggle')
        ->and($styleHandles)->toContain('wpc-builder');
});
