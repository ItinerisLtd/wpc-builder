<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Sections;

use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Sections\AbstractSection;

/**
 * A section with one field declaring requiredWhen() conditions and one that
 * doesn't, used to exercise Customizer's required-when payload collection
 * (see Support\collect_required_when_conditions()), mirroring
 * VisibleWhenFieldSectionFixture for the visibility engine.
 */
final class RequiredWhenFieldSectionFixture extends AbstractSection
{
    protected string $id = 'conditionally_required';
    protected ?string $title = 'Conditionally required';

    protected function fields(): array
    {
        return [
            Text::make('alert_text')->setRequiredWhen([
                ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
            ]),
            Text::make('plain'),
        ];
    }
}
