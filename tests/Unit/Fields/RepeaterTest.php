<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Enums\SaveAs;
use Itineris\WpcBuilder\Fields\Checkbox;
use Itineris\WpcBuilder\Fields\Color;
use Itineris\WpcBuilder\Fields\DropdownPages;
use Itineris\WpcBuilder\Fields\Image;
use Itineris\WpcBuilder\Fields\Link;
use Itineris\WpcBuilder\Fields\Radio;
use Itineris\WpcBuilder\Fields\Repeater;
use Itineris\WpcBuilder\Fields\Select;
use Itineris\WpcBuilder\Fields\Text;
use Itineris\WpcBuilder\Fields\Textarea;
use Itineris\WpcBuilder\Fields\Url;
use Itineris\WpcBuilder\Support\RowLabel;
use Itineris\WpcBuilder\Tests\Fixtures\Fields\EmailTypeFieldFixture;
use Itineris\WpcBuilder\Tests\Fixtures\Support\RequiredWhenSiblingSettings;

require_once __DIR__ . '/../../Fixtures/wp-error-double.php';
require_once __DIR__ . '/../../Fixtures/Controls/wp-customize-control-double.php';

beforeEach(function (): void {
    Functions\stubTranslationFunctions();
    Functions\when('sanitize_text_field')->returnArg();
    Functions\when('esc_url_raw')->returnArg();
    Functions\when('wp_allowed_protocols')->justReturn(['http', 'https', 'mailto', 'tel']);
});

it('decodes a JSON string into an array of rows', function (): void {
    $sanitize = Repeater::make('links')
        ->setFields([Text::make('title'), Url::make('url')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
    $json = rawurlencode(json_encode([
        ['title' => 'Home', 'url' => 'https://example.test'],
    ]));

    expect($sanitize($json))->toBe([
        ['title' => 'Home', 'url' => 'https://example.test'],
    ]);
});

it('passes an already-decoded array straight through', function (): void {
    $sanitize = Repeater::make('links')
        ->setFields([Text::make('title')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['title' => 'Home']]))->toBe([['title' => 'Home']]);
});

it('replaces a non-array row with an empty array', function (): void {
    $sanitize = Repeater::make('links')
        ->setFields([Text::make('title')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['title' => 'Home'], 'broken']))->toBe([['title' => 'Home'], []]);
});

it('forces refresh transport because repeaters cannot postMessage', function (): void {
    $args = Repeater::make('links')
        ->setTransport(\Itineris\WpcBuilder\Enums\Transport::POST_MESSAGE)
        ->buildSettingArgs(new Config());

    expect($args['transport'])->toBe('refresh');
});

it('exposes sub-fields keyed by id in control args', function (): void {
    $args = Repeater::make('links')
        ->setFields([Text::make('title')->setLabel('Title')])
        ->setRowLabel(RowLabel::fromField('title'))
        ->setLimit(5)
        ->buildControlArgs('footer');

    expect($args['fields'])->toHaveKey('title')
        ->and($args['fields']['title']['label'])->toBe('Title')
        ->and($args['row_label'])->toBe(['type' => 'field', 'value' => '', 'field' => 'title'])
        ->and($args['choices'])->toBe(['limit' => 5]);
});

it('omits button_label from control args until one is set', function (): void {
    $args = Repeater::make('links')
        ->setFields([Text::make('title')])
        ->buildControlArgs('footer');

    expect($args)->not->toHaveKey('button_label');
});

it('passes a set button label through to control args', function (): void {
    $args = Repeater::make('links')
        ->setFields([Text::make('title')])
        ->setButtonLabel('Add a link')
        ->buildControlArgs('footer');

    expect($args['button_label'])->toBe('Add a link');
});

it('keeps an empty button label as an empty string in control args', function (): void {
    $args = Repeater::make('links')
        ->setFields([Text::make('title')])
        ->setButtonLabel('')
        ->buildControlArgs('footer');

    expect($args['button_label'])->toBe('');
});

it('emits a multiple flag for multi-select, and a wp_dropdown_pages() dropdown for dropdown-pages', function (): void {
    Functions\when('wp_dropdown_pages')->justReturn('<select><option value="0">Select a page</option></select>');

    $args = Repeater::make('links')
        ->setFields([
            Select::make('locations')->setMultiple()->setChoices(['header' => 'Header']),
            DropdownPages::make('page'),
        ])
        ->buildControlArgs('footer');

    expect($args['fields']['locations']['multiple'])->toBeTrue()
        ->and($args['fields']['page']['dropdown'])->toBe('<select><option value="0">Select a page</option></select>');
});

it('embeds Controls\Link\'s markup for a wpc-builder-link sub-field, via renderFieldsMarkup()', function (): void {
    $args = Repeater::make('rows')
        ->setFields([Link::make('cta')])
        ->buildControlArgs('footer');

    expect($args['fields']['cta']['link'])->toBe(\Itineris\WpcBuilder\Controls\Link::renderFieldsMarkup());
});

it('drops a subfield id that is not a configured sub-field', function (): void {
    $sanitize = Repeater::make('links')
        ->setFields([Text::make('title')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['title' => 'Home', 'bogus' => 'nope']]))->toBe([['title' => 'Home']]);
});

it('returns the decoded value unchanged when no sub-fields are configured', function (): void {
    $sanitize = Repeater::make('links')->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['title' => 'Home', 'anything' => 'kept']]))
        ->toBe([['title' => 'Home', 'anything' => 'kept']]);
});

it('round-trips a two-row, two-sub-field value', function (): void {
    $sanitize = Repeater::make('links')
        ->setFields([Text::make('title'), Url::make('url')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    $stored = [
        ['title' => 'Home', 'url' => 'https://example.test/'],
        ['title' => 'About', 'url' => 'https://example.test/about'],
    ];

    expect($sanitize($stored))->toBe($stored);
});

it('coerces a checkbox sub-field to bool', function (): void {
    $sanitize = Repeater::make('rows')
        ->setFields([Checkbox::make('enabled')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['enabled' => '1']]))->toBe([['enabled' => true]])
        ->and($sanitize([['enabled' => '0']]))->toBe([['enabled' => false]])
        ->and($sanitize([['enabled' => '']]))->toBe([['enabled' => false]]);
});

it('coerces a dropdown-pages sub-field to int', function (): void {
    $sanitize = Repeater::make('rows')
        ->setFields([DropdownPages::make('page')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['page' => '7']]))->toBe([['page' => 7]]);
});

it('sanitizes a text sub-field through sanitize_text_field', function (): void {
    Functions\when('sanitize_text_field')->alias(static fn (string $value): string => strtoupper($value));

    $sanitize = Repeater::make('rows')
        ->setFields([Text::make('title')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['title' => 'home']]))->toBe([['title' => 'HOME']]);
});

it('sanitizes a url sub-field through esc_url_raw', function (): void {
    Functions\when('esc_url_raw')->alias(static fn (string $value): string => $value . '?escaped');

    $sanitize = Repeater::make('rows')
        ->setFields([Url::make('link')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['link' => 'https://example.test']]))->toBe([['link' => 'https://example.test?escaped']]);
});

it('sanitizes a Fields\Link sub-field into url/text/target via Fields\Link::sanitizeValue()', function (): void {
    Functions\when('esc_url_raw')->alias(static fn (string $value): string => $value . '?escaped');

    $sanitize = Repeater::make('rows')
        ->setFields([Link::make('cta')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    $row = ['url' => 'https://example.test', 'text' => 'Visit us', 'target' => '_blank'];

    expect($sanitize([['cta' => $row]]))->toBe([
        ['cta' => ['url' => 'https://example.test?escaped', 'text' => 'Visit us', 'target' => '_blank']],
    ]);
});

it('validates a plain url-typed sub-field and rejects an invalid URL', function (): void {
    $validate = Repeater::make('rows')
        ->setFields([Url::make('link')])
        ->buildSettingArgs(new Config())['validate_callback'];

    $validity = $validate(true, [['link' => 'not a url']]);

    expect($validity)->toBeInstanceOf(WP_Error::class)
        ->and($validity->get_error_codes())->toBe(['invalid_url']);
});

it('rejects an invalid URL for a Fields\Link sub-field, via Fields\Link::validateValue()', function (): void {
    $validate = Repeater::make('rows')
        ->setFields([Link::make('cta')])
        ->buildSettingArgs(new Config())['validate_callback'];

    $validity = $validate(true, [['cta' => ['url' => 'not a url', 'text' => 'Visit us']]]);

    expect($validity)->toBeInstanceOf(WP_Error::class)
        ->and($validity->get_error_codes())->toBe(['invalid_url']);
});

it('rejects a Fields\Link sub-field with no text, via Fields\Link::validateValue()', function (): void {
    $validate = Repeater::make('rows')
        ->setFields([Link::make('cta')])
        ->buildSettingArgs(new Config())['validate_callback'];

    $validity = $validate(true, [['cta' => ['url' => 'https://example.test', 'text' => '']]]);

    expect($validity)->toBeInstanceOf(WP_Error::class)
        ->and($validity->get_error_codes())->toBe(['link_text_required']);
});

it('passes validation for a valid Fields\Link sub-field', function (): void {
    $validate = Repeater::make('rows')
        ->setFields([Link::make('cta')])
        ->buildSettingArgs(new Config())['validate_callback'];

    expect($validate(true, [['cta' => ['url' => 'https://example.test', 'text' => 'Visit us']]]))->toBeTrue();
});

it('treats a whitespace-only url as empty for a Fields\Link sub-field, not as requiring text', function (): void {
    $validate = Repeater::make('rows')
        ->setFields([Link::make('cta')])
        ->buildSettingArgs(new Config())['validate_callback'];

    expect($validate(true, [['cta' => ['url' => ' ', 'text' => '']]]))->toBeTrue();
});

it('sanitizes a radio sub-field through sanitize_text_field', function (): void {
    Functions\when('sanitize_text_field')->alias(static fn (string $value): string => strtoupper($value));

    $sanitize = Repeater::make('rows')
        ->setFields([Radio::make('choice')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['choice' => 'a']]))->toBe([['choice' => 'A']]);
});

it('sanitizes a select sub-field through sanitize_text_field (see the "select" judgment call)', function (): void {
    Functions\when('sanitize_text_field')->alias(static fn (string $value): string => strtoupper($value));

    $sanitize = Repeater::make('rows')
        ->setFields([Select::make('layout')->setChoices(['a' => 'A', 'b' => 'B'])])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['layout' => 'b']]))->toBe([['layout' => 'B']]);
});

it('sanitizes a multiple-select sub-field into an array of sanitized strings', function (): void {
    Functions\when('sanitize_text_field')->alias(static fn (string $value): string => strtoupper($value));

    $locations = Select::make('locations')->setMultiple()->setChoices(['header' => 'Header', 'footer' => 'Footer']);

    $sanitize = Repeater::make('rows')
        ->setFields([$locations])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['locations' => ['header', 'footer']]]))->toBe([['locations' => ['HEADER', 'FOOTER']]]);
});

it('casts a scalar to a single-element array for multiple-select', function (): void {
    Functions\when('sanitize_text_field')->alias(static fn (string $value): string => strtoupper($value));

    $sanitize = Repeater::make('rows')
        ->setFields([Select::make('locations')->setMultiple()->setChoices(['header' => 'Header'])])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['locations' => 'header']]))->toBe([['locations' => ['HEADER']]]);
});

it('sanitizes an email sub-field through filter_var(FILTER_SANITIZE_EMAIL)', function (): void {
    $sanitize = Repeater::make('rows')
        ->setFields([EmailTypeFieldFixture::make('contact')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['contact' => 'not an email!']]))->toBe([['contact' => 'notanemail!']]);
});

it('decodes a textarea sub-field through html_entity_decode(wp_kses_post())', function (): void {
    Functions\when('wp_kses_post')->returnArg();

    $sanitize = Repeater::make('rows')
        ->setFields([Textarea::make('body')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['body' => 'Tom &amp; Jerry']]))->toBe([['body' => 'Tom & Jerry']]);
});

it('sanitizes a color sub-field only when truthy', function (): void {
    $sanitize = Repeater::make('rows')
        ->setFields([Color::make('accent')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['accent' => '#fff']]))->toBe([['accent' => '#fff']])
        ->and($sanitize([['accent' => '']]))->toBe([['accent' => '']])
        ->and($sanitize([['accent' => 'not-a-color']]))->toBe([['accent' => '']]);
});

it('sanitizes an image sub-field via AttachmentValue::sanitize()', function (): void {
    $sanitize = Repeater::make('rows')
        ->setFields([Image::make('photo')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['photo' => '42']]))->toBe([['photo' => 42]]);
});

it('honours an image sub-field\'s own setSaveAs() over the id default', function (): void {
    Functions\when('wp_get_attachment_url')->justReturn('https://example.test/photo.jpg');

    $sanitize = Repeater::make('rows')
        ->setFields([Image::make('photo')->setSaveAs(SaveAs::URL)])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['photo' => 42]]))->toBe([['photo' => 'https://example.test/photo.jpg']]);
});

it('lets an explicit per-sub-field sanitize_callback win over the type-based switch', function (): void {
    $sanitize = Repeater::make('rows')
        ->setFields([Text::make('shout')->setSanitizeCallback('strtoupper')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['shout' => 'hi']]))->toBe([['shout' => 'HI']]);
});

it('leaves an uncoerced sub-field type untouched', function (): void {
    $sanitize = Repeater::make('rows')
        ->setFields([\Itineris\WpcBuilder\Fields\Number::make('count')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['count' => '42']]))->toBe([['count' => '42']]);
});

it('hooks wp_enqueue_media() when a sub-field is an Image field', function (): void {
    \Brain\Monkey\Actions\expectAdded('customize_controls_enqueue_scripts')->once()->with('wp_enqueue_media');

    $field = Repeater::make('slides')->setFields([Image::make('photo')]);

    expect(fn (): mixed => (new ReflectionMethod($field, 'afterRegister'))->invoke($field))
        ->not->toThrow(Throwable::class);
});

it('does not hook wp_enqueue_media() when no sub-field is an Image field', function (): void {
    \Brain\Monkey\Actions\expectAdded('customize_controls_enqueue_scripts')->never();

    $field = Repeater::make('links')->setFields([Text::make('title')]);

    expect(fn (): mixed => (new ReflectionMethod($field, 'afterRegister'))->invoke($field))
        ->not->toThrow(Throwable::class);
});

it('hooks Controls\Link asset registration when a sub-field is a Link field', function (): void {
    \Brain\Monkey\Actions\expectAdded('customize_controls_enqueue_scripts')->once();

    $field = Repeater::make('rows')->setFields([Link::make('cta')]);

    expect(fn (): mixed => (new ReflectionMethod($field, 'afterRegister'))->invoke($field))
        ->not->toThrow(Throwable::class);
});

it('does not hook Controls\Link asset registration when no sub-field is a Link field', function (): void {
    \Brain\Monkey\Actions\expectAdded('customize_controls_enqueue_scripts')->never();

    $field = Repeater::make('rows')->setFields([Text::make('title')]);

    expect(fn (): mixed => (new ReflectionMethod($field, 'afterRegister'))->invoke($field))
        ->not->toThrow(Throwable::class);
});

it('hooks both wp_enqueue_media() and Controls\Link asset registration for an Image+Link repeater', function (): void {
    \Brain\Monkey\Actions\expectAdded('customize_controls_enqueue_scripts')->once()->with('wp_enqueue_media');
    \Brain\Monkey\Actions\expectAdded('customize_controls_enqueue_scripts')
        ->once()
        ->with(Mockery::type(Closure::class));

    $field = Repeater::make('slides')->setFields([Image::make('photo'), Link::make('cta')]);

    expect(fn (): mixed => (new ReflectionMethod($field, 'afterRegister'))->invoke($field))
        ->not->toThrow(Throwable::class);
});

it('passes validation for a legacy flat-string Fields\Link sub-field row with no text', function (): void {
    $validate = Repeater::make('rows')
        ->setFields([Link::make('cta')])
        ->buildSettingArgs(new Config())['validate_callback'];

    expect($validate(true, [['cta' => 'https://example.test']]))->toBeTrue();
});

it('sanitizes a legacy flat-string Fields\Link sub-field to esc_url_raw(), not the compound shape', function (): void {
    Functions\when('esc_url_raw')->alias(static fn (string $value): string => $value . '?escaped');

    $sanitize = Repeater::make('rows')
        ->setFields([Link::make('cta')])
        ->buildSettingArgs(new Config())['sanitize_callback'];

    expect($sanitize([['cta' => 'https://legacy.example.test']]))->toBe([
        ['cta' => 'https://legacy.example.test?escaped'],
    ]);
});

it(
    'does not force-migrate an untouched legacy row when an unrelated row is saved (the save-blocking bug)',
    function (): void {
        $sanitize = Repeater::make('rows')
            ->setFields([Text::make('name'), Link::make('cta')])
            ->buildSettingArgs(new Config())['sanitize_callback'];

        $rows = [
            ['name' => 'Twitter Updated', 'cta' => 'https://legacy.example.test'],
            ['name' => 'Facebook', 'cta' => 'https://legacy.example.test/fb'],
        ];

        $sanitized = $sanitize($rows);

        expect($sanitized[0]['cta'])->toBe('https://legacy.example.test')
            ->and($sanitized[1]['cta'])->toBe('https://legacy.example.test/fb');

        $validate = Repeater::make('rows')
            ->setFields([Text::make('name'), Link::make('cta')])
            ->buildSettingArgs(new Config())['validate_callback'];

        expect($validate(true, $sanitized))->toBeTrue();
    },
);

it(
    'stays a legacy string through a second sanitize/validate cycle, not just the first (the "one-free-save" trap)',
    function (): void {
        $sanitize = Repeater::make('rows')
            ->setFields([Text::make('name'), Link::make('cta')])
            ->buildSettingArgs(new Config())['sanitize_callback'];

        $rows = [
            ['name' => 'Twitter', 'cta' => 'https://legacy.example.test'],
        ];

        $firstSave = $sanitize($rows);
        $secondSave = $sanitize($firstSave);

        expect($firstSave[0]['cta'])->toBe('https://legacy.example.test')
            ->and($secondSave[0]['cta'])->toBe('https://legacy.example.test');

        $validate = Repeater::make('rows')
            ->setFields([Text::make('name'), Link::make('cta')])
            ->buildSettingArgs(new Config())['validate_callback'];

        expect($validate(true, $secondSave))->toBeTrue();
    },
);

it('treats a repeater with no rows, in either wire format, as blank for requiredWhen', function (): void {
    $field = Repeater::make('rows')
        ->setFields([Text::make('name')])
        ->setRequiredWhen([['setting' => 'enable_rows', 'operator' => '==', 'value' => true]]);

    $validate = $field->buildSettingArgs(new Config())['validate_callback'];
    $setting = RequiredWhenSiblingSettings::build(['enable_rows' => true]);

    expect($validate(true, [], $setting)->get_error_codes())->toBe(['value_required'])
        ->and($validate(true, '', $setting)->get_error_codes())->toBe(['value_required']);
});

it(
    'treats a repeater with at least one row as satisfying requiredWhen, even with blank sub-field values',
    function (): void {
        $field = Repeater::make('rows')
            ->setFields([Text::make('name')])
            ->setRequiredWhen([['setting' => 'enable_rows', 'operator' => '==', 'value' => true]]);

        $validate = $field->buildSettingArgs(new Config())['validate_callback'];
        $setting = RequiredWhenSiblingSettings::build(['enable_rows' => true]);

        expect($validate(true, [['name' => '']], $setting))->toBeTrue();
    },
);
