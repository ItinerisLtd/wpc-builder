<?php

declare(strict_types=1);

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Customizer;
use Itineris\WpcBuilder\Enums\OptionType;
use Itineris\WpcBuilder\Tests\Fixtures\Panels\FooterPanelFixture;
use Itineris\WpcBuilder\Tests\Fixtures\Sections\FooterSectionFixture;
use Itineris\WpcBuilder\Tests\Fixtures\Sections\RequiredWhenFieldSectionFixture;
use Itineris\WpcBuilder\Tests\Fixtures\Sections\SectionInPanelFixture;
use Itineris\WpcBuilder\Tests\Fixtures\Sections\ToggleSectionFixture;
use Itineris\WpcBuilder\Tests\Fixtures\Sections\VisibleWhenFieldSectionFixture;
use Itineris\WpcBuilder\Tests\Fixtures\Support\HasAssetsScanner;

it('accepts section class names and instantiates them', function (): void {
    $customizer = Customizer::make()->addSections([FooterSectionFixture::class]);

    expect($customizer->sections())->toHaveCount(1)
        ->and($customizer->sections()[0])->toBeInstanceOf(FooterSectionFixture::class);
});

it('accepts panel class names and already-instantiated panels', function (): void {
    $instance = new FooterPanelFixture();

    $customizer = Customizer::make()->addPanels([FooterPanelFixture::class, $instance]);

    $panels = (new ReflectionProperty($customizer, 'panels'))->getValue($customizer);

    expect($panels)->toHaveCount(2)
        ->and($panels[0])->toBeInstanceOf(FooterPanelFixture::class)
        ->and($panels[1])->toBe($instance);
});

it('defaults to a theme_mod config', function (): void {
    expect(Customizer::make()->config()->optionType)->toBe(OptionType::THEME_MOD);
});

it('replaces the default config through setConfig()', function (): void {
    $config = new Config(optionType: OptionType::OPTION, optionName: 'my_prefix');

    $customizer = Customizer::make()->setConfig($config);

    expect($customizer->config())->toBe($config)
        ->and($customizer->config()->optionName)->toBe('my_prefix');
});

it('passes its config down to the settings it actually registers', function (): void {
    $registered = null;

    Actions\expectAdded('customize_register')->once()->whenHappen(
        function (callable $callback) use (&$registered): void {
            $registered = $callback;
        },
    );

    Customizer::make()
        ->setConfig(new Config(
            capability: 'manage_options',
            optionType: OptionType::OPTION,
            optionName: 'my_prefix',
        ))
        ->addSections([FooterSectionFixture::class])
        ->register();

    expect($registered)->toBeCallable();

    $settings = [];

    $manager = Mockery::mock('WP_Customize_Manager');
    $manager->shouldReceive('get_section')->andReturn(null);
    $manager->shouldReceive('add_section');
    $manager->shouldReceive('add_control');
    $manager->shouldReceive('add_setting')->andReturnUsing(
        function (string $settingId, array $args) use (&$settings): void {
            $settings[$settingId] = $args;
        },
    );

    ($registered)($manager);

    expect(array_keys($settings))->toBe(['my_prefix[footer_copyright]'])
        ->and($settings['my_prefix[footer_copyright]']['type'])->toBe('option')
        ->and($settings['my_prefix[footer_copyright]']['capability'])->toBe('manage_options');
});

it('hooks registration onto customize_register at priority 20', function (): void {
    Actions\expectAdded('customize_register')->once()->with(
        Mockery::type('callable'),
        20,
    );

    expect(fn (): mixed => Customizer::make()
        ->addSections([FooterSectionFixture::class])
        ->register())->not->toThrow(Throwable::class);
});

it('hooks the control asset registrar onto customize_controls_enqueue_scripts', function (): void {
    Actions\expectAdded('customize_controls_enqueue_scripts')->times(4)->with(
        Mockery::type('callable'),
    );

    expect(fn (): mixed => Customizer::make()
        ->addSections([FooterSectionFixture::class])
        ->register())->not->toThrow(Throwable::class);
});

it('enqueues and localises the dependency script for a field with visible-when conditions', function (): void {
    Functions\when('content_url')->justReturn('https://example.test/app');
    Functions\when('wp_allowed_protocols')->justReturn(['http', 'https', 'tel']);
    Functions\when('wp_json_encode')->alias('json_encode');

    $capturedCallbacks = [];
    $enqueuedScripts = [];
    $localisedScripts = [];
    $inlineScripts = [];

    Actions\expectAdded('customize_controls_enqueue_scripts')->times(4)->whenHappen(
        function (callable $callback) use (&$capturedCallbacks): void {
            $capturedCallbacks[] = $callback;
        },
    );

    Customizer::make()
        ->addSections([VisibleWhenFieldSectionFixture::class])
        ->register();

    Functions\when('wp_enqueue_script')->alias(
        function (
            string $handle,
            string $src,
            array $dependencies,
            mixed $version,
            bool $inFooter
        ) use (&$enqueuedScripts): void {
            $enqueuedScripts[] = [
                'handle' => $handle,
                'src' => $src,
                'dependencies' => $dependencies,
                'version' => $version,
                'inFooter' => $inFooter,
            ];
        },
    );

    Functions\when('wp_localize_script')->alias(
        function (string $handle, string $name, array $payload) use (&$localisedScripts): void {
            $localisedScripts[] = [
                'handle' => $handle,
                'name' => $name,
                'payload' => $payload,
            ];
        },
    );

    Functions\when('wp_add_inline_script')->alias(
        function (string $handle, string $data, string $position) use (&$inlineScripts): void {
            $inlineScripts[] = [
                'handle' => $handle,
                'data' => $data,
                'position' => $position,
            ];
        },
    );

    foreach ($capturedCallbacks as $callback) {
        if ($callback instanceof Closure) {
            $callback();
        }
    }

    $dependencyScripts = array_values(array_filter(
        $enqueuedScripts,
        static fn (array $item): bool => 'wpc-builder-dependencies' === $item['handle'],
    ));

    expect($dependencyScripts)->toHaveCount(1)
        ->and($dependencyScripts[0]['src'])->toBeString()
        ->and($dependencyScripts[0]['dependencies'])->toBe(['customize-controls'])
        ->and($dependencyScripts[0]['inFooter'])->toBeTrue();

    $urlValidationCoreScripts = array_values(array_filter(
        $enqueuedScripts,
        static fn (array $item): bool => 'wpc-builder-url-validation-core' === $item['handle'],
    ));

    expect($urlValidationCoreScripts)->toHaveCount(1)
        ->and($urlValidationCoreScripts[0]['src'])->toBeString()
        ->and($urlValidationCoreScripts[0]['dependencies'])->toBe(['customize-controls', 'wp-i18n'])
        ->and($urlValidationCoreScripts[0]['inFooter'])->toBeTrue();

    $urlValidationScripts = array_values(array_filter(
        $enqueuedScripts,
        static fn (array $item): bool => 'wpc-builder-url-validation' === $item['handle'],
    ));

    expect($urlValidationScripts)->toHaveCount(1)
        ->and($urlValidationScripts[0]['src'])->toBeString()
        ->and($urlValidationScripts[0]['dependencies'])->toBe([
            'customize-controls',
            'wpc-builder-url-validation-core',
        ])
        ->and($urlValidationScripts[0]['inFooter'])->toBeTrue();

    $dependencyLocalised = array_values(array_filter(
        $localisedScripts,
        static fn (array $item): bool => 'wpcBuilderVisibilityDependencies' === $item['name'],
    ));

    expect($dependencyLocalised)->toHaveCount(1)
        ->and($dependencyLocalised[0])->toBe([
            'handle' => 'wpc-builder-dependencies',
            'name' => 'wpcBuilderVisibilityDependencies',
            'payload' => [
                'alert_text' => [
                    ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
                ],
            ],
        ]);

    expect($inlineScripts)->toHaveCount(1)
        ->and($inlineScripts[0]['handle'])->toBe('wpc-builder-url-validation-core')
        ->and($inlineScripts[0]['position'])->toBe('before')
        ->and($inlineScripts[0]['data'])->toBe(
            'window.wpcBuilderUrlValidationSettings = {"allowedProtocols":["http","https","tel"]};',
        );

    expect(array_column($enqueuedScripts, 'handle'))->not->toContain('wpc-builder-required-when');
});

it(
    'enqueues and localises the required-when script and style for a field with required-when conditions',
    function (): void {
        Functions\when('content_url')->justReturn('https://example.test/app');
        Functions\when('wp_allowed_protocols')->justReturn(['http', 'https', 'tel']);
        Functions\when('wp_json_encode')->alias('json_encode');

        $capturedCallbacks = [];
        $enqueuedScripts = [];
        $enqueuedStyles = [];
        $localisedScripts = [];

        Actions\expectAdded('customize_controls_enqueue_scripts')->times(4)->whenHappen(
            function (callable $callback) use (&$capturedCallbacks): void {
                $capturedCallbacks[] = $callback;
            },
        );

        Customizer::make()
            ->addSections([RequiredWhenFieldSectionFixture::class])
            ->register();

        Functions\when('wp_enqueue_script')->alias(
            function (
                string $handle,
                string $src,
                array $dependencies,
                mixed $version,
                bool $inFooter
            ) use (&$enqueuedScripts): void {
                $enqueuedScripts[] = [
                    'handle' => $handle,
                    'src' => $src,
                    'dependencies' => $dependencies,
                    'version' => $version,
                    'inFooter' => $inFooter,
                ];
            },
        );

        Functions\when('wp_enqueue_style')->alias(
            function (string $handle, string $src, array $dependencies, mixed $version) use (&$enqueuedStyles): void {
                $enqueuedStyles[] = [
                    'handle' => $handle,
                    'src' => $src,
                    'dependencies' => $dependencies,
                    'version' => $version,
                ];
            },
        );

        Functions\when('wp_localize_script')->alias(
            function (string $handle, string $name, array $payload) use (&$localisedScripts): void {
                $localisedScripts[] = [
                    'handle' => $handle,
                    'name' => $name,
                    'payload' => $payload,
                ];
            },
        );

        Functions\when('wp_add_inline_script')->justReturn(true);

        foreach ($capturedCallbacks as $callback) {
            if ($callback instanceof Closure) {
                $callback();
            }
        }

        $requiredWhenScripts = array_values(array_filter(
            $enqueuedScripts,
            static fn (array $item): bool => 'wpc-builder-required-when' === $item['handle'],
        ));

        expect($requiredWhenScripts)->toHaveCount(1)
            ->and($requiredWhenScripts[0]['src'])->toBeString()
            ->and($requiredWhenScripts[0]['dependencies'])->toBe(['customize-controls'])
            ->and($requiredWhenScripts[0]['inFooter'])->toBeTrue();

        $requiredWhenStyles = array_values(array_filter(
            $enqueuedStyles,
            static fn (array $item): bool => 'wpc-builder-required-when' === $item['handle'],
        ));

        expect($requiredWhenStyles)->toHaveCount(1)
            ->and($requiredWhenStyles[0]['src'])->toBeString();

        $requiredWhenLocalised = array_values(array_filter(
            $localisedScripts,
            static fn (array $item): bool => 'wpcBuilderRequiredWhenDependencies' === $item['name'],
        ));

        expect($requiredWhenLocalised)->toHaveCount(1)
            ->and($requiredWhenLocalised[0])->toBe([
                'handle' => 'wpc-builder-required-when',
                'name' => 'wpcBuilderRequiredWhenDependencies',
                'payload' => [
                    'alert_text' => [
                        ['setting' => 'alert_enabled', 'operator' => '==', 'value' => true],
                    ],
                ],
            ]);

        expect(array_column($enqueuedScripts, 'handle'))->not->toContain('wpc-builder-dependencies');
    },
);

it('does not enqueue the dependency script when no field declares visible-when conditions', function (): void {
    Functions\when('content_url')->justReturn('https://example.test/app');
    Functions\when('wp_allowed_protocols')->justReturn(['http', 'https', 'tel']);
    Functions\when('wp_json_encode')->alias('json_encode');

    $capturedCallbacks = [];
    $enqueuedScripts = [];
    $localisedScripts = [];
    $inlineScripts = [];

    Actions\expectAdded('customize_controls_enqueue_scripts')->times(4)->whenHappen(
        function (callable $callback) use (&$capturedCallbacks): void {
            $capturedCallbacks[] = $callback;
        },
    );

    Customizer::make()
        ->addSections([FooterSectionFixture::class])
        ->register();

    Functions\when('wp_enqueue_script')->alias(
        function (
            string $handle,
            string $src,
            array $dependencies,
            mixed $version,
            bool $inFooter
        ) use (&$enqueuedScripts): void {
            $enqueuedScripts[] = [
                'handle' => $handle,
                'src' => $src,
                'dependencies' => $dependencies,
                'version' => $version,
                'inFooter' => $inFooter,
            ];
        },
    );

    Functions\when('wp_localize_script')->alias(
        function (string $handle, string $name, array $payload) use (&$localisedScripts): void {
            $localisedScripts[] = [
                'handle' => $handle,
                'name' => $name,
                'payload' => $payload,
            ];
        },
    );

    Functions\when('wp_add_inline_script')->alias(
        function (string $handle, string $data, string $position) use (&$inlineScripts): void {
            $inlineScripts[] = [
                'handle' => $handle,
                'data' => $data,
                'position' => $position,
            ];
        },
    );

    foreach ($capturedCallbacks as $callback) {
        if ($callback instanceof Closure) {
            $callback();
        }
    }

    expect(array_column($enqueuedScripts, 'handle'))->not->toContain('wpc-builder-dependencies')
        ->not->toContain('wpc-builder-required-when')
        ->toContain('wpc-builder-url-validation-core')
        ->toContain('wpc-builder-url-validation')
        ->and($localisedScripts)->toHaveCount(0)
        ->and(array_column($inlineScripts, 'handle'))->toContain('wpc-builder-url-validation-core');
});

it('leaves every HasAssets control reachable from at least one field, no more and no less', function (): void {
    $controlFiles = glob(__DIR__ . '/../../src/Controls/*.php');
    $fieldFiles = glob(__DIR__ . '/../../src/Fields/*.php');

    expect($controlFiles)->not->toBeEmpty()
        ->and($fieldFiles)->not->toBeEmpty();

    $hasAssetsClasses = [];

    foreach ($controlFiles as $file) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,Generic.PHP.ForbiddenFunctions.Found
        $source = file_get_contents($file);

        foreach (HasAssetsScanner::classNames($source) as $className) {
            $hasAssetsClasses[] = 'Itineris\\WpcBuilder\\Controls\\' . $className;
        }
    }

    $controlsNamespace = 'Itineris\\WpcBuilder\\Controls\\';
    $reachableClasses = [];
    $foreignClasses = [];

    foreach ($fieldFiles as $file) {
        $fieldClass = 'Itineris\\WpcBuilder\\Fields\\' . basename($file, '.php');

        if (! class_exists($fieldClass) || (new ReflectionClass($fieldClass))->isAbstract()) {
            continue;
        }

        $controlClass = (new ReflectionClassConstant($fieldClass, 'CONTROL'))->getValue();

        if (null === $controlClass) {
            continue;
        }

        if (! str_starts_with($controlClass, $controlsNamespace)) {
            $foreignClasses[$controlClass] = true;

            continue;
        }

        $reachableClasses[$controlClass] = true;
    }

    expect(array_keys($foreignClasses))->toBe(['WP_Customize_Color_Control']);

    $reachableClasses = array_keys($reachableClasses);

    sort($hasAssetsClasses);
    sort($reachableClasses);

    expect($reachableClasses)->toBe($hasAssetsClasses);
});

it('derives the enqueued control list from the registered fields, not from a fixed list', function (): void {
    $derive = static fn (Customizer $customizer): array => (
        new ReflectionMethod($customizer, 'registeredControlClasses')
    )->invoke($customizer);

    expect($derive(Customizer::make()->addSections([FooterSectionFixture::class])))->toBe([]);

    expect($derive(Customizer::make()->addSections([ToggleSectionFixture::class])))
        ->toBe([Itineris\WpcBuilder\Controls\Toggle::class]);

    expect($derive(Customizer::make()->addSections([ToggleSectionFixture::class])))
        ->not->toContain(Itineris\WpcBuilder\Controls\Repeater::class);
});

it('deduplicates a control used by more than one registered field', function (): void {
    $derive = static fn (Customizer $customizer): array => (
        new ReflectionMethod($customizer, 'registeredControlClasses')
    )->invoke($customizer);

    expect($derive(
        Customizer::make()->addSections([ToggleSectionFixture::class, ToggleSectionFixture::class]),
    ))->toBe([Itineris\WpcBuilder\Controls\Toggle::class]);
});

it('registers panels before sections, so a section naming a panel is not silently dropped', function (): void {
    $capturedCallback = null;

    Actions\expectAdded('customize_register')->once()->whenHappen(
        function (callable $callback) use (&$capturedCallback): void {
            $capturedCallback = $callback;
        },
    );

    Customizer::make()
        ->addPanels([FooterPanelFixture::class])
        ->addSections([SectionInPanelFixture::class])
        ->register();

    expect($capturedCallback)->toBeCallable();

    $customizer = Mockery::mock('WP_Customize_Manager');

    $customizer->shouldReceive('add_panel')->once()->ordered();
    $customizer->shouldReceive('get_section')->once()->ordered()->andReturn(null);
    $customizer->shouldReceive('add_section')->once()->ordered();
    $customizer->shouldReceive('add_setting')->once();
    $customizer->shouldReceive('add_control')->once();

    expect(fn (): mixed => $capturedCallback($customizer))->not->toThrow(Throwable::class);
});
