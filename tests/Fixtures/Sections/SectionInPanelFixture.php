<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Sections;

use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Sections\AbstractSection;

/**
 * Names a panel via $panel, used to prove Customizer::register() registers
 * panels before sections: WordPress silently drops a section naming a panel
 * that does not yet exist, so this ordering is functionally significant,
 * not cosmetic.
 */
final class SectionInPanelFixture extends AbstractSection
{
    protected string $id = 'footer_social';
    protected ?string $title = 'Social';
    protected ?string $panel = 'footer_panel';

    protected function fields(): array
    {
        return [Text::make('footer_social_url')->setLabel('URL')];
    }
}
