<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use Itineris\WpcBuilder\Controls\Link as LinkControl;
use WP_Error;
use function __;
use function esc_url_raw;
use function is_array;
use function is_scalar;
use function is_string;
use function Itineris\WpcBuilder\Support\is_valid_or_empty_url;
use function json_decode;
use function rawurldecode;
use function sanitize_text_field;
use function trim;

/**
 * Stores `['url' => string, 'text' => string, 'target' => string]`. `target`
 * is the real HTML attribute value, `'_blank'` or `'_self'`. `rel` is
 * derived by the consuming theme, never stored.
 *
 * Backward compat: a legacy bare string (this field's original shape) is
 * read as the url.
 */
final class Link extends AbstractField
{
    protected const string CONTROL_TYPE = 'wpc-builder-link';
    protected const CONTROL = LinkControl::class;
    private const string TARGET_BLANK = '_blank';
    private const string TARGET_SELF = '_self';

    protected function setup(): void
    {
        $this->defaultValue ??= ['url' => '', 'text' => '', 'target' => self::TARGET_SELF];
    }

    protected function defaultSanitizeCallback(): callable
    {
        return static fn (mixed $value): array => self::sanitizeValue($value);
    }

    protected function isRequiredWhenValueBlank(mixed $value): bool
    {
        return '' === trim(self::fromStored($value)['url']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function settingArgs(): array
    {
        return [
            'validate_callback' => static function (mixed $validity, mixed $value): mixed {
                $errorCode = self::validateValue($value);

                if (null === $errorCode) {
                    return $validity;
                }

                $error = $validity instanceof WP_Error ? $validity : new WP_Error();
                $error->add(
                    $errorCode,
                    'invalid_url' === $errorCode
                        ? __('Please enter a valid URL.', 'wpc-builder')
                        : __('Please enter link text.', 'wpc-builder'),
                );

                return $error;
            },
        ];
    }

    /**
     * @return array{url: string, text: string, target: string}
     */
    public static function fromStored(mixed $value): array
    {
        $decoded = self::decodeRaw($value);

        return [
            'url' => self::toScalarString($decoded['url'] ?? ''),
            'text' => self::toScalarString($decoded['text'] ?? ''),
            'target' => self::normaliseTarget($decoded['target'] ?? ''),
        ];
    }

    /**
     * @return array{url: string, text: string, target: string}
     */
    public static function sanitizeValue(mixed $value): array
    {
        $decoded = self::fromStored($value);

        return [
            'url' => esc_url_raw($decoded['url']),
            'text' => sanitize_text_field($decoded['text']),
            'target' => $decoded['target'],
        ];
    }

    public static function validateValue(mixed $value): ?string
    {
        $decoded = self::fromStored($value);

        if (! is_valid_or_empty_url($decoded['url'])) {
            return 'invalid_url';
        }

        if ('' !== trim($decoded['url']) && '' === trim($decoded['text'])) {
            return 'link_text_required';
        }

        return null;
    }

    private static function normaliseTarget(mixed $target): string
    {
        return self::TARGET_BLANK === $target ? self::TARGET_BLANK : self::TARGET_SELF;
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function decodeRaw(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || '' === $value) {
            return [];
        }

        $decoded = json_decode(rawurldecode($value), true);

        return is_array($decoded) ? $decoded : ['url' => $value];
    }

    private static function toScalarString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
