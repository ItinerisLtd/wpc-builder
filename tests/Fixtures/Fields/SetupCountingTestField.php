<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Fields;

use Itineris\WpcBuilder\Fields\AbstractField;

/**
 * A field that counts setup() invocations, used to prove setup() runs
 * lazily and exactly once across repeated buildSettingArgs()/
 * buildControlArgs() calls.
 */
final class SetupCountingTestField extends AbstractField
{
    public int $setupCalls = 0;

    protected function setup(): void
    {
        ++$this->setupCalls;
    }
}
