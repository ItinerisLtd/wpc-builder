<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use WP_Error;

use function __;
use function Itineris\WpcBuilder\Support\is_valid_or_empty_url;

class Url extends AbstractTextField
{
    protected const string CONTROL_TYPE = 'url';

    /**
     * `wp_kses_post`, not `esc_url_raw`: the same shadowing as
     * Fields\Text, one level deeper. Do not "correct" this back.
     *
     * `Fields\Link` used to be a zero-override alias of this class as
     * `extends Url {}`, but no longer is. It's now an independent field
     * storing a compound `url`/`text`/`target` value.
     */
    protected function defaultSanitizeCallback(): callable|string|null
    {
        return 'wp_kses_post';
    }

    /**
     * @return array<string, mixed>
     */
    protected function settingArgs(): array
    {
        return [
            'validate_callback' => static function (mixed $validity, mixed $value): mixed {
                if (! is_valid_or_empty_url($value)) {
                    $error = $validity instanceof WP_Error ? $validity : new WP_Error();
                    $error->add('invalid_url', __('Please enter a valid URL.', 'wpc-builder'));

                    return $error;
                }

                return $validity;
            },
        ];
    }
}
