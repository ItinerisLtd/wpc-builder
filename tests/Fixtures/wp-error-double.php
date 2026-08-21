<?php

declare(strict_types=1);

/**
 * A minimal, deliberately fake stand-in for WordPress core's `WP_Error`.
 * This test suite has no WordPress bootstrap. `add()`'s signature and
 * the public `$errors` property are matched to core's own declaration,
 * not invented. Declared only in the global namespace and loaded via
 * an explicit `require_once`, never autoloaded.
 */
if (! class_exists('WP_Error', false)) {
    // phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    class WP_Error
    {
        /** @var array<string, array<int, string>> */
        public array $errors = [];

        /** @var array<string, mixed> */
        public array $error_data = [];

        public function add(string $code, string $message, mixed $data = ''): void
        {
            $this->errors[$code][] = $message;

            if ('' !== $data) {
                $this->error_data[$code] = $data;
            }
        }

        /**
         * @return array<int, string>
         */
        public function get_error_codes(): array
        {
            return array_keys($this->errors);
        }
    }
    // phpcs:enable
}
