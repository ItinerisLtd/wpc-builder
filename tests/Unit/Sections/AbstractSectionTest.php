<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Tests\Fixtures\Sections\CoreSectionFixture;
use Itineris\WpcBuilder\Tests\Fixtures\Sections\EmptyIdSectionFixture;
use Itineris\WpcBuilder\Tests\Fixtures\Sections\FooterSectionFixture;
use Itineris\WpcBuilder\Tests\Fixtures\Sections\NoTitleSectionFixture;

it('builds section args', function (): void {
    expect((new FooterSectionFixture())->buildSectionArgs())->toBe([
        'title' => 'Footer',
        'description' => 'Footer settings',
        'priority' => 160,
    ]);
});

it('defaults the title from the section id when none is set', function (): void {
    expect((new NoTitleSectionFixture())->buildSectionArgs())->toBe([
        'title' => 'Brand settings',
        'priority' => 160,
    ]);
});

it('exposes its id', function (): void {
    expect((new FooterSectionFixture())->id())->toBe('footer');
});

it('adds the section and registers its fields against the customizer', function (): void {
    $section = new FooterSectionFixture();
    $config = new Config();
    $customizer = Mockery::mock('WP_Customize_Manager');

    $customizer->shouldReceive('get_section')
        ->once()
        ->with('footer')
        ->andReturn(null);

    $customizer->shouldReceive('add_section')
        ->once()
        ->with('footer', $section->buildSectionArgs());

    $customizer->shouldReceive('add_setting')->once();
    $customizer->shouldReceive('add_control')->once();

    expect(fn (): mixed => $section->register($customizer, $config))->not->toThrow(Throwable::class);
});

it('skips add_section() when section already exists but still registers its fields', function (): void {
    $section = new CoreSectionFixture();
    $config = new Config();
    $customizer = Mockery::mock('WP_Customize_Manager');

    $customizer->shouldReceive('get_section')
        ->once()
        ->with('title_tagline')
        ->andReturn(Mockery::mock('WP_Customize_Section'));

    $customizer->shouldNotReceive('add_section');
    $customizer->shouldReceive('add_setting')->once();
    $customizer->shouldReceive('add_control')->once();

    expect(fn (): mixed => $section->register($customizer, $config))->not->toThrow(Throwable::class);
});

it('is idempotent: a second register() call does not repeat any WordPress calls', function (): void {
    $section = new FooterSectionFixture();
    $config = new Config();
    $customizer = Mockery::mock('WP_Customize_Manager');

    $customizer->shouldReceive('get_section')
        ->once()
        ->with('footer')
        ->andReturn(null);

    $customizer->shouldReceive('add_section')->once();
    $customizer->shouldReceive('add_setting')->once();
    $customizer->shouldReceive('add_control')->once();

    $section->register($customizer, $config);

    expect(fn (): mixed => $section->register($customizer, $config))->not->toThrow(Throwable::class);
});

it('exposes its fields for the dependency engine without registering them', function (): void {
    $section = new FooterSectionFixture();

    $fields = $section->fieldsForDependencies();

    expect($fields)->toHaveCount(1)
        ->and($fields[0])->toBeInstanceOf(\Itineris\WpcBuilder\Fields\AbstractField::class)
        ->and($fields[0]->id)->toBe('footer_copyright');
});

it('throws when registering a section with no id', function (): void {
    $section = new EmptyIdSectionFixture();
    $config = new Config();
    $customizer = Mockery::mock('WP_Customize_Manager');

    expect(fn (): mixed => $section->register($customizer, $config))
        ->toThrow(InvalidArgumentException::class, 'must declare a non-empty $id');
});
