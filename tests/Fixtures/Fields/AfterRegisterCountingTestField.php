<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Fields;

use Itineris\WpcBuilder\Fields\AbstractField;

/**
 * A field that counts afterRegister() invocations, used to prove
 * afterRegister() runs exactly once, only from within register() (never
 * from buildSettingArgs()/buildControlArgs() alone), and never for a
 * second register() call on an already-registered field.
 */
final class AfterRegisterCountingTestField extends AbstractField
{
    public int $afterRegisterCalls = 0;

    protected function afterRegister(): void
    {
        ++$this->afterRegisterCalls;
    }
}
