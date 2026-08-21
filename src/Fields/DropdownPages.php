<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

final class DropdownPages extends AbstractField
{
    protected const string CONTROL_TYPE = 'dropdown-pages';

    protected function defaultSanitizeCallback(): callable|string
    {
        return 'sanitize_text_field';
    }
}
