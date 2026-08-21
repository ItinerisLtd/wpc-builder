<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Fields\Text;

it('records partial refresh arguments', function (): void {
    $field = Text::make('footer_copyright')->setPartialRefresh(
        selector: '#footer .copyright',
        renderCallback: static fn (): string => 'rendered',
    );

    $partial = $field->partialRefresh();

    expect($partial['selector'])->toBe('#footer .copyright')
        ->and(($partial['render_callback'])())->toBe('rendered')
        ->and($partial)->not->toHaveKey('container_inclusive');
});

it('has no partial refresh by default', function (): void {
    expect(Text::make('footer_copyright')->partialRefresh())->toBeNull();
});
