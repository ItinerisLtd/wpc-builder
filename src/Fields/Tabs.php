<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use Itineris\WpcBuilder\Controls\Tabs as TabsControl;

/**
 * Built internally by AbstractSection when a section overrides tabs();
 * not meant to be added to a fields() array directly.
 */
final class Tabs extends AbstractField
{
    protected const string CONTROL_TYPE = 'wpc-builder-tabs';
    protected const CONTROL = TabsControl::class;

    /** @var array<string, string> tab id => label */
    private array $tabDefinitions = [];

    /** @var array<string, string> setting id => tab id */
    private array $assignments = [];

    /**
     * @param array<string, string> $tabs Tab id => label.
     */
    public function setTabDefinitions(array $tabs): self
    {
        $this->tabDefinitions = $tabs;

        return $this;
    }

    /**
     * @param array<string, string> $assignments Setting id => tab id.
     */
    public function setAssignments(array $assignments): self
    {
        $this->assignments = $assignments;

        return $this;
    }

    /**
     * Stores nothing; see Fields\Custom for why a display-only control
     * must not register a setting.
     */
    protected function registersSetting(): bool
    {
        return false;
    }

    protected function defaultSanitizeCallback(): string
    {
        return '__return_null';
    }

    /**
     * @return array<string, mixed>
     */
    protected function controlArgs(): array
    {
        return [
            'tabs' => $this->tabDefinitions,
            'assignments' => $this->assignments,
        ];
    }
}
