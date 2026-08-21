<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Sections;

use Itineris\WpcBuilder\Fields\Toggle;
use Itineris\WpcBuilder\Sections\AbstractSection;

/**
 * A section whose only field needs one of this package's own controls, so a
 * test can assert Customizer derives exactly that control's assets and no
 * others, the conditional-asset guarantee.
 */
final class ToggleSectionFixture extends AbstractSection
{
    protected string $id = 'toggle_section';
    protected ?string $title = 'Toggle';

    protected function fields(): array
    {
        return [Toggle::make('alert_enabled')];
    }
}
