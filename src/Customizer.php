<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder;

use Itineris\WpcBuilder\Panels\AbstractPanel;
use Itineris\WpcBuilder\Registrars\ControlAssetRegistrar;
use Itineris\WpcBuilder\Registrars\PanelRegistrar;
use Itineris\WpcBuilder\Registrars\SectionRegistrar;
use Itineris\WpcBuilder\Sections\AbstractSection;
use Itineris\WpcBuilder\Support\Asset;
use WP_Customize_Control;
use WP_Customize_Manager;

use function Itineris\WpcBuilder\Support\collect_required_when_conditions;
use function Itineris\WpcBuilder\Support\collect_visible_when_conditions;

final class Customizer
{
    private Config $config;

    /** @var array<int, AbstractPanel> */
    private array $panels = [];

    /** @var array<int, AbstractSection> */
    private array $sections = [];

    private function __construct()
    {
        $this->config = new Config();
    }

    public static function make(): self
    {
        return new self();
    }

    public function setConfig(Config $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function config(): Config
    {
        return $this->config;
    }

    /**
     * @param array<int, class-string<AbstractPanel>|AbstractPanel> $panels
     */
    public function addPanels(array $panels): self
    {
        foreach ($panels as $panel) {
            $this->panels[] = is_string($panel) ? new $panel() : $panel;
        }

        return $this;
    }

    /**
     * @param array<int, class-string<AbstractSection>|AbstractSection> $sections
     */
    public function addSections(array $sections): self
    {
        foreach ($sections as $section) {
            $this->sections[] = is_string($section) ? new $section() : $section;
        }

        return $this;
    }

    /**
     * @return array<int, AbstractSection>
     */
    public function sections(): array
    {
        return $this->sections;
    }

    public function register(): void
    {
        $panels = new PanelRegistrar($this->panels);
        $sections = new SectionRegistrar($this->sections, $this->config);
        $assets = new ControlAssetRegistrar($this->registeredControlClasses());

        add_action('customize_controls_enqueue_scripts', [$assets, 'register']);

        add_action(
            'customize_controls_enqueue_scripts',
            function (): void {
                $this->enqueueDependencies();
            },
        );

        add_action(
            'customize_controls_enqueue_scripts',
            function (): void {
                $this->enqueueRequiredWhen();
            },
        );

        add_action(
            'customize_controls_enqueue_scripts',
            function (): void {
                $this->enqueueUrlValidation();
            },
        );

        add_action(
            'customize_register',
            function (WP_Customize_Manager $customizer) use ($panels, $sections): void {
                $panels->register($customizer);
                $sections->register($customizer);
            },
            20,
        );
    }

    /**
     * The distinct control classes this site's registered fields
     * actually use, deduplicated and in first-seen order, so a site
     * with no repeater ships no repeater.js/repeater.css. A field with
     * no CONTROL (WordPress resolves it from `type`) contributes
     * nothing. Sub-fields inside a Fields\Repeater are not walked: a
     * repeater sub-field is rendered by repeater.js, not by its own
     * control class, so pulling in its assets would reintroduce the
     * unconditional loading this method exists to avoid.
     *
     * @return array<int, class-string<WP_Customize_Control>>
     */
    private function registeredControlClasses(): array
    {
        /** @var array<class-string<WP_Customize_Control>, true> $classes */
        $classes = [];

        foreach ($this->sections as $section) {
            foreach ($section->fieldsForDependencies() as $field) {
                $controlClass = $field->controlClass();

                if (null === $controlClass) {
                    continue;
                }

                $classes[$controlClass] = true;
            }
        }

        return array_keys($classes);
    }

    /**
     * Enqueues the JS dependency (conditional visibility) engine and
     * localises it with every registered field's visibleWhen()
     * conditions. Skipped entirely when no field declares any
     * conditions, or when the package is installed outside
     * WP_CONTENT_DIR. See Asset::url()'s own docblock; that call
     * announces the skip itself via _doing_it_wrong(), so this guard
     * stays a plain skip.
     */
    private function enqueueDependencies(): void
    {
        $payload = $this->dependenciesPayload();

        if ([] === $payload) {
            return;
        }

        $src = Asset::url('dist/js/dependencies.js');

        if ('' === $src) {
            return;
        }

        $version = Asset::version('dist/js/dependencies.js');

        wp_enqueue_script(
            'wpc-builder-dependencies',
            $src,
            ['customize-controls'],
            null === $version ? null : (string) $version,
            true,
        );

        wp_localize_script('wpc-builder-dependencies', 'wpcBuilderVisibilityDependencies', $payload);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function dependenciesPayload(): array
    {
        $fields = [];

        foreach ($this->sections as $section) {
            foreach ($section->fieldsForDependencies() as $field) {
                $fields[] = $field;
            }
        }

        return collect_visible_when_conditions($fields, $this->config);
    }

    /**
     * Enqueues the client-side required-when hint engine (a visual/
     * accessibility indicator only; server-side enforcement is
     * AbstractField::buildSettingArgs()'s composed validate_callback,
     * see docs/conditionally-required.md) and localises it with every
     * registered field's requiredWhen() conditions. Skipped entirely
     * when no field declares any conditions, mirroring
     * enqueueDependencies()'s own skip.
     */
    private function enqueueRequiredWhen(): void
    {
        $payload = $this->requiredWhenPayload();

        if ([] === $payload) {
            return;
        }

        $src = Asset::url('dist/js/required-when.js');

        if ('' === $src) {
            return;
        }

        $version = Asset::version('dist/js/required-when.js');

        wp_enqueue_script(
            'wpc-builder-required-when',
            $src,
            ['customize-controls'],
            null === $version ? null : (string) $version,
            true,
        );

        wp_localize_script('wpc-builder-required-when', 'wpcBuilderRequiredWhenDependencies', $payload);

        $styleSrc = Asset::url('dist/css/required-when.css');

        if ('' === $styleSrc) {
            return;
        }

        $styleVersion = Asset::version('dist/css/required-when.css');

        wp_enqueue_style(
            'wpc-builder-required-when',
            $styleSrc,
            [],
            null === $styleVersion ? null : (string) $styleVersion,
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function requiredWhenPayload(): array
    {
        $fields = [];

        foreach ($this->sections as $section) {
            foreach ($section->fieldsForDependencies() as $field) {
                $fields[] = $field;
            }
        }

        return collect_required_when_conditions($fields, $this->config);
    }

    private function enqueueUrlValidation(): void
    {
        $validationSrc = Asset::url('dist/js/url-validation.js');
        $validationVersion = Asset::version('dist/js/url-validation.js');

        if ('' === $validationSrc) {
            return;
        }

        wp_enqueue_script(
            'wpc-builder-url-validation-core',
            $validationSrc,
            ['customize-controls', 'wp-i18n'],
            null === $validationVersion ? null : (string) $validationVersion,
            true,
        );

        // The live list is filterable (kses_allowed_protocols), so the
        // client rule must use it rather than a hard-coded copy.
        wp_add_inline_script(
            'wpc-builder-url-validation-core',
            'window.wpcBuilderUrlValidationSettings = '
                . wp_json_encode(['allowedProtocols' => wp_allowed_protocols()]) . ';',
            'before',
        );

        $src = Asset::url('dist/js/url.js');

        if ('' === $src) {
            return;
        }

        $version = Asset::version('dist/js/url.js');

        wp_enqueue_script(
            'wpc-builder-url-validation',
            $src,
            ['customize-controls', 'wpc-builder-url-validation-core'],
            null === $version ? null : (string) $version,
            true,
        );
    }
}
