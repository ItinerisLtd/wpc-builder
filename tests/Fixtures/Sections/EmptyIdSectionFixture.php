<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Sections;

use Itineris\WpcBuilder\Sections\AbstractSection;

/**
 * Deliberately leaves $id at its default empty string, to prove register()
 * throws rather than silently registering an unidentified section.
 */
final class EmptyIdSectionFixture extends AbstractSection
{
    protected function fields(): array
    {
        return [];
    }
}
