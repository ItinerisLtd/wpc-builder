<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Controls\Tabs as TabsControl;
use Itineris\WpcBuilder\Fields\Tabs;

/**
 * A Fields\Tabs setting present in a changeset would make WordPress reject
 * the WHOLE save (`transaction_fail`): a null sanitize-callback return is
 * read as invalid, aborting the entire transaction. The fix is to register
 * no setting at all. See Fields\Custom's own test for the same reasoning
 * applied to a different display-only field.
 */
it('registers NO setting for a tabs field, so it cannot poison a save', function (): void {
    $customizer = Mockery::mock('WP_Customize_Manager');

    $customizer->shouldReceive('add_setting')->never();

    $control = null;

    $customizer->shouldReceive('add_control')
        ->once()
        ->with(Mockery::type(TabsControl::class))
        ->andReturnUsing(function (TabsControl $passed) use (&$control): void {
            $control = $passed;
        });

    Tabs::make('header_tabs')
        ->setTabDefinitions(['global' => 'Global'])
        ->setAssignments(['header_global_text' => 'global'])
        ->register($customizer, 'header', new Config());

    expect($control)->toBeInstanceOf(TabsControl::class)
        ->and($control->tabs)->toBe(['global' => 'Global'])
        ->and($control->assignments)->toBe(['header_global_text' => 'global'])
        ->and($control->settings)->toBe([]);
});

it('tells WordPress the tabs control has no settings, explicitly', function (): void {
    $args = Tabs::make('header_tabs')->setTabDefinitions(['global' => 'Global'])->buildControlArgs('header');

    expect($args)->toHaveKey('settings')
        ->and($args['settings'])->toBe([]);
});

it('exposes its tab definitions and assignments as control args', function (): void {
    $args = Tabs::make('header_tabs')
        ->setTabDefinitions(['global' => 'Global', 'landing' => 'Landing'])
        ->setAssignments(['header_landing_buttons' => 'landing'])
        ->buildControlArgs('header');

    expect($args['tabs'])->toBe(['global' => 'Global', 'landing' => 'Landing'])
        ->and($args['assignments'])->toBe(['header_landing_buttons' => 'landing']);
});

it('still writes nothing for a tabs field', function (): void {
    $field = Tabs::make('header_tabs')->setTabDefinitions(['global' => 'Global']);

    expect($field->buildSettingArgs(new Config())['sanitize_callback'])->toBe('__return_null');

    $customizer = Mockery::mock('WP_Customize_Manager');
    $customizer->shouldReceive('add_setting')->never();
    $customizer->shouldReceive('add_control')->once();

    $field->register($customizer, 'header', new Config());
});
