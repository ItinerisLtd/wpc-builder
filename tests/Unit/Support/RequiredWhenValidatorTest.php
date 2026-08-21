<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Support\RequiredWhenValidator;
use Itineris\WpcBuilder\Support\SettingLookupResult;

it('reads the in-flight posted value when the sibling setting was touched this request', function (): void {
    $sibling = Mockery::mock('WP_Customize_Setting');
    $sibling->shouldReceive('post_value')->andReturn('posted value');
    $sibling->shouldNotReceive('value');

    $manager = Mockery::mock('WP_Customize_Manager');
    $manager->shouldReceive('get_setting')->once()->with('sibling')->andReturn($sibling);

    $resolve = RequiredWhenValidator::valueResolver($manager);

    expect($resolve('sibling'))->toBe('posted value');
});

it('falls back to value() when the sibling setting was not touched this request', function (): void {
    $sibling = Mockery::mock('WP_Customize_Setting');
    $sibling->shouldReceive('post_value')->andReturnUsing(static fn (mixed $default): mixed => $default);
    $sibling->shouldReceive('value')->once()->andReturn('stored value');

    $manager = Mockery::mock('WP_Customize_Manager');
    $manager->shouldReceive('get_setting')->with('sibling')->andReturn($sibling);

    $resolve = RequiredWhenValidator::valueResolver($manager);

    expect($resolve('sibling'))->toBe('stored value');
});

it('returns SettingLookupResult::NotFound for a setting id that resolves to nothing', function (): void {
    $manager = Mockery::mock('WP_Customize_Manager');
    $manager->shouldReceive('get_setting')->with('missing')->andReturn(null);

    $resolve = RequiredWhenValidator::valueResolver($manager);

    expect($resolve('missing'))->toBe(SettingLookupResult::NotFound);
});

it('treats a legitimately null posted value as a real answer, not as "untouched"', function (): void {
    $sibling = Mockery::mock('WP_Customize_Setting');
    $sibling->shouldReceive('post_value')->andReturn(null);
    $sibling->shouldNotReceive('value');

    $manager = Mockery::mock('WP_Customize_Manager');
    $manager->shouldReceive('get_setting')->with('sibling')->andReturn($sibling);

    $resolve = RequiredWhenValidator::valueResolver($manager);

    expect($resolve('sibling'))->toBeNull();
});
