<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Tests\Fixtures\Fields\SetupCountingTestField;

it('runs setup() exactly once across repeated buildSettingArgs()/buildControlArgs() calls', function (): void {
    $field = SetupCountingTestField::make('example');

    $config = new Config();

    $field->buildSettingArgs($config);
    $field->buildControlArgs('sec');
    $field->buildSettingArgs($config);
    $field->buildControlArgs('sec');

    expect($field->setupCalls)->toBe(1);
});
