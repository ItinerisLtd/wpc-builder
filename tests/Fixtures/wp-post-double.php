<?php

declare(strict_types=1);

/**
 * A minimal, deliberately fake stand-in for WordPress core's `WP_Post`.
 * This test suite has no WordPress bootstrap. Only the `$ID` property is
 * needed by anything under test; declared only in the global namespace
 * and loaded via an explicit `require_once`, never autoloaded.
 */
if (! class_exists('WP_Post', false)) {
    // phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    class WP_Post
    {
        public function __construct(public int $ID)
        {
        }
    }
    // phpcs:enable
}
