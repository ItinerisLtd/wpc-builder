<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Support;

use Mockery;

/**
 * Builds a fake WP_Customize_Setting for the field UNDER TEST (the third
 * argument WordPress passes to a validate_callback), wired to a fake
 * WP_Customize_Manager that resolves the given sibling condition
 * settings and returns null (SettingLookupResult::NotFound, once
 * resolved by RequiredWhenValidator) for any other id.
 */
final class RequiredWhenSiblingSettings
{
    /**
     * @param array<string, mixed> $siblingValues
     */
    public static function build(array $siblingValues): object
    {
        $manager = Mockery::mock('WP_Customize_Manager');

        foreach ($siblingValues as $id => $value) {
            $sibling = Mockery::mock('WP_Customize_Setting');
            $sibling->shouldReceive('post_value')->andReturnUsing(static fn (mixed $default): mixed => $default);
            $sibling->shouldReceive('value')->andReturn($value);

            $manager->shouldReceive('get_setting')->with($id)->andReturn($sibling);
        }

        $manager->shouldReceive('get_setting')->andReturn(null)->byDefault();

        $setting = Mockery::mock('WP_Customize_Setting');
        $setting->manager = $manager;

        return $setting;
    }
}
