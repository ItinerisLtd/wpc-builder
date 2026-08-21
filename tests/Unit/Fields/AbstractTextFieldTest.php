<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Fields\Text;

it('merges setPlaceholder() and setMaxLength() into input_attrs', function (): void {
    $args = Text::make('example')
        ->setPlaceholder('e.g. 01234')
        ->setMaxLength(20)
        ->buildControlArgs('sec');

    expect($args['input_attrs'])->toBe([
        'placeholder' => 'e.g. 01234',
        'maxlength' => 20,
    ]);
});

it('lets setPlaceholder() override an explicit setInputAttrs() placeholder', function (): void {
    $args = Text::make('example')
        ->setInputAttrs(['placeholder' => 'old'])
        ->setPlaceholder('ph')
        ->buildControlArgs('sec');

    expect($args['input_attrs'])->toBe(['placeholder' => 'ph']);
});

it('keeps unrelated setInputAttrs() keys alongside setPlaceholder()', function (): void {
    $args = Text::make('example')
        ->setInputAttrs(['data-foo' => 'bar'])
        ->setPlaceholder('ph')
        ->buildControlArgs('sec');

    expect($args['input_attrs'])->toBe([
        'data-foo' => 'bar',
        'placeholder' => 'ph',
    ]);
});

it('preserves an explicit setInputAttrs() placeholder when setPlaceholder() is never called', function (): void {
    $args = Text::make('example')
        ->setInputAttrs(['placeholder' => 'old'])
        ->buildControlArgs('sec');

    expect($args['input_attrs'])->toBe(['placeholder' => 'old']);
});
