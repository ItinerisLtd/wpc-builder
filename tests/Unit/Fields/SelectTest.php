<?php

declare(strict_types=1);

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Itineris\WpcBuilder\Fields\PostSelect;
use Itineris\WpcBuilder\Fields\Select;

it('hooks one shared wp-components enqueue ahead of the package stylesheet for every select field', function (): void {
    Actions\expectAdded('customize_controls_enqueue_scripts')
        ->twice()
        ->with([Select::class, 'enqueueComponentsStylesheet'], 9);

    foreach ([Select::make('layout'), PostSelect::make('featured')] as $field) {
        expect(fn (): mixed => (new ReflectionMethod($field, 'afterRegister'))->invoke($field))
            ->not->toThrow(Throwable::class);
    }
});

it('enqueues the wp-components stylesheet', function (): void {
    Functions\expect('wp_enqueue_style')->once()->with('wp-components');

    expect(Select::enqueueComponentsStylesheet(...))->not->toThrow(Throwable::class);
});
