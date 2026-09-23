<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Sections;

use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Sections\AbstractSection;

final class TabsSectionFixture extends AbstractSection
{
    protected string $id = 'header';
    protected ?string $title = 'Header';

    /**
     * @return array<string, string>
     */
    protected function tabs(): array
    {
        return [
            'global' => 'Global',
            'landing' => 'Landing',
        ];
    }

    protected function fields(): array
    {
        return [
            Text::make('header_global_text')->setTab('global'),
            Text::make('header_landing_text')->setTab('landing'),
            Text::make('header_untabbed_text'),
            Text::make('header_unknown_tab_text')->setTab('does_not_exist'),
        ];
    }
}
