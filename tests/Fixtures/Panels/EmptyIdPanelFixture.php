<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Panels;

use Itineris\WpcBuilder\Panels\AbstractPanel;

/**
 * Deliberately leaves $id at its default empty string, to prove register()
 * throws rather than silently registering an unidentified panel.
 */
final class EmptyIdPanelFixture extends AbstractPanel
{
}
