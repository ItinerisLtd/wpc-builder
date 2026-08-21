<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Controls\Custom as CustomControl;
use Itineris\WpcBuilder\Fields\Custom;
use Itineris\WpcBuilder\Fields\Text;

/**
 * A Fields\Custom setting present in a changeset made WordPress reject
 * the WHOLE save (`transaction_fail`): a null sanitize-callback return
 * is read as invalid, aborting the entire transaction. The fix is to
 * register no setting at all. See Fields\Custom's own docblock.
 */
it('registers NO setting for a custom field, so it cannot poison a save', function (): void {
    $customizer = Mockery::mock('WP_Customize_Manager');

    $customizer->shouldReceive('add_setting')->never();

    $control = null;

    $customizer->shouldReceive('add_control')
        ->once()
        ->with(Mockery::type(CustomControl::class))
        ->andReturnUsing(function (CustomControl $passed) use (&$control): void {
            $control = $passed;
        });

    Custom::make('notice')->setHtml('<p>Hello</p>')->register($customizer, 'footer', new Config());

    expect($control)->toBeInstanceOf(CustomControl::class)
        ->and($control->html)->toBe('<p>Hello</p>')
        ->and($control->settings)->toBe([]);
});

it('tells WordPress the custom control has no settings, explicitly', function (): void {
    $args = Custom::make('notice')->setHtml('<p>Hello</p>')->buildControlArgs('footer');

    expect($args)->toHaveKey('settings')
        ->and($args['settings'])->toBe([]);
});

it('leaves every other field type registering its setting as before', function (): void {
    $args = Text::make('footer_copyright')->buildControlArgs('footer');

    expect($args)->not->toHaveKey('settings');

    $customizer = Mockery::mock('WP_Customize_Manager');
    $customizer->shouldReceive('add_setting')->once();
    $customizer->shouldReceive('add_control')->once();

    Text::make('footer_copyright')->register($customizer, 'footer', new Config());
});

it('still writes nothing for a custom field', function (): void {
    $field = Custom::make('notice')->setHtml('<p>Hello</p>')->setDefaultValue('ignored');

    expect($field->buildSettingArgs(new Config())['sanitize_callback'])->toBe('__return_null');

    $customizer = Mockery::mock('WP_Customize_Manager');
    $customizer->shouldReceive('add_setting')->never();
    $customizer->shouldReceive('add_control')->once();

    $field->register($customizer, 'footer', new Config());
});

it('does not register a selective-refresh partial for a settings-less field', function (): void {
    $selectiveRefresh = Mockery::mock('WP_Customize_Selective_Refresh');
    $selectiveRefresh->shouldReceive('add_partial')->never();

    $customizer = Mockery::mock('WP_Customize_Manager');
    $customizer->selective_refresh = $selectiveRefresh;
    $customizer->shouldReceive('add_setting')->never();
    $customizer->shouldReceive('add_control')->once();

    $field = Custom::make('notice')
        ->setHtml('<p>Hello</p>')
        ->setPartialRefresh('.target', static fn (): string => 'x');

    expect(fn (): mixed => $field->register($customizer, 'footer', new Config()))
        ->not->toThrow(Throwable::class)
        ->and($field->partialRefresh())->not->toBeNull();
});
