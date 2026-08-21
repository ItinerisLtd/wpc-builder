<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Fields\Text;

it('builds control args', function (): void {
    $args = Text::make('brand_phone')
        ->setLabel('Brand phone')
        ->setDescription('Shown in the footer')
        ->setPriority(20)
        ->setInputAttrs(['placeholder' => '01234'])
        ->buildControlArgs('footer');

    expect($args)->toBe([
        'section' => 'footer',
        'type' => 'text',
        'label' => 'Brand phone',
        'description' => 'Shown in the footer',
        'priority' => 20,
        'input_attrs' => ['placeholder' => '01234'],
    ]);
});

it('defaults label from id and omits other optional args that were never set', function (): void {
    $args = Text::make('brand_phone')->buildControlArgs('footer');

    expect($args)->toBe([
        'section' => 'footer',
        'type' => 'text',
        'label' => 'Brand phone',
        'priority' => 10,
    ]);
});
