<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use Itineris\WpcBuilder\Controls\RadioButtonset as RadioButtonsetControl;

/**
 * Only the control type/class differs from Radio, not the sanitize
 * behaviour, so this extends our own Radio field rather than
 * re-deriving the "value must be a known choice, else fall back to
 * default" logic.
 */
final class RadioButtonset extends Radio
{
    protected const string CONTROL_TYPE = 'wpc-builder-radio-buttonset';
    protected const CONTROL = RadioButtonsetControl::class;
}
