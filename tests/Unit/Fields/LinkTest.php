<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Controls\Link as LinkControl;
use Itineris\WpcBuilder\Fields\Link;
use Itineris\WpcBuilder\Tests\Fixtures\Support\RequiredWhenSiblingSettings;

require_once __DIR__ . '/../../Fixtures/wp-error-double.php';

beforeEach(function (): void {
    Functions\stubTranslationFunctions();
    Functions\when('esc_url_raw')->returnArg();
    Functions\when('sanitize_text_field')->returnArg();
    Functions\when('wp_allowed_protocols')->justReturn(['http', 'https', 'mailto', 'tel']);
});

it('sanitizes a fresh array value into the url/text/target shape', function (): void {
    $sanitize = Link::make('cta')->buildSettingArgs(new Config())['sanitize_callback'];

    $result = $sanitize(['url' => 'https://example.test', 'text' => 'Visit us', 'target' => '_blank']);

    expect($result)->toBe([
        'url' => 'https://example.test',
        'text' => 'Visit us',
        'target' => '_blank',
    ]);
});

it('sanitizes the JSON+urlencoded wire format written by the hidden input', function (): void {
    $sanitize = Link::make('cta')->buildSettingArgs(new Config())['sanitize_callback'];

    // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
    $wire = rawurlencode((string) json_encode([
        'url' => 'https://example.test',
        'text' => 'Visit us',
        'target' => '_blank',
    ]));

    expect($sanitize($wire))->toBe([
        'url' => 'https://example.test',
        'text' => 'Visit us',
        'target' => '_blank',
    ]);
});

it('treats a legacy plain-string value as the url, for backward compatibility', function (): void {
    $sanitize = Link::make('cta')->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize('https://legacy.example.test'))->toBe([
        'url' => 'https://legacy.example.test',
        'text' => '',
        'target' => '_self',
    ]);
});

it('sanitizes an empty or absent value to the empty shape', function (): void {
    $sanitize = Link::make('cta')->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize(null))->toBe(['url' => '', 'text' => '', 'target' => '_self'])
        ->and($sanitize(''))->toBe(['url' => '', 'text' => '', 'target' => '_self']);
});

it('passes validation when the url is empty, regardless of text', function (): void {
    $validate = Link::make('cta')->buildSettingArgs(new Config())['validate_callback'];

    expect($validate(true, ['url' => '', 'text' => '']))->toBeTrue();
});

it('fails validation with invalid_url when the url does not parse', function (): void {
    $validate = Link::make('cta')->buildSettingArgs(new Config())['validate_callback'];

    $validity = $validate(true, ['url' => 'not a url', 'text' => 'Visit us']);

    expect($validity)->toBeInstanceOf(WP_Error::class)
        ->and($validity->get_error_codes())->toBe(['invalid_url']);
});

it('fails validation with link_text_required when the url is set but text is blank', function (): void {
    $validate = Link::make('cta')->buildSettingArgs(new Config())['validate_callback'];

    $validity = $validate(true, ['url' => 'https://example.test', 'text' => '  ']);

    expect($validity)->toBeInstanceOf(WP_Error::class)
        ->and($validity->get_error_codes())->toBe(['link_text_required']);
});

it('passes validation when both url and text are set', function (): void {
    $validate = Link::make('cta')->buildSettingArgs(new Config())['validate_callback'];

    expect($validate(true, ['url' => 'https://example.test', 'text' => 'Visit us']))->toBeTrue();
});

it('merges into an existing WP_Error rather than replacing it', function (): void {
    $validate = Link::make('cta')->buildSettingArgs(new Config())['validate_callback'];

    $existing = new WP_Error();
    $existing->add('some_other_error', 'Something else is wrong.');

    $validity = $validate($existing, ['url' => 'not a url']);

    expect($validity)->toBe($existing)
        ->and($validity->get_error_codes())->toBe(['some_other_error', 'invalid_url']);
});

it('exposes fromStored() as the public, supported way to read a possibly-legacy value', function (): void {
    expect(Link::fromStored('https://legacy.example.test'))->toBe([
        'url' => 'https://legacy.example.test',
        'text' => '',
        'target' => '_self',
    ])
        ->and(Link::fromStored([
            'url' => 'https://example.test',
            'text' => 'Visit us',
            'target' => '_blank',
        ]))->toBe([
            'url' => 'https://example.test',
            'text' => 'Visit us',
            'target' => '_blank',
        ]);
});

it('resolves to the wpc-builder-link control type and class', function (): void {
    $args = Link::make('cta')->buildControlArgs('footer');

    expect($args['type'])->toBe('wpc-builder-link')
        ->and(Link::make('cta')->controlClass())->toBe(LinkControl::class);
});

it('sanitize_callback is a closure, not a plain string sanitizer', function (): void {
    $sanitize = Link::make('cta')->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize)->toBeInstanceOf(Closure::class);
});

it('defaults an unset field to the array shape, not WP core\'s own raw empty string', function (): void {
    $args = Link::make('cta')->buildSettingArgs(new Config());

    expect($args['default'])->toBe(['url' => '', 'text' => '', 'target' => '_self']);
});

it('keeps an explicitly set default value', function (): void {
    $args = Link::make('cta')
        ->setDefaultValue(['url' => 'https://example.test', 'text' => 'Visit us', 'target' => '_blank'])
        ->buildSettingArgs(new Config());

    expect($args['default'])->toBe(['url' => 'https://example.test', 'text' => 'Visit us', 'target' => '_blank']);
});

it('exposes sanitizeValue() as the reusable sanitizer Repeater will call directly', function (): void {
    expect(Link::sanitizeValue(['url' => 'https://example.test', 'text' => 'Visit us', 'target' => '_blank']))
        ->toBe(['url' => 'https://example.test', 'text' => 'Visit us', 'target' => '_blank'])
        ->and(Link::sanitizeValue('https://legacy.example.test'))
        ->toBe(['url' => 'https://legacy.example.test', 'text' => '', 'target' => '_self']);
});

it('exposes validateValue() as the reusable validator Repeater will call directly', function (): void {
    expect(Link::validateValue(['url' => '', 'text' => '']))->toBeNull()
        ->and(Link::validateValue(['url' => 'not a url', 'text' => 'Visit us']))->toBe('invalid_url')
        ->and(Link::validateValue(['url' => 'https://example.test', 'text' => '  ']))->toBe('link_text_required')
        ->and(Link::validateValue(['url' => 'https://example.test', 'text' => 'Visit us']))->toBeNull();
});

it('requires text for any value validated as the compound shape, including a bare string', function (): void {
    expect(Link::validateValue('https://example.test'))->toBe('link_text_required');
});

it('treats a whitespace-only url as empty, not as requiring text', function (): void {
    expect(Link::validateValue(['url' => ' ', 'text' => '']))->toBeNull();
});

it('treats a blank url as blank for requiredWhen, regardless of any text', function (): void {
    $field = Link::make('cta')->setRequiredWhen([
        ['setting' => 'cta_enabled', 'operator' => '==', 'value' => true],
    ]);

    $validate = $field->buildSettingArgs(new Config())['validate_callback'];
    $setting = RequiredWhenSiblingSettings::build(['cta_enabled' => true]);

    $validity = $validate(true, ['url' => '', 'text' => 'orphaned text'], $setting);

    expect($validity)->toBeInstanceOf(WP_Error::class)
        ->and($validity->get_error_codes())->toBe(['value_required']);
});

it('treats a set url as satisfying requiredWhen', function (): void {
    $field = Link::make('cta')->setRequiredWhen([
        ['setting' => 'cta_enabled', 'operator' => '==', 'value' => true],
    ]);

    $validate = $field->buildSettingArgs(new Config())['validate_callback'];
    $setting = RequiredWhenSiblingSettings::build(['cta_enabled' => true]);

    expect($validate(true, ['url' => 'https://example.test', 'text' => 'Visit us'], $setting))->toBeTrue();
});

it('coerces any target other than the literal "_blank" to "_self", including a malicious string', function (): void {
    expect(Link::sanitizeValue(['url' => '', 'text' => '', 'target' => '_blank']))->toHaveKey('target', '_blank')
        ->and(Link::sanitizeValue(['url' => '', 'text' => '', 'target' => '_self']))->toHaveKey('target', '_self')
        ->and(Link::sanitizeValue(['url' => '', 'text' => '', 'target' => '" onmouseover="alert(1)']))
        ->toHaveKey('target', '_self')
        ->and(Link::sanitizeValue(['url' => '', 'text' => '', 'target' => true]))->toHaveKey('target', '_self')
        ->and(Link::sanitizeValue(['url' => '', 'text' => '', 'target' => null]))->toHaveKey('target', '_self');
});
