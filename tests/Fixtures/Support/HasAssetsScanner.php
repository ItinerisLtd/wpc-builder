<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Support;

use function array_map;
use function explode;
use function preg_match;
use function preg_match_all;
use function strrpos;
use function substr;
use function trim;

use const PREG_SET_ORDER;

/**
 * Statically detects which classes declared in a chunk of PHP source
 * implement HasAssets, without loading/declaring them: merely
 * autoloading a control class fatals in this test suite, which has no
 * WordPress bootstrap.
 *
 * Unlike a single-line "class X extends AbstractControl implements
 * HasAssets" regex: it doesn't require AbstractControl specifically as
 * the parent (matching ControlAssetRegistrar::register()'s own
 * is_subclass_of(HasAssets::class) check); it parses the full
 * `implements` clause in either order; and it anchors the class
 * keyword to the start of a trimmed line, so a commented-out or
 * docblock-only mention of "implements HasAssets" is correctly
 * rejected.
 */
final class HasAssetsScanner
{
    /**
     * @return array<int, string>
     */
    public static function classNames(string $source): array
    {
        $classNames = [];

        $classPattern = '/^\s*(?:final\s+)?(?:abstract\s+)?class\s+(\w+)\b(.*?)\{/ms';

        preg_match_all($classPattern, $source, $classMatches, PREG_SET_ORDER);

        foreach ($classMatches as $classMatch) {
            [, $className, $header] = $classMatch;

            if (1 !== preg_match('/\bimplements\b(.*)/s', $header, $implementsMatch)) {
                continue;
            }

            if (self::implementsHasAssets($implementsMatch[1])) {
                $classNames[] = $className;
            }
        }

        return $classNames;
    }

    private static function implementsHasAssets(string $implementsClause): bool
    {
        $interfaces = array_map(
            static fn (string $interface): string => trim($interface),
            explode(',', $implementsClause),
        );

        foreach ($interfaces as $interface) {
            $separatorPosition = strrpos($interface, '\\');
            $shortName = false === $separatorPosition ? $interface : substr($interface, $separatorPosition + 1);

            if ('HasAssets' === $shortName) {
                return true;
            }
        }

        return false;
    }
}
