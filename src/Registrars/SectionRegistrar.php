<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Registrars;

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Sections\AbstractSection;
use WP_Customize_Manager;

final class SectionRegistrar
{
    /**
     * @param array<int, AbstractSection> $sections
     */
    public function __construct(
        private readonly array $sections,
        private readonly Config $config
    ) {
    }

    public function register(WP_Customize_Manager $customizer): void
    {
        foreach ($this->sections as $section) {
            $section->register($customizer, $this->config);
        }
    }
}
