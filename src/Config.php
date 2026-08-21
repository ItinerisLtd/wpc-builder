<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder;

use Itineris\WpcBuilder\Enums\OptionType;

final class Config
{
    public function __construct(
        public readonly string $capability = 'edit_theme_options',
        public readonly OptionType $optionType = OptionType::THEME_MOD,
        public readonly string $optionName = ''
    ) {
    }
}
