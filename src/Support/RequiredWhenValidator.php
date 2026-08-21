<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Support;

use stdClass;
use WP_Customize_Manager;

/**
 * Builds the value resolver ConditionEvaluator::passes() needs to read
 * sibling settings during server-side requiredWhen validation.
 */
final class RequiredWhenValidator
{
    /**
     * Reads post_value(), not value(): during a changeset save it carries
     * the in-flight submitted value while value() still holds the
     * pre-save one, and evaluating a sibling condition against a stale
     * value would let a same-request save slip through. post_value()
     * returns its $default_value argument on failure, so a fresh stdClass
     * sentinel (never equal to any real posted value) distinguishes
     * "sibling untouched this request" from "sibling explicitly posted as
     * null" before falling back to value().
     */
    public static function valueResolver(WP_Customize_Manager $manager): callable
    {
        return static function (string $settingId) use ($manager): mixed {
            $setting = $manager->get_setting($settingId);

            if (null === $setting) {
                return SettingLookupResult::NotFound;
            }

            $sentinel = new stdClass();
            $posted = $setting->post_value($sentinel);

            return $sentinel === $posted ? $setting->value() : $posted;
        };
    }
}
