<?php

declare(strict_types=1);

/**
 * A minimal, deliberately fake stand-in for WordPress core's
 * `WP_Customize_Control`. This test suite has no WordPress bootstrap.
 *
 * The constructor signature and `$args`-to-property copy are matched
 * to core's: an arg naming a property that isn't already declared is
 * silently dropped, not merely deprecated. That's why
 * `Controls\Repeater`/`Controls\Select`/`Controls\Image` declare their
 * own extra properties. The properties below are left untyped, as core
 * leaves them, since several controls override `public $type` without
 * a native type (typing the parent here would fatal the whole suite).
 *
 * `to_json()` is intentionally a no-op. What core's own `to_json()`
 * actually sets is documented (with citation) on
 * `Controls\AbstractControl::to_json()` instead. `link()`/`get_link()`
 * are implemented faithfully, including the part that matters:
 * `get_link()` returns `''` when the setting is absent, so a render
 * test asserting no `data-customize-setting-link` attribute isn't
 * passing for the wrong reason. `input_attrs()`, `render()`,
 * `get_content()` and the rest stay absent rather than stubbed as
 * no-ops, so a test that needs one fails loudly instead of quietly
 * asserting the wrong thing.
 *
 * `$stubbedValue` is the one property here that core does not have:
 * how a test supplies the value `value()` returns, set through the
 * same `$args` mechanism as everything else.
 *
 * Declared only in the global namespace and loaded via an explicit
 * `require_once`, never autoloaded.
 */
if (! class_exists('WP_Customize_Control', false)) {
    // phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    // phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
    class WP_Customize_Control
    {
        /** @var mixed */
        public $manager;

        /** @var string */
        public $id = '';

        /** @var array<int|string, mixed>|string */
        public $settings = '';

        /** @var mixed */
        public $setting;

        /** @var string */
        public $capability = '';

        /** @var int */
        public $priority = 10;

        /** @var string */
        public $section = '';

        /** @var string */
        public $label = '';

        /** @var string */
        public $description = '';

        /** @var array<int|string, mixed> */
        public $choices = [];

        /** @var array<string, mixed> */
        public $input_attrs = [];

        /** @var string */
        public $type = 'text';

        /** @var mixed */
        public $active_callback;

        /** @var array<string, mixed> */
        public $json = [];

        /** @var int */
        public $instance_number = 0;

        /**
         * Not a core property; see this file's docblock. Supplied through
         * the same $args copy as everything else, e.g.
         * `new SomeControl(null, 'my_setting', ['stubbedValue' => 'x'])`.
         *
         * @var mixed
         */
        public $stubbedValue;

        /**
         * @param mixed                $manager
         * @param string               $id
         * @param array<string, mixed> $args
         */
        public function __construct($manager, $id, $args = [])
        {
            $keys = array_keys(get_object_vars($this));

            foreach ($keys as $key) {
                if (! isset($args[$key])) {
                    continue;
                }

                $this->$key = $args[$key];
            }

            $this->manager = $manager;
            $this->id = $id;
        }

        public function value(): mixed
        {
            return $this->stubbedValue;
        }

        /**
         * Transcribed from core: the binding attribute, or '' when there is
         * no such setting. Emitting '' for a missing setting is the part
         * worth reproducing; see this file's docblock.
         *
         * @param string $setting_key
         */
        // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        public function get_link($setting_key = 'default'): string
        {
            $setting = is_array($this->settings) ? ($this->settings[$setting_key] ?? null) : null;

            if (! is_object($setting) || ! isset($setting->id)) {
                return '';
            }

            return 'data-customize-setting-link="' . esc_attr($setting->id) . '"';
        }

        /**
         * @param string $setting_key
         */
        public function link($setting_key = 'default'): void
        {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $this->get_link($setting_key);
        }

        // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        public function to_json(): void
        {
        }
    }
    // phpcs:enable
}
