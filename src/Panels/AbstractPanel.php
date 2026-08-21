<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Panels;

use InvalidArgumentException;
use WP_Customize_Manager;

use function Itineris\WpcBuilder\Support\label_from_id;
use function sprintf;

abstract class AbstractPanel
{
    protected string $id = '';
    protected ?string $title = null;
    protected ?string $description = null;
    protected int $priority = 160;

    private bool $registered = false;

    final public function id(): string
    {
        return $this->id;
    }

    /**
     * @return array<string, mixed>
     */
    final public function buildPanelArgs(): array
    {
        $this->title ??= label_from_id($this->id);

        return array_filter(
            [
                'title' => $this->title,
                'description' => $this->description,
                'priority' => $this->priority,
            ],
            static fn (mixed $value): bool => null !== $value && '' !== $value,
        );
    }

    /**
     * Idempotent: a second call is a no-op, matching
     * Sections\AbstractSection::register(). The README documented this for
     * both classes but only the section actually had the guard, so a panel
     * added to two Customizer instances (or a section list registered
     * twice) called add_panel() again with the same id.
     *
     * @throws InvalidArgumentException When the panel declares no $id.
     */
    final public function register(WP_Customize_Manager $customizer): void
    {
        if ($this->registered) {
            return;
        }

        if ('' === $this->id) {
            throw new InvalidArgumentException(
                sprintf('Panel %s must declare a non-empty $id.', static::class),
            );
        }

        // @phpstan-ignore argument.type
        $customizer->add_panel($this->id, $this->buildPanelArgs());

        $this->registered = true;
    }
}
