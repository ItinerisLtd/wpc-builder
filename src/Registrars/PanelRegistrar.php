<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Registrars;

use Itineris\WpcBuilder\Panels\AbstractPanel;
use WP_Customize_Manager;

final class PanelRegistrar
{
    /**
     * @param array<int, AbstractPanel> $panels
     */
    public function __construct(private readonly array $panels)
    {
    }

    public function register(WP_Customize_Manager $customizer): void
    {
        foreach ($this->panels as $panel) {
            $panel->register($customizer);
        }
    }
}
