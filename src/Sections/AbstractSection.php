<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Sections;

use InvalidArgumentException;
use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\AbstractField;
use Itineris\WpcBuilder\Fields\Tabs;
use WP_Customize_Manager;
use WP_Customize_Section;

use function array_key_exists;
use function Itineris\WpcBuilder\Support\label_from_id;
use function sprintf;

abstract class AbstractSection
{
    protected string $id = '';
    protected ?string $title = null;
    protected ?string $description = null;
    protected ?string $panel = null;
    protected int $priority = 160;
    protected ?string $capability = null;

    private bool $registered = false;

    /**
     * @return array<int, AbstractField>
     */
    abstract protected function fields(): array;

    /**
     * Overridden by a section that groups its fields() into a tab menu.
     * A method, not a property, so a dynamic tab set works too. See
     * docs/tabs.md.
     *
     * @return array<string, string> tab id => label
     */
    protected function tabs(): array
    {
        return [];
    }

    final public function id(): string
    {
        return $this->id;
    }

    /**
     * Exposes this section's fields without triggering registration
     * against a real WP_Customize_Manager. Customizer::register() uses
     * this to collect every field's visibleWhen() AND requiredWhen()
     * conditions, independently of (and before) the section actually
     * being registered. Includes the synthetic tabs field so
     * registeredControlClasses() picks up Controls\Tabs for asset
     * gating on a site that uses tabs().
     *
     * @return array<int, AbstractField>
     */
    final public function fieldsForDependencies(): array
    {
        $fields = $this->fields();
        $tabsField = $this->tabsField(null, $fields);

        return null === $tabsField ? $fields : [$tabsField, ...$fields];
    }

    /**
     * Builds the synthetic tab-menu field, or null when tabs() is empty.
     * $fields is the caller's own already-fetched fields(), not
     * re-fetched here: fields() has no purity guarantee, so a second
     * call could diverge from what the caller actually registers.
     * $config is null from fieldsForDependencies(), which only needs
     * the control class exposed, not a real assignment map.
     *
     * @param Config|null               $config Null skips the assignment map.
     * @param array<int, AbstractField> $fields
     */
    private function tabsField(?Config $config, array $fields): ?Tabs
    {
        $tabs = $this->tabs();

        if ([] === $tabs) {
            return null;
        }

        $tabsField = Tabs::make("_wpc_builder_tabs_{$this->id}")
            ->setLabel('')
            ->setPriority(-1)
            ->setTabDefinitions($tabs);

        if (null === $config) {
            return $tabsField;
        }

        $assignments = [];

        foreach ($fields as $field) {
            $tabId = $field->tab();

            if (null !== $tabId && array_key_exists($tabId, $tabs)) {
                $assignments[$field->settingId($config)] = $tabId;
            }
        }

        return $tabsField->setAssignments($assignments);
    }

    /**
     * @return array<string, mixed>
     */
    final public function buildSectionArgs(): array
    {
        $this->title ??= label_from_id($this->id);

        return array_filter(
            [
                'title' => $this->title,
                'description' => $this->description,
                'priority' => $this->priority,
                'panel' => $this->panel,
                'capability' => $this->capability,
            ],
            static fn (mixed $value): bool => null !== $value && '' !== $value,
        );
    }

    final public function register(WP_Customize_Manager $customizer, Config $config): void
    {
        if ($this->registered) {
            return;
        }

        if ('' === $this->id) {
            throw new InvalidArgumentException(
                sprintf('Section %s must declare a non-empty $id.', static::class),
            );
        }

        $section = $customizer->get_section($this->id);
        if (! $section instanceof WP_Customize_Section) {
            // @phpstan-ignore argument.type
            $customizer->add_section($this->id, $this->buildSectionArgs());
        }

        $sectionFields = $this->fields();
        $tabsField = $this->tabsField($config, $sectionFields);
        $fields = null === $tabsField ? $sectionFields : [$tabsField, ...$sectionFields];

        foreach ($fields as $field) {
            $field->register($customizer, $this->id, $config);
        }

        $this->registered = true;
    }
}
