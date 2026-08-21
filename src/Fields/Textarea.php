<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

final class Textarea extends AbstractTextField
{
    protected const string CONTROL_TYPE = 'textarea';

    protected function defaultSanitizeCallback(): callable|string
    {
        return 'wp_kses_post';
    }
}
