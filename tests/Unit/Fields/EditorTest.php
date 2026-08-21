<?php

declare(strict_types=1);

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Itineris\WpcBuilder\Fields\Editor;

it('conditionally hooks TinyMCE/Quicktags enqueueing, only once this field is actually registered', function (): void {
    Actions\expectAdded('customize_controls_enqueue_scripts')->once()->with('wp_enqueue_editor');
    Actions\expectAdded('customize_controls_enqueue_scripts')->once()->with('wp_enqueue_media');

    $capturedCallback = null;
    $capturedPriority = null;

    Actions\expectAdded('customize_controls_print_footer_scripts')->once()->whenHappen(
        function (callable $callback, int $priority) use (&$capturedCallback, &$capturedPriority): void {
            $capturedCallback = $callback;
            $capturedPriority = $priority;
        },
    );

    $field = Editor::make('body_content');

    expect(fn (): mixed => (new ReflectionMethod($field, 'afterRegister'))->invoke($field))
        ->not->toThrow(Throwable::class);

    expect($capturedCallback)->toBeCallable()
        ->and($capturedPriority)->toBe(20);
});

it('prints the editor default scripts at most once, even after widgets already printed them', function (): void {
    $editors = Mockery::mock('alias:_WP_Editors');
    $editors->shouldReceive('print_default_editor_scripts')->once();

    $capturedCallback = null;

    Actions\expectAdded('customize_controls_enqueue_scripts');
    Actions\expectAdded('customize_controls_print_footer_scripts')->once()->whenHappen(
        function (callable $callback) use (&$capturedCallback): void {
            $capturedCallback = $callback;
        },
    );

    $field = Editor::make('body_content');
    (new ReflectionMethod($field, 'afterRegister'))->invoke($field);

    expect($capturedCallback)->toBeCallable();

    Functions\when('did_action')->justReturn(0);
    $capturedCallback();

    Functions\when('did_action')->justReturn(1);
    $capturedCallback();
});
