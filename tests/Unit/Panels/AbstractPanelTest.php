<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Tests\Fixtures\Panels\EmptyIdPanelFixture;
use Itineris\WpcBuilder\Tests\Fixtures\Panels\FooterPanelFixture;
use Itineris\WpcBuilder\Tests\Fixtures\Panels\NoTitlePanelFixture;

it('builds panel args', function (): void {
    expect((new FooterPanelFixture())->buildPanelArgs())->toBe([
        'title' => 'Footer',
        'description' => 'Footer panel',
        'priority' => 160,
    ]);
});

it('defaults the title from the panel id when none is set', function (): void {
    expect((new NoTitlePanelFixture())->buildPanelArgs())->toBe([
        'title' => 'Brand settings panel',
        'priority' => 160,
    ]);
});

it('exposes its id', function (): void {
    expect((new FooterPanelFixture())->id())->toBe('footer_panel');
});

it('registers itself against the customizer', function (): void {
    $panel = new FooterPanelFixture();
    $customizer = Mockery::mock('WP_Customize_Manager');

    $customizer->shouldReceive('add_panel')
        ->once()
        ->with('footer_panel', $panel->buildPanelArgs());

    expect(fn (): mixed => $panel->register($customizer))->not->toThrow(Throwable::class);
});

it('is idempotent: a second register() is a no-op', function (): void {
    $panel = new FooterPanelFixture();
    $customizer = Mockery::mock('WP_Customize_Manager');

    $customizer->shouldReceive('add_panel')->once();

    $panel->register($customizer);

    expect(fn (): mixed => $panel->register($customizer))->not->toThrow(Throwable::class);
});

it('throws when registering a panel with no id', function (): void {
    $panel = new EmptyIdPanelFixture();
    $customizer = Mockery::mock('WP_Customize_Manager');

    expect(fn (): mixed => $panel->register($customizer))
        ->toThrow(InvalidArgumentException::class, 'must declare a non-empty $id');
});
