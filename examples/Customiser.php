<?php

declare(strict_types=1);

namespace App;

use Itineris\WpcBuilder\Customizer;

final class Customiser
{
    public static function register(): void
    {
        Customizer::make()
            ->addSections([
                Sections\Footer::class,
                Sections\SiteIdentity::class,
            ])
            ->register();
    }
}
