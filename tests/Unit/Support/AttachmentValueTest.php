<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use Itineris\WpcBuilder\Enums\SaveAs;
use Itineris\WpcBuilder\Support\AttachmentValue;

beforeEach(function (): void {
    Functions\when('sanitize_text_field')->returnArg();
    Functions\when('attachment_url_to_postid')->justReturn(42);
    Functions\when('wp_get_attachment_url')->justReturn('https://example.test/img.jpg');
    Functions\when('get_attached_file')->justReturn('/srv/uploads/img.jpg');
});

it('saves an attachment id from a numeric value', function (): void {
    expect(AttachmentValue::sanitize('42', SaveAs::ID))->toBe(42)
        ->and(AttachmentValue::sanitize(42, SaveAs::ID))->toBe(42);
});

it('resolves an id from a url', function (): void {
    expect(AttachmentValue::sanitize('https://example.test/img.jpg', SaveAs::ID))->toBe(42);
});

it('reads the id out of an array value', function (): void {
    expect(AttachmentValue::sanitize(['id' => '42'], SaveAs::ID))->toBe(42);
});

it('empties to 0 without looking up an attachment', function (): void {
    expect(AttachmentValue::sanitize('', SaveAs::ID))->toBe(0);
});

it('escapes a url string when saving as url', function (): void {
    Functions\expect('esc_url_raw')
        ->once()
        ->with('https://example.test/img.jpg')
        ->andReturnFirstArg();

    expect(AttachmentValue::sanitize('https://example.test/img.jpg', SaveAs::URL))
        ->toBe('https://example.test/img.jpg');
});

it('converts a numeric value to a url when saving as url', function (): void {
    expect(AttachmentValue::sanitize(42, SaveAs::URL))->toBe('https://example.test/img.jpg');
});

it('reads the url out of an array value', function (): void {
    Functions\expect('esc_url_raw')->never();

    expect(AttachmentValue::sanitize(['url' => 'https://example.test/img.jpg'], SaveAs::URL))
        ->toBe('https://example.test/img.jpg');
});

it('builds the three-key array shape', function (): void {
    expect(AttachmentValue::sanitize(42, SaveAs::ARRAY))->toBe([
        'id' => 42,
        'url' => 'https://example.test/img.jpg',
        'filename' => 'img.jpg',
    ]);
});

it('preserves an already-shaped array', function (): void {
    Functions\when('esc_url_raw')->returnArg();

    $value = ['id' => '42', 'url' => 'https://example.test/img.jpg', 'filename' => 'img.jpg'];

    expect(AttachmentValue::sanitize($value, SaveAs::ARRAY))->toBe([
        'id' => 42,
        'url' => 'https://example.test/img.jpg',
        'filename' => 'img.jpg',
    ]);
});

it('returns empty strings for missing array keys', function (): void {
    expect(AttachmentValue::sanitize(['id' => '', 'url' => '', 'filename' => ''], SaveAs::ARRAY))->toBe([
        'id' => '',
        'url' => '',
        'filename' => '',
    ]);
});
