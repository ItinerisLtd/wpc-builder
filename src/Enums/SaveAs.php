<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Enums;

enum SaveAs: string
{
    case URL = 'url';
    case ID = 'id';
    case ARRAY = 'array';
}
