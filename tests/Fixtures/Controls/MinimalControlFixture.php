<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Controls;

use Itineris\WpcBuilder\Controls\AbstractControl;

/**
 * The smallest possible concrete AbstractControl subclass, implementing
 * only the one abstract method it must, used solely to exercise
 * AbstractControl::to_json() against the fake WP_Customize_Control
 * double (wp-customize-control-double.php) in
 * tests/Unit/Controls/AbstractControlToJsonTest.php. Never rendered.
 */
final class MinimalControlFixture extends AbstractControl
{
    public function renderContent(): void
    {
    }
}
