<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Sections;

use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Sections\AbstractSection;

final class CountingSectionFixture extends AbstractSection
{
    public static int $fieldsCalls = 0;

    protected string $id = 'counting';

    protected function fields(): array
    {
        ++self::$fieldsCalls;

        return [Text::make('counting_text')->setLabel('Counting')];
    }
}
