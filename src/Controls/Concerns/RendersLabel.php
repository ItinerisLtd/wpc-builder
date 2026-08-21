<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls\Concerns;

trait RendersLabel
{
    protected function renderLabel(): void
    {
        if (empty($this->label)) {
            return;
        }

        ?>
        <span class="customize-control-title">
            <?php echo esc_html($this->label); ?>
        </span>
        <?php
    }
}
