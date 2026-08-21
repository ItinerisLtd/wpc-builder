<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Fields;

use Itineris\WpcBuilder\Fields\AbstractField;

/**
 * A field whose settingArgs() and controlArgs() both contribute keys, used
 * to prove register() forwards buildSettingArgs()/buildControlArgs() output
 * to WordPress unmodified.
 */
final class RegisterTestField extends AbstractField
{
    protected const string CONTROL_TYPE = 'checkbox';

    protected function defaultSanitizeCallback(): callable|string|null
    {
        return 'rest_sanitize_boolean';
    }

    /**
     * @return array<string, mixed>
     */
    protected function settingArgs(): array
    {
        return ['sanitize_js_callback' => 'absint'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function controlArgs(): array
    {
        return ['choices' => ['a' => 'A', 'b' => 'B']];
    }
}
