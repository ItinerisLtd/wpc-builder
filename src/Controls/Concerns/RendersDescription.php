<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Controls\Concerns;

use function esc_attr;
use function wp_kses_post;

/**
 * Renders a control description as HTML, not escaped text, matching
 * how WordPress core renders descriptions everywhere else (raw,
 * unescaped), so a description containing e.g. an `<a href>` renders as
 * a link rather than visible angle brackets. Uses `wp_kses_post()`
 * rather than a bare `echo`, to keep a stray `<script>` out of the
 * Customizer screen.
 *
 * $descriptionId, when given, is emitted as the span's `id` so a
 * control's own input can point at it via `aria-describedby`.
 */
trait RendersDescription
{
    protected function renderDescription(?string $descriptionId = null): void
    {
        if (empty($this->description)) {
            return;
        }

        ?>
        <span
            <?php if (null !== $descriptionId) : ?>
                id="<?php echo esc_attr($descriptionId); ?>"
            <?php endif; ?>
            class="description customize-control-description"
        ><?php echo wp_kses_post($this->description); ?></span>
        <?php
    }
}
