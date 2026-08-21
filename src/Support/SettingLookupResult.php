<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Support;

/**
 * Sentinel returned by a ConditionEvaluator::passes() value resolver when
 * a condition row's `setting` id names a setting that doesn't exist (a
 * renamed/dropped field, or the wrong id under option storage). Distinct
 * from `null`, which is a legitimate stored value.
 */
enum SettingLookupResult
{
    case NotFound;
}
