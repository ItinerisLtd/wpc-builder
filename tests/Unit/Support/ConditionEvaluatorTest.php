<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Support\ConditionEvaluator;
use Itineris\WpcBuilder\Support\SettingLookupResult;

describe('evaluate()', function (): void {
    it('handles strict and loose equality', function (): void {
        expect(ConditionEvaluator::evaluate('1', '1', '==='))->toBeTrue()
            ->and(ConditionEvaluator::evaluate('1', 1, '==='))->toBeFalse()
            ->and(ConditionEvaluator::evaluate('1', 1, '=='))->toBeTrue()
            ->and(ConditionEvaluator::evaluate('1', 1, 'equals'))->toBeTrue();
    });

    it('handles negation', function (): void {
        expect(ConditionEvaluator::evaluate('1', 1, '!=='))->toBeTrue()
            ->and(ConditionEvaluator::evaluate('1', 2, '!='))->toBeTrue()
            ->and(ConditionEvaluator::evaluate('1', 1, 'not equal'))->toBeFalse();
    });

    it('compares actual against expected for ordering operators', function (): void {
        expect(ConditionEvaluator::evaluate(5, 10, '>'))->toBeTrue()
            ->and(ConditionEvaluator::evaluate(5, 5, '>='))->toBeTrue()
            ->and(ConditionEvaluator::evaluate(5, 1, '<'))->toBeTrue()
            ->and(ConditionEvaluator::evaluate(5, 5, '<='))->toBeTrue();
    });

    it('handles containment', function (): void {
        expect(ConditionEvaluator::evaluate('b', ['a', 'b'], 'in'))->toBeTrue()
            ->and(ConditionEvaluator::evaluate('c', ['a', 'b'], 'contains'))->toBeFalse()
            ->and(ConditionEvaluator::evaluate('c', ['a', 'b'], 'not in'))->toBeTrue();
    });

    it('indexes into an array value with choice', function (): void {
        expect(ConditionEvaluator::evaluate('x', ['top' => 'x'], '==', 'top'))->toBeTrue();
    });

    it('falls back to loose equality for an unknown operator', function (): void {
        expect(ConditionEvaluator::evaluate('1', 1, 'wat'))->toBeTrue();
    });

    it('indexes into a list value with choice, not just an associative array', function (): void {
        expect(ConditionEvaluator::evaluate('a', ['a', 'b'], '===', '0'))->toBeTrue()
            ->and(ConditionEvaluator::evaluate('b', ['a', 'b'], '===', '0'))->toBeFalse();
    });

    it(
        'falls back to loose equality inside contains()/"in" when no array/object/string branch matches',
        function (): void {
            expect(ConditionEvaluator::evaluate(5, 5, 'in'))->toBeTrue()
                ->and(ConditionEvaluator::evaluate(5, 6, 'in'))->toBeFalse();
        },
    );

    it(
        'gates the contains() string branch on the ACTUAL operand\'s type, not the expected operand\'s',
        function (): void {
            expect(ConditionEvaluator::evaluate(5, 'abc', 'in'))->toBeFalse()
                ->and(ConditionEvaluator::evaluate(['a', 'b', 'c'], 'b', 'in'))->toBeTrue();
        },
    );

    it('does not double-apply choice indexing for "not in"/"does not contain"', function (): void {
        expect(ConditionEvaluator::evaluate('a', ['top' => ['a', 'b']], 'not in', 'top'))->toBeFalse();
    });

    it(
        'treats an integer 0 choice as falsy and skips indexing, matching evaluate.js\'s `choice &&` guard',
        function (): void {
            expect(ConditionEvaluator::evaluate('a', ['a', 'b'], '===', 0))->toBeFalse();
        },
    );

    it('still indexes with a string "0" choice, since JS only treats the empty string as falsy', function (): void {
        expect(ConditionEvaluator::evaluate('x', ['0' => 'x'], '==', '0'))->toBeTrue();
    });
});

describe('passes()', function (): void {
    $get = static fn (string $setting): mixed => ['enabled' => true, 'mode' => 'advanced'][$setting] ?? null;

    it('ANDs conditions at the top level', function () use ($get): void {
        expect(ConditionEvaluator::passes([
            ['setting' => 'enabled', 'operator' => '==', 'value' => true],
            ['setting' => 'mode', 'operator' => '==', 'value' => 'advanced'],
        ], $get))->toBeTrue();

        expect(ConditionEvaluator::passes([
            ['setting' => 'enabled', 'operator' => '==', 'value' => true],
            ['setting' => 'mode', 'operator' => '==', 'value' => 'basic'],
        ], $get))->toBeFalse();
    });

    it('ORs one level down', function () use ($get): void {
        expect(ConditionEvaluator::passes([
            [
                ['setting' => 'mode', 'operator' => '==', 'value' => 'basic'],
                ['setting' => 'mode', 'operator' => '==', 'value' => 'advanced'],
            ],
        ], $get))->toBeTrue();
    });

    it('passes when there are no conditions', function () use ($get): void {
        expect(ConditionEvaluator::passes([], $get))->toBeTrue();
    });

    it('fails OPEN (passes) when the value resolver reports SettingLookupResult::NotFound', function (): void {
        $getWithMissing = static fn (string $setting): mixed => 'known' === $setting
            ? true
            : SettingLookupResult::NotFound;

        expect(ConditionEvaluator::passes([
            ['setting' => 'unknown', 'operator' => '==', 'value' => false],
        ], $getWithMissing))->toBeTrue();

        expect(ConditionEvaluator::passes([
            ['setting' => 'unknown', 'operator' => '==', 'value' => false],
            ['setting' => 'known', 'operator' => '==', 'value' => false],
        ], $getWithMissing))->toBeFalse();
    });
});
