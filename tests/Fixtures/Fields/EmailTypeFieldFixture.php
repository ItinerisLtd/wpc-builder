<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Tests\Fixtures\Fields;

use Itineris\WpcBuilder\Fields\AbstractField;

/**
 * No Field class in this package declares CONTROL_TYPE 'email', so the
 * repeater's 'email' sub-field coercion is otherwise unreachable from
 * this package's own public API. Exists purely so that match arm still
 * has a direct regression test.
 */
final class EmailTypeFieldFixture extends AbstractField
{
    protected const string CONTROL_TYPE = 'email';
}
