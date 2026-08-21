<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Sections;

use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Sections\AbstractSection;

/**
 * Mirrors extending a WordPress core section (e.g. "title_tagline"):
 * register() auto-detects the existing section and skips add_section(),
 * but the field below still must be registered against that core section.
 */
final class CoreSectionFixture extends AbstractSection
{
    protected string $id = 'title_tagline';

    protected function fields(): array
    {
        return [Text::make('site_icon_note')->setLabel('Note')];
    }
}
