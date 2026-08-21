<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Fields;

use Itineris\WpcBuilder\Enums\Transport;
use Itineris\WpcBuilder\Fields\AbstractField;

/**
 * Mirrors Fields\Repeater::settingArgs()'s own hard override to
 * transport: refresh, without extending the real Fields\Repeater
 * (whose control this bootstrap-free suite can't instantiate via
 * register()). Isolates the one thing under test: that register()
 * skips add_partial() whenever a field type's own settingArgs()
 * override wins the final transport back to 'refresh'.
 */
final class ForcedRefreshTransportFieldFixture extends AbstractField
{
    /**
     * @return array<string, mixed>
     */
    protected function settingArgs(): array
    {
        return ['transport' => Transport::REFRESH->value];
    }
}
