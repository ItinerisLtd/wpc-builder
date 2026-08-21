<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Sections;

use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Sections\AbstractSection;

/**
 * A section with one field declaring visibleWhen() conditions and one that
 * doesn't, used to exercise Customizer's dependency payload collection
 * (see Support\collect_visible_when_conditions()) without disturbing
 * FooterSectionFixture, which other tests assume carries no conditions.
 */
final class VisibleWhenFieldSectionFixture extends AbstractSection
{
    protected string $id = 'conditional';
    protected ?string $title = 'Conditional';

    protected function fields(): array
    {
        return [
            Text::make('alert_text')->setVisibleWhen([
                ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
            ]),
            Text::make('plain'),
        ];
    }
}
