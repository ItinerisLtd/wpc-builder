<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Support;

use Itineris\WpcBuilder\Enums\SaveAs;

use function absint;
use function attachment_url_to_postid;
use function basename;
use function esc_url_raw;
use function get_attached_file;
use function is_array;
use function is_numeric;
use function is_scalar;
use function is_string;
use function sanitize_text_field;
use function wp_get_attachment_url;

final class AttachmentValue
{
    public static function sanitize(mixed $value, SaveAs $saveAs): mixed
    {
        return match ($saveAs) {
            SaveAs::ARRAY => self::toArray($value),
            SaveAs::ID => self::toId($value),
            SaveAs::URL => self::toUrl($value),
        };
    }

    /**
     * @return array{id: int|string, url: string, filename: string}
     */
    private static function toArray(mixed $value): array
    {
        if (is_array($value)) {
            $id = $value['id'] ?? null;
            $url = $value['url'] ?? null;
            $filename = $value['filename'] ?? null;

            return [
                'id' => isset($id) && '' !== $id && is_scalar($id) ? (int) $id : '',
                'url' => isset($url) && '' !== $url ? esc_url_raw(self::toScalarString($url)) : '',
                'filename' => isset($filename) && '' !== $filename
                    ? sanitize_text_field(self::toScalarString($filename))
                    : '',
            ];
        }

        if (is_string($value) && ! is_numeric($value)) {
            $attachmentId = attachment_url_to_postid($value);

            return [
                'id' => $attachmentId,
                'url' => $value,
                'filename' => basename((string) get_attached_file($attachmentId)),
            ];
        }

        $attachmentId = self::toAbsint($value);

        return [
            'id' => $attachmentId,
            'url' => (string) wp_get_attachment_url($attachmentId),
            'filename' => basename((string) get_attached_file($attachmentId)),
        ];
    }

    private static function toId(mixed $value): int
    {
        if ('' === $value) {
            return 0;
        }

        if (is_string($value) && ! is_numeric($value)) {
            return attachment_url_to_postid($value);
        }

        if (is_array($value) && isset($value['id'])) {
            return self::toAbsint($value['id']);
        }

        return self::toAbsint($value);
    }

    private static function toUrl(mixed $value): string
    {
        if (is_array($value) && isset($value['url'])) {
            return self::toScalarString($value['url']);
        }

        if (is_numeric($value)) {
            return (string) wp_get_attachment_url(self::toAbsint($value));
        }

        return esc_url_raw(self::toScalarString($value));
    }

    /**
     * `absint()` accepts `array|bool|float|int|resource|string|null` at
     * runtime; a `mixed` value that isn't one of those (i.e. an object) is
     * coerced to `0` here rather than passed through, since a media value
     * never realistically receives an object.
     */
    private static function toAbsint(mixed $value): int
    {
        return absint(is_array($value) || is_scalar($value) || null === $value ? $value : 0);
    }

    /**
     * Under this package's `strict_types=1`, casting a non-scalar to
     * `(string)` would fatal; a media value is never realistically an
     * array or object here, so a non-scalar is coerced to `''` rather
     * than passed through.
     */
    private static function toScalarString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
