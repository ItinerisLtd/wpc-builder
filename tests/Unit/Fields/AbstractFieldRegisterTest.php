<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Enums\OptionType;
use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Tests\Fixtures\Fields\AfterRegisterCountingTestField;
use Itineris\WpcBuilder\Tests\Fixtures\Fields\ForcedRefreshTransportFieldFixture;
use Itineris\WpcBuilder\Tests\Fixtures\Fields\RegisterTestField;

it('registers the exact args buildSettingArgs() and buildControlArgs() produced, unmodified', function (): void {
    $field = RegisterTestField::make('example');
    $field->setDefaultValue(false);

    $config = new Config();
    $expectedSettingArgs = $field->buildSettingArgs($config);
    $expectedControlArgs = $field->buildControlArgs('sec');

    expect($expectedSettingArgs)->toHaveKey('default', false)
        ->and($expectedSettingArgs)->toHaveKey('sanitize_js_callback', 'absint')
        ->and($expectedControlArgs)->toHaveKey('choices', ['a' => 'A', 'b' => 'B']);

    $customizer = Mockery::mock('WP_Customize_Manager');

    $customizer->shouldReceive('add_setting')
        ->once()
        ->with('example', $expectedSettingArgs);

    $customizer->shouldReceive('add_control')
        ->once()
        ->with('example', $expectedControlArgs);

    $field->register($customizer, 'sec', $config);
});

it('calls afterRegister() once, via register(), never via buildSettingArgs()/buildControlArgs()', function (): void {
    $field = AfterRegisterCountingTestField::make('example');
    $config = new Config();

    $field->buildSettingArgs($config);
    $field->buildControlArgs('sec');

    expect($field->afterRegisterCalls)->toBe(0);

    $customizer = Mockery::mock('WP_Customize_Manager');
    $customizer->shouldReceive('add_setting');
    $customizer->shouldReceive('add_control');

    $field->register($customizer, 'sec', $config);

    expect($field->afterRegisterCalls)->toBe(1);

    $field->register($customizer, 'sec', $config);

    expect($field->afterRegisterCalls)->toBe(1);
});

it('adds a partial to selective refresh when the field declares one and the component is loaded', function (): void {
    $renderCallback = static fn (): string => 'rendered';
    $field = Text::make('example')->setPartialRefresh(
        selector: '#footer .copyright',
        renderCallback: $renderCallback,
    );
    $expectedPartial = ['selector' => '#footer .copyright', 'render_callback' => $renderCallback];

    $config = new Config();

    $addPartialCalled = false;

    $selectiveRefresh = Mockery::mock('WP_Customize_Selective_Refresh');
    $selectiveRefresh->shouldReceive('add_partial')
        ->once()
        ->with('example', $expectedPartial)
        ->andReturnUsing(function () use (&$addPartialCalled): void {
            $addPartialCalled = true;
        });

    $customizer = Mockery::mock('WP_Customize_Manager');
    $customizer->selective_refresh = $selectiveRefresh;
    $customizer->shouldReceive('add_setting');
    $customizer->shouldReceive('add_control');

    $field->register($customizer, 'sec', $config);

    expect($addPartialCalled)->toBeTrue();
});

it('adds the partial under the FORMATTED setting id under option storage, not the bare field id', function (): void {
    $field = Text::make('example')->setPartialRefresh(
        selector: '#footer .copyright',
        renderCallback: static fn (): string => 'rendered',
    );

    $config = new Config(optionType: OptionType::OPTION, optionName: 'acme_theme');

    $addPartialId = null;

    $selectiveRefresh = Mockery::mock('WP_Customize_Selective_Refresh');
    $selectiveRefresh->shouldReceive('add_partial')
        ->once()
        ->with(Mockery::on(function (string $id) use (&$addPartialId): bool {
            $addPartialId = $id;

            return true;
        }), Mockery::any());

    $customizer = Mockery::mock('WP_Customize_Manager');
    $customizer->selective_refresh = $selectiveRefresh;
    $customizer->shouldReceive('add_setting');
    $customizer->shouldReceive('add_control');

    $field->register($customizer, 'sec', $config);

    expect($addPartialId)->toBe('acme_theme[example]');
});

it(
    'never registers a partial whose final transport is not postMessage, even when a partial was configured',
    function (): void {
        $field = ForcedRefreshTransportFieldFixture::make('example')->setPartialRefresh(
            selector: '#footer .repeater',
            renderCallback: static fn (): string => 'rendered',
        );

        $config = new Config();

        expect($field->buildSettingArgs($config)['transport'])->toBe('refresh');

        $selectiveRefresh = Mockery::mock('WP_Customize_Selective_Refresh');
        $selectiveRefresh->shouldNotReceive('add_partial');

        $customizer = Mockery::mock('WP_Customize_Manager');
        $customizer->selective_refresh = $selectiveRefresh;
        $customizer->shouldReceive('add_setting');
        $customizer->shouldReceive('add_control');

        $field->register($customizer, 'sec', $config);
    },
);

it('does not add a partial when the manager has no selective refresh component loaded', function (): void {
    $field = Text::make('example')->setPartialRefresh(
        selector: '#footer .copyright',
        renderCallback: static fn (): string => 'rendered',
    );

    $config = new Config();

    $customizer = Mockery::mock('WP_Customize_Manager');
    $customizer->selective_refresh = null;
    $customizer->shouldReceive('add_setting');
    $customizer->shouldReceive('add_control');

    $field->register($customizer, 'sec', $config);
})->throwsNoExceptions();

it('never touches selective refresh when the field declares no partial', function (): void {
    $field = RegisterTestField::make('example');

    $config = new Config();

    expect($field->partialRefresh())->toBeNull();

    $selectiveRefresh = Mockery::mock('WP_Customize_Selective_Refresh');
    $selectiveRefresh->shouldNotReceive('add_partial');

    $customizer = Mockery::mock('WP_Customize_Manager');
    $customizer->selective_refresh = $selectiveRefresh;
    $customizer->shouldReceive('add_setting');
    $customizer->shouldReceive('add_control');

    $field->register($customizer, 'sec', $config);
});
