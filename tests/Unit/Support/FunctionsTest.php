<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Enums\OptionType;
use Itineris\WpcBuilder\Fields\Text;

use function Itineris\WpcBuilder\Support\build_partial_refresh_args;
use function Itineris\WpcBuilder\Support\collect_required_when_conditions;
use function Itineris\WpcBuilder\Support\collect_visible_when_conditions;
use function Itineris\WpcBuilder\Support\editor_id_from_setting_id;
use function Itineris\WpcBuilder\Support\format_setting_id;
use function Itineris\WpcBuilder\Support\is_valid_or_empty_url;
use function Itineris\WpcBuilder\Support\label_from_id;

it('converts a snake_case id into sentence case', function (): void {
    expect(label_from_id('brand_phone'))->toBe('Brand phone');
});

it('converts a kebab-case id into sentence case', function (): void {
    expect(label_from_id('brand-phone'))->toBe('Brand phone');
});

it('capitalises a single-word id', function (): void {
    expect(label_from_id('phone'))->toBe('Phone');
});

it('does not capitalise words after the first', function (): void {
    expect(label_from_id('footer_logo_text'))->toBe('Footer logo text');
});

it('trims a leading separator rather than leaving a stray space', function (): void {
    expect(label_from_id('_brand_phone'))->toBe('Brand phone');
});

it('collapses a doubled separator into a single space', function (): void {
    expect(label_from_id('brand__phone'))->toBe('Brand phone');
});

it('collects visible-when conditions keyed by formatted setting id', function (): void {
    $fields = [
        Text::make('alert_text')->setVisibleWhen([
            ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
        ]),
        Text::make('plain'),
    ];

    expect(collect_visible_when_conditions($fields, new Config()))->toBe([
        'alert_text' => [
            ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
        ],
    ]);
});

it('formats keys for option storage', function (): void {
    $config = new Config(optionType: OptionType::OPTION, optionName: 'my_theme');

    $fields = [
        Text::make('alert_text')->setVisibleWhen([
            ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
        ]),
    ];

    expect(collect_visible_when_conditions($fields, $config))
        ->toHaveKey('my_theme[alert_text]');
});

it(
    'does not reformat a condition\'s inner setting id under option storage',
    function (): void {
        $config = new Config(optionType: OptionType::OPTION, optionName: 'my_theme');

        $fields = [
            Text::make('alert_text')->setVisibleWhen([
                ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
            ]),
        ];

        expect(collect_visible_when_conditions($fields, $config))->toBe([
            'my_theme[alert_text]' => [
                ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
            ],
        ]);
    },
);

it('collects required-when conditions keyed by formatted setting id', function (): void {
    $fields = [
        Text::make('alert_text')->setRequiredWhen([
            ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
        ]),
        Text::make('plain'),
    ];

    expect(collect_required_when_conditions($fields, new Config()))->toBe([
        'alert_text' => [
            ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
        ],
    ]);
});

it('strips characters a TinyMCE id cannot carry', function (): void {
    expect(editor_id_from_setting_id('acme_theme[footer_text]'))
        ->toStartWith('wpc_builder_editor_acmethemefootertext_')
        ->and(editor_id_from_setting_id('acme_theme[footer_text]'))->toMatch('/^[a-z0-9_]+$/');
});

it('gives colliding slugs distinct ids', function (): void {
    expect(editor_id_from_setting_id('footer_text'))
        ->not->toBe(editor_id_from_setting_id('footertext'));

    expect(editor_id_from_setting_id('acme[logo]'))
        ->not->toBe(editor_id_from_setting_id('acmelogo'));
});

it('is stable across calls, so the id does not change between requests', function (): void {
    expect(editor_id_from_setting_id('footer_text'))->toBe(editor_id_from_setting_id('footer_text'));
});

it('still produces a usable id when the setting id has no alphanumerics at all', function (): void {
    expect(editor_id_from_setting_id('[]'))->toMatch('/^wpc_builder_editor__[a-f0-9]+$/');
});

it('builds the add_partial() args, omitting container_inclusive when not given', function (): void {
    $callback = static fn (): string => 'rendered';

    $args = build_partial_refresh_args('#footer .copyright', $callback, null);

    expect($args)->toBe([
        'selector' => '#footer .copyright',
        'render_callback' => $callback,
    ]);
});

it('includes an explicit container_inclusive flag, even when false', function (): void {
    $callback = static fn (): string => 'rendered';

    $args = build_partial_refresh_args('#footer .copyright', $callback, false);

    expect($args)->toBe([
        'selector' => '#footer .copyright',
        'render_callback' => $callback,
        'container_inclusive' => false,
    ]);
});

it('returns the raw id for theme_mod storage', function (): void {
    $config = new Config(optionType: OptionType::THEME_MOD, optionName: 'ignored');

    expect(format_setting_id('brand_logo', $config))->toBe('brand_logo');
});

it('wraps the id in the option name for option storage', function (): void {
    $config = new Config(optionType: OptionType::OPTION, optionName: 'my_theme');

    expect(format_setting_id('brand_logo', $config))->toBe('my_theme[brand_logo]');
});

it('returns the raw id for option storage without an option name', function (): void {
    $config = new Config(optionType: OptionType::OPTION, optionName: '');

    expect(format_setting_id('brand_logo', $config))->toBe('brand_logo');
});

it('leaves an id that already contains brackets untouched', function (): void {
    $config = new Config(optionType: OptionType::OPTION, optionName: 'my_theme');

    expect(format_setting_id('my_theme[brand_logo]', $config))->toBe('my_theme[brand_logo]');
});

describe('is_valid_or_empty_url()', function (): void {
    beforeEach(function (): void {
        Functions\when('wp_allowed_protocols')->justReturn([
            'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'ircs', 'gopher',
            'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'sms', 'svn', 'tel', 'fax',
            'xmpp', 'webcal', 'urn',
        ]);
    });

    it('accepts real-world URL shapes', function (string $url): void {
        expect(is_valid_or_empty_url($url))->toBeTrue();
    })->with([
        'absolute' => 'https://example.test/path',
        'uppercase scheme' => 'HTTPS://EXAMPLE.TEST/PATH',
        'relative path' => '/contact',
        'fragment' => '#footer',
        'query' => '?s=query',
        'protocol-relative' => '//cdn.example.test/x',
        'schemeless host' => 'example.test/foo',
        'tel' => 'tel:+441234567890',
        'sms' => 'sms:+441234567890',
        'mailto' => 'mailto:hello@example.test',
        'IDN host' => 'https://exämple.test/path',
    ]);

    it('accepts empty-ish values', function (mixed $value): void {
        expect(is_valid_or_empty_url($value))->toBeTrue();
    })->with([
        'null' => [null],
        'empty string' => '',
        'whitespace only' => '   ',
    ]);

    it('rejects whitespace-containing garbage and disallowed schemes', function (string $url): void {
        expect(is_valid_or_empty_url($url))->toBeFalse();
    })->with([
        'plain words' => 'not a url',
        'absolute with space' => 'https://example.test/a b',
        'javascript scheme' => 'javascript:alert(1)',
        'data scheme' => 'data:text/html,x',
    ]);

    it('rejects a non-scalar value', function (): void {
        expect(is_valid_or_empty_url(['https://example.test']))->toBeFalse();
    });
});
