<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\Custom;
use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Fields\Url;
use Itineris\WpcBuilder\Tests\Fixtures\Support\RequiredWhenSiblingSettings;

require_once __DIR__ . '/../../Fixtures/wp-error-double.php';

beforeEach(function (): void {
    Functions\stubTranslationFunctions();
});

it('does not add a validate_callback when no requiredWhen conditions are set', function (): void {
    $args = Text::make('brand_phone')->buildSettingArgs(new Config());

    expect($args)->not->toHaveKey('validate_callback');
});

it('keeps requiredWhen independent of visibleWhen', function (): void {
    $conditions = [['setting' => 'alert_enabled', 'operator' => '==', 'value' => true]];

    $field = Text::make('alert_text')->setRequiredWhen($conditions);

    expect($field->requiredWhen())->toBe($conditions)
        ->and($field->visibleWhen())->toBe([]);
});

it('adds a value_required error when conditions pass and the value is blank', function (): void {
    $field = Text::make('alert_text')->setRequiredWhen([
        ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
    ]);

    $validate = $field->buildSettingArgs(new Config())['validate_callback'];
    $setting = RequiredWhenSiblingSettings::build(['alert_enabled' => true]);

    $validity = $validate(true, '', $setting);

    expect($validity)->toBeInstanceOf(WP_Error::class)
        ->and($validity->get_error_codes())->toBe(['value_required']);
});

it('does not add an error when conditions pass but the value is present', function (): void {
    $field = Text::make('alert_text')->setRequiredWhen([
        ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
    ]);

    $validate = $field->buildSettingArgs(new Config())['validate_callback'];
    $setting = RequiredWhenSiblingSettings::build(['alert_enabled' => true]);

    expect($validate(true, 'Site is under maintenance', $setting))->toBeTrue();
});

it('does not add an error when conditions do not pass, even with a blank value', function (): void {
    $field = Text::make('alert_text')->setRequiredWhen([
        ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
    ]);

    $validate = $field->buildSettingArgs(new Config())['validate_callback'];
    $setting = RequiredWhenSiblingSettings::build(['alert_enabled' => false]);

    expect($validate(true, '', $setting))->toBeTrue();
});

it('fails open toward "required" when the sibling setting cannot be resolved', function (): void {
    $field = Text::make('alert_text')->setRequiredWhen([
        ['setting' => 'renamed_or_missing', 'operator' => '==', 'value' => true],
    ]);

    $validate = $field->buildSettingArgs(new Config())['validate_callback'];
    $setting = RequiredWhenSiblingSettings::build([]);

    $validity = $validate(true, '', $setting);

    expect($validity)->toBeInstanceOf(WP_Error::class)
        ->and($validity->get_error_codes())->toBe(['value_required']);
});

it('merges value_required into an existing WP_Error rather than replacing it', function (): void {
    $field = Text::make('alert_text')->setRequiredWhen([
        ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
    ]);

    $validate = $field->buildSettingArgs(new Config())['validate_callback'];
    $setting = RequiredWhenSiblingSettings::build(['alert_enabled' => true]);

    $existing = new WP_Error();
    $existing->add('some_other_error', 'Something else is wrong.');

    $validity = $validate($existing, '', $setting);

    expect($validity)->toBe($existing)
        ->and($validity->get_error_codes())->toBe(['some_other_error', 'value_required']);
});

it('preserves the field\'s own validation error when requiredWhen adds nothing on top', function (): void {
    Brain\Monkey\Functions\when('wp_allowed_protocols')->justReturn(['http', 'https']);

    $field = Url::make('cta_url')->setRequiredWhen([
        ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
    ]);

    $validate = $field->buildSettingArgs(new Config())['validate_callback'];
    $setting = RequiredWhenSiblingSettings::build(['alert_enabled' => true]);

    $validity = $validate(true, 'not a url', $setting);

    expect($validity)->toBeInstanceOf(WP_Error::class)
        ->and($validity->get_error_codes())->toBe(['invalid_url']);
});

it('adds value_required on top of a field whose own validation otherwise passes', function (): void {
    Brain\Monkey\Functions\when('wp_allowed_protocols')->justReturn(['http', 'https']);

    $field = Url::make('cta_url')->setRequiredWhen([
        ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
    ]);

    $validate = $field->buildSettingArgs(new Config())['validate_callback'];
    $setting = RequiredWhenSiblingSettings::build(['alert_enabled' => true]);

    $validity = $validate(true, '', $setting);

    expect($validity)->toBeInstanceOf(WP_Error::class)
        ->and($validity->get_error_codes())->toBe(['value_required']);
});

it('never applies requiredWhen to a field that registers no setting', function (): void {
    $args = Custom::make('marketing_banner')
        ->setRequiredWhen([['setting' => 'anything', 'operator' => '==', 'value' => true]])
        ->buildSettingArgs(new Config());

    expect($args)->not->toHaveKey('validate_callback');
});
