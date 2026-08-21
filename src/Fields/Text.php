<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

final class Text extends AbstractTextField
{
    protected const string CONTROL_TYPE = 'text';

    /**
     * `wp_kses_post`, not `sanitize_textarea_field`.
     *
     * Keep it this way: sanitize callbacks run on write only, and a
     * Text field holding `Copyright 2024<br>...` keeps its `<br>`
     * forever. Under `sanitize_textarea_field` the next editor save
     * would flatten it, permanently and silently.
     */
    protected function defaultSanitizeCallback(): callable|string
    {
        return 'wp_kses_post';
    }
}
