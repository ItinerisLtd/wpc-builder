<?php

declare(strict_types=1);

require_once __DIR__ . '/../../Fixtures/Controls/wp-customize-control-double.php';

use Itineris\WpcBuilder\Tests\Fixtures\Controls\MinimalControlFixture;

/**
 * WP core's own `WP_Customize_Control::to_json()` does not populate
 * `$this->json['value']` (see Controls\AbstractControl::to_json()'s own
 * docblock for the citation from wp-includes/class-wp-customize-control.php), so
 * `AbstractControl::to_json()` sets it itself. Every control in this
 * package relies on `control.params.value` being populated client-side,
 * which comes directly from this key.
 */
it('sets $this->json[\'value\'] from $this->value(), which WP core\'s own to_json() does not', function (): void {
    $control = new MinimalControlFixture(null, 'acme_setting', ['stubbedValue' => 'the stored value']);

    $control->to_json();

    expect($control->json)->toHaveKey('value')
        ->and($control->json['value'])->toBe('the stored value');
});

it('copies an arg onto a DECLARED property only, matching core\'s constructor', function (): void {
    $control = new MinimalControlFixture(null, 'acme_setting', [
        'label' => 'Declared, so it lands',
        'no_such_property' => 'undeclared, so it is dropped',
    ]);

    expect($control->label)->toBe('Declared, so it lands')
        ->and(property_exists($control, 'no_such_property'))->toBeFalse()
        ->and($control->id)->toBe('acme_setting');
});
