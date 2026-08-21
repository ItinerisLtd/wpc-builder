<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Sections;

use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Sections\AbstractSection;

final class NoTitleSectionFixture extends AbstractSection
{
    protected string $id = 'brand_settings';

    protected function fields(): array
    {
        return [Text::make('brand_phone')->setLabel('Phone')];
    }
}
