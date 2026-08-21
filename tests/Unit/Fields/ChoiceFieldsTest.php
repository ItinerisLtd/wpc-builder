<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Controls\Select as SelectControl;
use Itineris\WpcBuilder\Fields\Checkbox;
use Itineris\WpcBuilder\Fields\DropdownPages;
use Itineris\WpcBuilder\Fields\Multicheck;
use Itineris\WpcBuilder\Fields\Radio;
use Itineris\WpcBuilder\Fields\Select;

it('sanitizes a single select as text', function (): void {
    $sanitize = Select::make('layout')
        ->setChoices(['a' => 'A', 'b' => 'B'])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize)->toBe('sanitize_text_field');
});

it('caps a multiple select at the max selection number', function (): void {
    Brain\Monkey\Functions\when('sanitize_text_field')->returnArg();

    $sanitize = Select::make('layout')
        ->setMultiple(true)
        ->setMaxSelectionNumber(2)
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize(['a', 'b', 'c']))->toBe(['a', 'b']);
});

it('treats a null multiple-select value as an empty array', function (): void {
    Brain\Monkey\Functions\when('sanitize_text_field')->returnArg();

    $sanitize = Select::make('layout')
        ->setMultiple(true)
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize(null))->toBe([])
        ->and($sanitize([]))->toBe([]);
});

it('preserves string keys in a multiple select value', function (): void {
    Brain\Monkey\Functions\when('sanitize_text_field')->returnArg();

    $sanitize = Select::make('layout')
        ->setMultiple(true)
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize(['x' => 'a', 'y' => 'b']))->toBe(['x' => 'a', 'y' => 'b']);
});

it('defaults the multiple-select cap to 999', function (): void {
    Brain\Monkey\Functions\when('sanitize_text_field')->returnArg();

    $sanitize = Select::make('layout')
        ->setMultiple(true)
        ->buildSettingArgs(new Config())['sanitize_callback'];

    $values = array_fill(0, 150, 'a');

    expect($sanitize($values))->toHaveCount(150);
});

it('falls back to the default when a radio value is not a known choice', function (): void {
    $sanitize = Radio::make('align')
        ->setChoices(['left' => 'Left', 'right' => 'Right'])
        ->setDefaultValue('left')
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize('right'))->toBe('right')
        ->and($sanitize('centre'))->toBe('left');
});

it('falls back to an empty string when a radio has no default', function (): void {
    $sanitize = Radio::make('align')
        ->setChoices(['left' => 'Left'])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize('centre'))->toBe('');
});

it('sanitizes multicheck values into an array', function (): void {
    Brain\Monkey\Functions\when('sanitize_text_field')->returnArg();

    $sanitize = Multicheck::make('features')->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize(['a', 'b']))->toBe(['a', 'b'])
        ->and($sanitize([]))->toBe([])
        ->and($sanitize('a,b'))->toBe(['a', 'b'])
        ->and($sanitize(''))->toBe([])
        ->and($sanitize(null))->toBe([])
        ->and($sanitize(new stdClass()))->toBe([]);
});

it('stores checkbox values as booleans', function (): void {
    $sanitize = Checkbox::make('enabled')->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize('0'))->toBeFalse()
        ->and($sanitize('false'))->toBeFalse()
        ->and($sanitize(''))->toBeFalse()
        ->and($sanitize('1'))->toBeTrue()
        ->and($sanitize(true))->toBeTrue();
});

it('coerces checkbox defaults to booleans', function (): void {
    $args = Checkbox::make('enabled')->setDefaultValue('1')->buildSettingArgs(new Config());

    expect($args['default'])->toBeTrue();
});

it('uses the dropdown-pages control type', function (): void {
    $args = DropdownPages::make('page')->buildControlArgs('footer');

    expect($args['type'])->toBe('dropdown-pages');
});

it('passes multiple as a first-class control arg, not through input_attrs', function (): void {
    $args = Select::make('pages')
        ->setChoices(['1' => 'Home'])
        ->setMultiple()
        ->buildControlArgs('footer');

    expect($args['multiple'])->toBeTrue()
        ->and($args)->not->toHaveKey('input_attrs');
});

it('omits the multiple control arg for a single select', function (): void {
    $args = Select::make('layout')
        ->setChoices(['a' => 'A'])
        ->buildControlArgs('footer');

    expect($args)->not->toHaveKey('multiple');
});

it('keeps setMultiple(false) a genuine single select', function (): void {
    $field = Select::make('layout')->setChoices(['a' => 'A'])->setMultiple(false);

    expect($field->isMultiple())->toBeFalse()
        ->and($field->buildControlArgs('footer'))->not->toHaveKey('multiple')
        ->and($field->buildSettingArgs(new Config())['sanitize_callback'])->toBe('sanitize_text_field');
});

it('renders through this package\'s own select control, not core\'s built-in branch', function (): void {
    $control = (new ReflectionClassConstant(Select::class, 'CONTROL'))->getValue();

    expect($control)->toBe(SelectControl::class)
        ->and(Select::make('layout')->controlType())->toBe('select');
});
