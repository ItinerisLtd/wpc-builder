<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Support;

use function array_is_list;
use function array_key_exists;
use function get_object_vars;
use function in_array;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function property_exists;
use function str_contains;

/**
 * A dependency-free PHP port of assets/src/js/evaluate.js: same
 * condition-row shape, same operator table, same AND/OR nesting-flip, same
 * fail-open-on-unresolvable-setting rule. See docs/conditional-visibility.md
 * for the full spec. Exists to run server-side, where evaluate.js can't, for
 * evaluating a field's conditionally-required-value conditions inside a
 * validate_callback.
 */
final class ConditionEvaluator
{
    /**
     * @param array<int, mixed> $conditions
     */
    public static function passes(array $conditions, callable $getValue, string $relation = 'AND'): bool
    {
        if ([] === $conditions) {
            return true;
        }

        $results = [];

        foreach ($conditions as $condition) {
            $results[] = self::evaluateRow($condition, $getValue, $relation);
        }

        return 'OR' === $relation ? in_array(true, $results, true) : ! in_array(false, $results, true);
    }

    public static function evaluate(
        mixed $expected,
        mixed $actual,
        string $operator,
        string|int|null $choice = null
    ): bool {
        // JS truthiness for evaluate.js's own `choice && isIndexable(actual)` guard,
        // narrowed to this parameter's string|int|null domain: 0, '' and null all
        // skip indexing, matching JS's falsy check (a string '0' is JS-truthy, so
        // it still indexes). `isIndexable` in JS covers plain objects as well as
        // arrays, so `$actual` here can be a genuine object (e.g. a stdClass a
        // third-party sanitize_callback returned), not just a PHP array.
        $choiceIsTruthy = null !== $choice && 0 !== $choice && '' !== $choice;

        if ($choiceIsTruthy && is_array($actual)) {
            $actual = $actual[$choice] ?? null;
        } elseif ($choiceIsTruthy && is_object($actual)) {
            $property = (string) $choice;
            $actual = property_exists($actual, $property) ? $actual->{$property} : null;
        }

        return match ($operator) {
            '===' => $expected === $actual,
            // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator, Universal.Operators.StrictComparisons.LooseEqual
            '==', '=', 'equals', 'equal' => $expected == $actual,
            '!==' => $expected !== $actual,
            // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedNotEqualOperator, Universal.Operators.StrictComparisons.LooseNotEqual
            '!=', 'not equal' => $expected != $actual,
            '>=', 'greater or equal', 'equal or greater' => $actual >= $expected,
            '<=', 'smaller or equal', 'equal or smaller' => $actual <= $expected,
            '>', 'greater' => $actual > $expected,
            '<', 'smaller' => $actual < $expected,
            'contains', 'in' => self::contains($expected, $actual),
            'does not contain', 'not in' => ! self::contains($expected, $actual),
            // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator, Universal.Operators.StrictComparisons.LooseEqual
            default => $expected == $actual,
        };
    }

    /**
     * A leaf row is evaluated directly; a nested list of rows recurses
     * with the relation flipped one level. A malformed row (not an array,
     * or missing a string `setting`) fails open, same as an unresolvable
     * setting id.
     */
    private static function evaluateRow(mixed $condition, callable $getValue, string $relation): bool
    {
        if (! is_array($condition)) {
            return true;
        }

        if (array_is_list($condition)) {
            return self::passes($condition, $getValue, 'AND' === $relation ? 'OR' : 'AND');
        }

        $setting = $condition['setting'] ?? null;

        if (! is_string($setting)) {
            return true;
        }

        $value = $getValue($setting);

        if ($value instanceof SettingLookupResult) {
            return true;
        }

        $operator = $condition['operator'] ?? null;
        $choice = $condition['choice'] ?? null;

        return self::evaluate(
            $condition['value'] ?? null,
            $value,
            is_string($operator) ? $operator : '',
            is_string($choice) || is_int($choice) ? $choice : null,
        );
    }

    private static function contains(mixed $expected, mixed $actual): bool
    {
        $expectedIsList = is_array($expected) && array_is_list($expected);
        $actualIsList = is_array($actual) && array_is_list($actual);

        if ($expectedIsList && $actualIsList) {
            foreach ($actual as $item) {
                if (in_array($item, $expected, true)) {
                    return true;
                }
            }

            return false;
        }

        if ($actualIsList) {
            foreach ($actual as $item) {
                // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator, Universal.Operators.StrictComparisons.LooseEqual
                if ($item == $expected) {
                    return true;
                }
            }

            return false;
        }

        if (is_array($actual)) {
            if ((is_string($expected) || is_int($expected)) && array_key_exists($expected, $actual)) {
                return true;
            }

            foreach ($actual as $item) {
                if ($item === $expected) {
                    return true;
                }
            }

            return false;
        }

        if (is_object($actual)) {
            if ((is_string($expected) || is_int($expected)) && property_exists($actual, (string) $expected)) {
                return true;
            }

            foreach (get_object_vars($actual) as $item) {
                if ($item === $expected) {
                    return true;
                }
            }

            return false;
        }

        if (is_string($actual)) {
            if (is_string($expected)) {
                return str_contains($expected, $actual) && str_contains($actual, $expected);
            }

            return is_array($expected) && array_is_list($expected) && in_array($actual, $expected, true);
        }

        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator, Universal.Operators.StrictComparisons.LooseEqual
        return $expected == $actual;
    }
}
