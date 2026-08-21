<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Sections;

use InvalidArgumentException;
use Itineris\WpcBuilder\Config;
use Itineris\WpcBuilder\Fields\AbstractField;
use WP_Customize_Manager;
use WP_Customize_Section;

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

    final public function id(): string
    {
        return $this->id;
    }

    /**
     * Exposes this section's fields without triggering registration
     * against a real WP_Customize_Manager. Customizer::register() uses
     * this to collect every field's visibleWhen() AND requiredWhen()
     * conditions for their respective JS payloads, independently of
     * (and before) the section actually being registered.
     *
     * @return array<int, AbstractField>
     */
    final public function fieldsForDependencies(): array
    {
        return $this->fields();
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

        foreach ($this->fields() as $field) {
            $field->register($customizer, $this->id, $config);
        }

        $this->registered = true;
    }
}
