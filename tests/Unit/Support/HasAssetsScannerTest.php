<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Tests\Fixtures\Support\HasAssetsScanner;

it('detects a class implementing HasAssets alongside another interface, in either order', function (): void {
    $trailer = "\n{\n}";
    $first = 'final class Foo extends AbstractControl implements Countable, HasAssets' . $trailer;
    $second = 'final class Foo extends AbstractControl implements HasAssets, Countable' . $trailer;

    expect(HasAssetsScanner::classNames($first))->toBe(['Foo'])
        ->and(HasAssetsScanner::classNames($second))->toBe(['Foo']);
});

it('detects HasAssets under an intermediate parent, not just AbstractControl directly', function (): void {
    $source = "final class Foo extends SomeIntermediateAbstract implements HasAssets\n{\n}";

    expect(HasAssetsScanner::classNames($source))->toBe(['Foo']);
});

it('does not match a commented-out or docblock-only line mentioning implements HasAssets', function (): void {
    $comment = "// final class Foo extends AbstractControl implements HasAssets\n";
    $docblock = " * final class Foo extends AbstractControl implements HasAssets\n";

    expect(HasAssetsScanner::classNames($comment))->toBe([])
        ->and(HasAssetsScanner::classNames($docblock))->toBe([]);
});

it('ignores a class declaration with no implements clause at all', function (): void {
    $source = "abstract class AbstractControl extends WP_Customize_Control\n{\n}";

    expect(HasAssetsScanner::classNames($source))->toBe([]);
});
