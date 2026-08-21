<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Enums\OptionType;

it('defaults to theme_mod storage with the theme options capability', function (): void {
    $config = new Config();

    expect($config->capability)->toBe('edit_theme_options')
        ->and($config->optionType)->toBe(OptionType::THEME_MOD)
        ->and($config->optionName)->toBe('');
});
