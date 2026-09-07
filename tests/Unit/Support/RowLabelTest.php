<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Support\RowLabel;

it('builds a text row label', function (): void {
    expect(RowLabel::text('Team member')->toArray())
        ->toBe(['type' => 'text', 'value' => 'Team member']);
});

it('builds a field row label with no custom label', function (): void {
    expect(RowLabel::fromField('title')->toArray())
        ->toBe(['type' => 'field', 'value' => '', 'field' => 'title']);
});

it('builds a field row label with a custom label for when the field is blank', function (): void {
    expect(RowLabel::fromField('title', 'Untitled')->toArray())
        ->toBe(['type' => 'field', 'value' => 'Untitled', 'field' => 'title']);
});
