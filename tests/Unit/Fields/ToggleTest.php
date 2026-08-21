<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Fields\CheckboxToggle;

it('uses the custom toggle control type', function (): void {
    $args = CheckboxToggle::make('show_footer')->buildControlArgs('footer');

    expect($args['type'])->toBe('wpc-builder-toggle');
});
