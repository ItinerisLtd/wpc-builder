<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Panels;

use Itineris\WpcBuilder\Panels\AbstractPanel;

final class FooterPanelFixture extends AbstractPanel
{
    protected string $id = 'footer_panel';
    protected ?string $title = 'Footer';
    protected ?string $description = 'Footer panel';
    protected int $priority = 160;
}
