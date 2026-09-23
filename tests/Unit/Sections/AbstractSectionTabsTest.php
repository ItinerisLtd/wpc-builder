<?php

declare(strict_types=1);

use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Controls\Tabs as TabsControl;
use Itineris\WpcBuilder\Fields\Tabs;
use Itineris\WpcBuilder\Tests\Fixtures\Sections\FooterSectionFixture;
use Itineris\WpcBuilder\Tests\Fixtures\Sections\TabsSectionFixture;

it('does not inject a tabs field for a section that never overrides tabs()', function (): void {
    expect((new FooterSectionFixture())->fieldsForDependencies())->toHaveCount(1);
});

it('prepends a synthetic tabs field to fieldsForDependencies(), with no assignments yet', function (): void {
    $fields = (new TabsSectionFixture())->fieldsForDependencies();

    expect($fields)->toHaveCount(5)
        ->and($fields[0])->toBeInstanceOf(Tabs::class);

    $args = $fields[0]->buildControlArgs('header');

    expect($args['tabs'])->toBe(['global' => 'Global', 'landing' => 'Landing'])
        ->and($args['assignments'])->toBe([]);
});

it('suppresses the synthetic tabs field label, so no id-derived title renders over the menu', function (): void {
    $fields = (new TabsSectionFixture())->fieldsForDependencies();

    expect($fields[0]->buildControlArgs('header')['label'])->toBe('');
});

it('renders the tabs field ahead of its default-priority siblings', function (): void {
    $fields = (new TabsSectionFixture())->fieldsForDependencies();

    expect($fields[0]->buildControlArgs('header')['priority'])->toBeLessThan(
        $fields[1]->buildControlArgs('header')['priority'],
    );
});

it('computes settingId => tabId assignments on register(), omitting untabbed/unmatched-tab fields', function (): void {
    $section = new TabsSectionFixture();
    $config = new Config();
    $customizer = Mockery::mock('WP_Customize_Manager');

    $customizer->shouldReceive('get_section')->once()->with('header')->andReturn(null);
    $customizer->shouldReceive('add_section')->once();
    $customizer->shouldReceive('add_setting')->times(4);

    $tabsControl = null;

    $customizer->shouldReceive('add_control')
        ->times(5)
        ->andReturnUsing(function (mixed $arg) use (&$tabsControl): void {
            if ($arg instanceof TabsControl) {
                $tabsControl = $arg;
            }
        });

    $section->register($customizer, $config);

    expect($tabsControl)->toBeInstanceOf(TabsControl::class)
        ->and($tabsControl->tabs)->toBe(['global' => 'Global', 'landing' => 'Landing'])
        ->and($tabsControl->assignments)->toBe([
            'header_global_text' => 'global',
            'header_landing_text' => 'landing',
        ]);
});
