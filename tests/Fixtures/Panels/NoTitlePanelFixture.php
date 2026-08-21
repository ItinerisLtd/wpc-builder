<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Panels;

use Itineris\WpcBuilder\Panels\AbstractPanel;

final class NoTitlePanelFixture extends AbstractPanel
{
    protected string $id = 'brand_settings_panel';
}
