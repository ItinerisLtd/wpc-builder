<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Enums;

enum Transport: string
{
    case REFRESH = 'refresh';
    case POST_MESSAGE = 'postMessage';
}
