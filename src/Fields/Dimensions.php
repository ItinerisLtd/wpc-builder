<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use Itineris\WpcBuilder\Controls\Dimensions as DimensionsControl;

final class Dimensions extends AbstractChoiceField
{
    protected const string CONTROL_TYPE = 'wpc-builder-dimensions';
    protected const CONTROL = DimensionsControl::class;

    protected function defaultSanitizeCallback(): string
    {
        return 'sanitize_text_field';
    }
}
