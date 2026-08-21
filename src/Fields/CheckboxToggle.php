<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use Itineris\WpcBuilder\Controls\Toggle as ToggleControl;

class CheckboxToggle extends Checkbox
{
    protected const string CONTROL_TYPE = 'wpc-builder-toggle';
    protected const CONTROL = ToggleControl::class;
}
