<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Sections;

use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Sections\AbstractSection;

final class FooterSectionFixture extends AbstractSection
{
    protected string $id = 'footer';
    protected ?string $title = 'Footer';
    protected ?string $description = 'Footer settings';
    protected int $priority = 160;

    protected function fields(): array
    {
        return [Text::make('footer_copyright')->setLabel('Copyright')];
    }
}
