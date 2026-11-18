<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Fields;

use InvalidArgumentException;
use WP_Post;
use WP_Query;

use function array_combine;
use function array_filter;
use function array_map;
use function ctype_digit;
use function get_the_title;

/**
 * A Select field whose choices are posts of a given post type (or all
 * post types), queried directly rather than supplied via setChoices().
 * A single select also offers `0 => 'None'` first, so a selection can be
 * cleared (see setNoneChoice()/removeNoneChoice()).
 * Calling setChoices() manually has no lasting effect: it's overwritten
 * by the query results the first time choices()/controlArgs() runs.
 */
final class PostSelect extends Select
{
    /** @var string|array<int, string> */
    private string|array $postType = 'any';

    /** @var array<string, mixed> */
    private array $queryArgs = [];

    private bool $resolved = false;

    private bool $noneEnabled = true;

    private ?string $noneLabel = null;

    private string $noneValue = '0';

    /**
     * @param string|array<int, string> $postType
     */
    public function setPostType(string|array $postType): self
    {
        $this->postType = $postType;
        $this->resolved = false;

        return $this;
    }

    /**
     * Merged under the built-in defaults (post_status, posts_per_page,
     * orderby, order) and overridden by setPostType()/the fixed 'all'
     * fields mode, so a caller-supplied post_type or fields can't break
     * the id => title contract queryChoices() depends on.
     *
     * @param array<string, mixed> $queryArgs
     */
    public function setQueryArgs(array $queryArgs): self
    {
        $this->queryArgs = $queryArgs;
        $this->resolved = false;

        return $this;
    }

    /**
     * Overrides the label and value of the "nothing selected" choice,
     * which every single-select PostSelect prepends as `0 => 'None'`.
     * A positive integer `$value` is rejected, as it could match a post ID.
     *
     * @throws InvalidArgumentException When `$value` is a positive integer.
     */
    public function setNoneChoice(?string $label = null, string $value = '0'): self
    {
        if (ctype_digit($value) && (int) $value > 0) {
            throw new InvalidArgumentException('The none value must not be a positive integer.');
        }

        $this->noneEnabled = true;
        $this->noneLabel = $label;
        $this->noneValue = $value;
        $this->resolved = false;

        return $this;
    }

    public function removeNoneChoice(): self
    {
        $this->noneEnabled = false;
        $this->resolved = false;

        return $this;
    }

    #[\Override]
    public function setMultiple(bool $multiple = true): self
    {
        parent::setMultiple($multiple);
        $this->resolved = false;

        return $this;
    }

    #[\Override]
    public function choices(): array
    {
        $this->resolveChoices();

        return parent::choices();
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    protected function controlArgs(): array
    {
        $this->resolveChoices();

        return parent::controlArgs();
    }

    /**
     * Runs the query once, regardless of whether it's triggered via
     * choices()/controlArgs() on direct registration or via
     * Repeater::controlArgs() calling a sub-field's choices() directly.
     */
    private function resolveChoices(): void
    {
        if ($this->resolved) {
            return;
        }

        $this->resolved = true;

        $choices = $this->queryChoices();

        if ($this->noneEnabled && ! $this->isMultiple()) {
            $noneLabel = $this->noneLabel ?? __('None', 'wpc-builder');
            $choices = [$this->noneValue => $noneLabel] + $choices;
        }

        $this->setChoices($choices);
    }

    /**
     * Fetches full WP_Post rows ('fields' => 'all'), not ids: WP core
     * only primes the post cache for its 'all'-shaped fields modes: the
     * 'ids' mode returns $query->posts directly without ever priming
     * anything, turning the get_the_title() call per choice into an
     * uncached query per post. update_post_term_cache/
     * update_post_meta_cache are disabled below since choices only need
     * each post's id and title, not its terms or meta.
     *
     * @return array<int, string>
     */
    private function queryChoices(): array
    {
        $args = [
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
            ...$this->queryArgs,
            'post_type' => $this->postType,
            'fields' => 'all',
        ];

        $posts = array_filter(
            new WP_Query($args)->posts ?? [],
            static fn (mixed $post): bool => $post instanceof WP_Post,
        );

        return array_combine(
            array_map(static fn (WP_Post $post): int => $post->ID, $posts),
            array_map(static fn (WP_Post $post): string => get_the_title($post), $posts),
        );
    }
}
