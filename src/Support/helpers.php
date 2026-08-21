<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Support;

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Enums\OptionType;
use Itineris\WpcBuilder\Fields\AbstractField;

use function array_filter;
use function crc32;
use function dechex;
use function in_array;
use function is_scalar;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_contains;
use function strtolower;
use function trim;
use function ucfirst;
use function wp_allowed_protocols;

function label_from_id(string $id): string
{
    $words = trim((string) preg_replace('/[_\-]+/', ' ', $id));

    return ucfirst(strtolower($words));
}

/**
 * Reads $field->visibleWhen() directly, unlike AbstractField's own
 * buildSettingArgs()/buildControlArgs(), which trigger the lazy-once
 * setup() hook first. A subclass populating $visibleWhen from setup()
 * rather than the fluent setVisibleWhen() call would be silently
 * dropped if collect_visible_when_conditions() runs before setup()
 * otherwise fires.
 *
 * @param array<int, AbstractField> $fields
 *
 * @return array<string, array<int, mixed>>
 */
function collect_visible_when_conditions(array $fields, Config $config): array
{
    $collected = [];

    foreach ($fields as $field) {
        $visibleWhen = $field->visibleWhen();

        if ([] === $visibleWhen) {
            continue;
        }

        $collected[$field->settingId($config)] = $visibleWhen;
    }

    return $collected;
}

/**
 * Same shape and same lazy-once-setup() caveat as
 * collect_visible_when_conditions() above, for AbstractField::$requiredWhen
 * instead.
 *
 * @param array<int, AbstractField> $fields
 *
 * @return array<string, array<int, mixed>>
 */
function collect_required_when_conditions(array $fields, Config $config): array
{
    $collected = [];

    foreach ($fields as $field) {
        $requiredWhen = $field->requiredWhen();

        if ([] === $requiredWhen) {
            continue;
        }

        $collected[$field->settingId($config)] = $requiredWhen;
    }

    return $collected;
}

/**
 * Derives the DOM/TinyMCE id for a Controls\Editor textarea from a
 * Customizer setting id (which routinely contains `[`/`]` and isn't
 * usable as-is). Stripping non-alphanumerics alone is lossy: `footer_text`
 * and `footertext` both reduce to `footertext`, so two Editor fields with
 * colliding stripped ids would render two textareas sharing one DOM id,
 * and the second would silently fail to initialise.
 * The `crc32` suffix of the raw id disambiguates them; it's a checksum,
 * not a security primitive, chosen for being deterministic across
 * requests (unlike PHP's own string hashing).
 */
function editor_id_from_setting_id(string $settingId): string
{
    $slug = preg_replace('/[^a-z0-9]/', '', strtolower($settingId)) ?? '';

    return 'wpc_builder_editor_' . $slug . '_' . dechex(crc32($settingId));
}

/**
 * Builds the args array WP_Customize_Selective_Refresh::add_partial()
 * expects. `container_inclusive` is only included when the caller
 * explicitly set it, because core already defaults it to false, so omitting
 * it is equivalent but keeps the array minimal, matching the
 * array_filter(... !== null) convention AbstractField uses elsewhere.
 *
 * @return array<string, mixed>
 */
function build_partial_refresh_args(string $selector, callable $renderCallback, ?bool $containerInclusive): array
{
    $args = [
        'selector' => $selector,
        'render_callback' => $renderCallback,
        'container_inclusive' => $containerInclusive,
    ];

    return array_filter($args, static fn (mixed $value): bool => null !== $value);
}

function format_setting_id(string $settingId, Config $config): string
{
    if (OptionType::OPTION !== $config->optionType) {
        return $settingId;
    }

    if ('' === $config->optionName) {
        return $settingId;
    }

    if (str_contains($settingId, '[')) {
        return $settingId;
    }

    return sprintf('%s[%s]', $config->optionName, $settingId);
}

/**
 * Rejects only whitespace and schemes outside wp_allowed_protocols().
 * Deliberately permissive: relative paths, fragments, protocol-relative,
 * tel: and IDN URLs are all real stored shapes, and one rejected value
 * makes WordPress abort the entire changeset save.
 * Mirrored client-side in assets/src/js/url-validation.js, keep in sync.
 */
function is_valid_or_empty_url(mixed $value): bool
{
    if (null === $value) {
        return true;
    }

    if (! is_scalar($value)) {
        return false;
    }

    $url = trim((string) $value);

    if ('' === $url) {
        return true;
    }

    if (1 === preg_match('/\s/', $url)) {
        return false;
    }

    if (1 !== preg_match('/^([a-z][a-z0-9+.\-]*):/i', $url, $matches)) {
        return true;
    }

    return in_array(strtolower($matches[1]), wp_allowed_protocols(), true);
}
