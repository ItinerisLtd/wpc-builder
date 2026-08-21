<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Enums\OptionType;
use Itineris\WpcBuilder\Enums\Transport;
use Itineris\WpcBuilder\Fields\Text;

it('builds setting args with the expected defaults', function (): void {
    $args = Text::make('brand_phone')
        ->setDefaultValue('01234')
        ->buildSettingArgs(new Config());

    expect($args)->toBe([
        'type' => 'theme_mod',
        'capability' => 'edit_theme_options',
        'default' => '01234',
        'transport' => 'refresh',
        'sanitize_callback' => 'wp_kses_post',
    ]);
});

it('carries the option type and capability from config', function (): void {
    $config = new Config(
        capability: 'manage_options',
        optionType: OptionType::OPTION,
        optionName: 'my_theme',
    );

    $args = Text::make('brand_phone')->buildSettingArgs($config);

    expect($args['type'])->toBe('option')
        ->and($args['capability'])->toBe('manage_options');
});

it('lets a per-field capability override the config', function (): void {
    $args = Text::make('brand_phone')
        ->setCapability('manage_options')
        ->buildSettingArgs(new Config());

    expect($args['capability'])->toBe('manage_options');
});

it('lets a per-field option type override the config', function (): void {
    $args = Text::make('brand_phone')
        ->setOptionType(OptionType::OPTION)
        ->buildSettingArgs(new Config());

    expect($args['type'])->toBe('option');
});

it('lets a per-field option type override an option-storage config back to theme_mod', function (): void {
    $config = new Config(optionType: OptionType::OPTION, optionName: 'my_theme');

    $field = Text::make('brand_phone')->setOptionType(OptionType::THEME_MOD);

    expect($field->buildSettingArgs($config)['type'])->toBe('theme_mod')
        ->and($field->settingId($config))->toBe('brand_phone');
});

it('lets a per-field option name override the config, and reformats the setting id with it', function (): void {
    $config = new Config(optionType: OptionType::OPTION, optionName: 'my_theme');

    $field = Text::make('brand_phone')->setOptionName('legacy_theme');

    expect($field->settingId($config))->toBe('legacy_theme[brand_phone]')
        ->and($field->buildSettingArgs($config)['type'])->toBe('option');
});

it('applies a per-field option name to a field that also opts into option storage', function (): void {
    $field = Text::make('brand_phone')
        ->setOptionType(OptionType::OPTION)
        ->setOptionName('legacy_theme');

    expect($field->settingId(new Config()))->toBe('legacy_theme[brand_phone]');
});

it('leaves the config untouched for a field with no storage override', function (): void {
    $config = new Config(optionType: OptionType::OPTION, optionName: 'my_theme');

    expect(Text::make('brand_phone')->effectiveConfig($config))->toBe($config);
});

it('reports the per-field overrides through effectiveConfig()', function (): void {
    $effective = Text::make('brand_phone')
        ->setCapability('manage_options')
        ->setOptionType(OptionType::OPTION)
        ->setOptionName('legacy_theme')
        ->effectiveConfig(new Config());

    expect($effective->capability)->toBe('manage_options')
        ->and($effective->optionType)->toBe(OptionType::OPTION)
        ->and($effective->optionName)->toBe('legacy_theme');
});

it('lets a per-field sanitize callback override the default', function (): void {
    $args = Text::make('brand_phone')
        ->setSanitizeCallback('absint')
        ->buildSettingArgs(new Config());

    expect($args['sanitize_callback'])->toBe('absint');
});

it('routes an array-form active_callback into visibleWhen', function (): void {
    $conditions = [
        ['setting' => 'scripts_google_fonts_enable', 'operator' => '==', 'value' => true],
    ];

    $field = Text::make('brand_phone')->setActiveCallback($conditions);

    expect($field->visibleWhen())->toBe($conditions)
        ->and($field->buildControlArgs('footer'))->not->toHaveKey('active_callback');
});

it('still treats a genuinely callable array as a callback', function (): void {
    $callable = [new DateTimeImmutable('@0'), 'getTimestamp'];

    $field = Text::make('brand_phone')->setActiveCallback($callable);

    expect($field->visibleWhen())->toBe([])
        ->and($field->buildControlArgs('footer')['active_callback'])->toBe($callable);
});

it('drops conditions when stripping callable entries removes index 0', function (): void {
    $field = Text::make('brand_phone')->setActiveCallback([
        '__return_true',
        ['setting' => 'other', 'operator' => '==', 'value' => true],
    ]);

    expect($field->visibleWhen())->toBe([]);
});

it('keeps the surviving conditions when the callable entry is not at index 0', function (): void {
    $condition = ['setting' => 'other', 'operator' => '==', 'value' => true];

    $field = Text::make('brand_phone')->setActiveCallback([$condition, '__return_true']);

    expect($field->visibleWhen())->toBe([$condition]);
});

it('lets visibleWhen win over a callable active_callback, regardless of call order', function (): void {
    $conditions = [['setting' => 'other', 'operator' => '==', 'value' => true]];

    $callbackFirst = Text::make('a')
        ->setActiveCallback('__return_true')
        ->setVisibleWhen($conditions)
        ->buildControlArgs('footer');

    $visibleWhenFirst = Text::make('b')
        ->setVisibleWhen($conditions)
        ->setActiveCallback('__return_true')
        ->buildControlArgs('footer');

    expect($callbackFirst)->not->toHaveKey('active_callback')
        ->and($visibleWhenFirst)->not->toHaveKey('active_callback');
});

it('uses postMessage transport when asked', function (): void {
    $args = Text::make('brand_phone')
        ->setTransport(Transport::POST_MESSAGE)
        ->buildSettingArgs(new Config());

    expect($args['transport'])->toBe('postMessage');
});

it(
    'forces postMessage transport when a partial refresh is configured, even with no explicit transport',
    function (): void {
        $args = Text::make('footer_copyright')
            ->setPartialRefresh(
                selector: '#footer .copyright',
                renderCallback: static fn (): string => 'rendered',
            )
            ->buildSettingArgs(new Config());

        expect($args['transport'])->toBe('postMessage');
    },
);

it(
    'forces postMessage even when setTransport(REFRESH) was called before setPartialRefresh()',
    function (): void {
        $args = Text::make('footer_copyright')
            ->setTransport(Transport::REFRESH)
            ->setPartialRefresh(
                selector: '#footer .copyright',
                renderCallback: static fn (): string => 'rendered',
            )
            ->buildSettingArgs(new Config());

        expect($args['transport'])->toBe('postMessage');
    },
);

it(
    'forces postMessage even when setTransport(REFRESH) was called after setPartialRefresh(), '
        . 'unlike a plain setter override',
    function (): void {
        $args = Text::make('footer_copyright')
            ->setPartialRefresh(
                selector: '#footer .copyright',
                renderCallback: static fn (): string => 'rendered',
            )
            ->setTransport(Transport::REFRESH)
            ->buildSettingArgs(new Config());

        expect($args['transport'])->toBe('postMessage');
    },
);
