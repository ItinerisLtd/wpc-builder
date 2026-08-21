<?php

declare(strict_types=1);

it('autoloads the package namespace', function (): void {
    expect(class_exists(\Itineris\WpcBuilder\Enums\Transport::class))->toBeTrue();
});
