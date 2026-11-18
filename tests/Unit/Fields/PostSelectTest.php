<?php

declare(strict_types=1);

use Brain\Monkey;
use Brain\Monkey\Functions;
use Itineris\WpcBuilder\Fields\PostSelect;
use Itineris\WpcBuilder\Fields\Select;

require_once __DIR__ . '/../../Fixtures/wp-post-double.php';

beforeEach(function (): void {
    Monkey\Functions\stubTranslationFunctions();
});

it('is a Select, so Repeater still recognises multiple-select sub-fields', function (): void {
    expect(PostSelect::make('featured'))->toBeInstanceOf(Select::class);
});

it('defaults post_type to any and locks post_type/fields regardless of query args', function (): void {
    Mockery::mock('overload:' . WP_Query::class)
        ->shouldReceive('__construct')
        ->once()
        ->with(Mockery::on(
            static function (array $args): bool {
                expect($args['post_type'])->toBe('any')
                    ->and($args['fields'])->toBe('all')
                    ->and($args['post_status'])->toBe('publish')
                    ->and($args['posts_per_page'])->toBe(-1)
                    ->and($args['orderby'])->toBe('title')
                    ->and($args['order'])->toBe('ASC');

                return true;
            },
        ))
        ->andSet('posts', []);

    PostSelect::make('featured')->buildControlArgs('footer');
});

it('uses setPostType() and merges setQueryArgs() under the built-in defaults', function (): void {
    Mockery::mock('overload:' . WP_Query::class)
        ->shouldReceive('__construct')
        ->once()
        ->with(Mockery::on(
            static function (array $args): bool {
                expect($args['post_type'])->toBe('event')
                    ->and($args['post_status'])->toBe('any')
                    ->and($args['posts_per_page'])->toBe(5)
                    ->and($args['fields'])->toBe('all');

                return true;
            },
        ))
        ->andSet('posts', []);

    PostSelect::make('featured')
        ->setPostType('event')
        ->setQueryArgs(['post_status' => 'any', 'posts_per_page' => 5])
        ->buildControlArgs('footer');
});

it('cannot have post_type/fields overridden by setQueryArgs()', function (): void {
    Mockery::mock('overload:' . WP_Query::class)
        ->shouldReceive('__construct')
        ->once()
        ->with(Mockery::on(
            static function (array $args): bool {
                expect($args['post_type'])->toBe('page')
                    ->and($args['fields'])->toBe('all');

                return true;
            },
        ))
        ->andSet('posts', []);

    PostSelect::make('featured')
        ->setPostType('page')
        ->setQueryArgs(['post_type' => 'post', 'fields' => 'ids'])
        ->buildControlArgs('footer');
});

it('builds string-keyed id => title choices from the queried posts', function (): void {
    Mockery::mock('overload:' . WP_Query::class)
        ->shouldReceive('__construct')
        ->once()
        ->andSet('posts', [new WP_Post(3), new WP_Post(1)]);

    Functions\when('get_the_title')->alias(static fn (WP_Post $post): string => "Title {$post->ID}");

    $args = PostSelect::make('featured')->buildControlArgs('footer');

    expect($args['choices'])->toBe([
        0 => 'None',
        3 => 'Title 3',
        1 => 'Title 1',
    ]);
});

it('drops any non-WP_Post entry a filtered query might return', function (): void {
    Mockery::mock('overload:' . WP_Query::class)
        ->shouldReceive('__construct')
        ->once()
        ->andSet('posts', [new WP_Post(3), 4, 'not-a-post']);

    Functions\when('get_the_title')->alias(static fn (WP_Post $post): string => "Title {$post->ID}");

    $args = PostSelect::make('featured')->buildControlArgs('footer');

    expect($args['choices'])->toBe([0 => 'None', 3 => 'Title 3']);
});

it('runs the query only once, even across repeated choices()/controlArgs() calls', function (): void {
    Mockery::mock('overload:' . WP_Query::class)
        ->shouldReceive('__construct')
        ->once()
        ->andSet('posts', [new WP_Post(1)]);

    Functions\when('get_the_title')->justReturn('Title');

    $field = PostSelect::make('featured');

    $field->choices();
    $field->buildControlArgs('footer');

    expect($field->choices())->toBe([0 => 'None', 1 => 'Title']);
});

it('is what Repeater reads directly via choices(), bypassing controlArgs()', function (): void {
    Mockery::mock('overload:' . WP_Query::class)
        ->shouldReceive('__construct')
        ->once()
        ->andSet('posts', [new WP_Post(7)]);

    Functions\when('get_the_title')->justReturn('Seven');

    expect(PostSelect::make('featured')->choices())->toBe([0 => 'None', 7 => 'Seven']);
});

it('prepends a none choice ahead of the queried posts by default', function (): void {
    Mockery::mock('overload:' . WP_Query::class)
        ->shouldReceive('__construct')
        ->once()
        ->andSet('posts', [new WP_Post(3)]);

    Functions\when('get_the_title')->alias(static fn (WP_Post $post): string => "Title {$post->ID}");

    $args = PostSelect::make('featured')->buildControlArgs('footer');

    expect($args['choices'])->toBe([
        0 => 'None',
        3 => 'Title 3',
    ]);
});

it('overrides the none label and value, and still yields it for an empty query', function (): void {
    Mockery::mock('overload:' . WP_Query::class)
        ->shouldReceive('__construct')
        ->once()
        ->andSet('posts', []);

    $choices = PostSelect::make('featured')->setNoneChoice('Default template', 'default')->choices();

    expect($choices)->toBe(['default' => 'Default template']);
});

it('omits the none choice for multiple selects', function (): void {
    Mockery::mock('overload:' . WP_Query::class)
        ->shouldReceive('__construct')
        ->once()
        ->andSet('posts', [new WP_Post(3)]);

    Functions\when('get_the_title')->justReturn('Title');

    $choices = PostSelect::make('featured')->setMultiple()->choices();

    expect($choices)->toBe([3 => 'Title']);
});

it('rejects a positive integer none value, as it could match a post ID', function (string $value): void {
    PostSelect::make('featured')->setNoneChoice('None', $value);
})->with(['1', '42'])->throws(InvalidArgumentException::class);

it('accepts zero and non-numeric none values', function (string $value): void {
    expect(PostSelect::make('featured')->setNoneChoice('None', $value))->toBeInstanceOf(PostSelect::class);
})->with(['0', 'default']);

it('re-resolves choices when setMultiple() is called after they were resolved', function (): void {
    Mockery::mock('overload:' . WP_Query::class)
        ->shouldReceive('__construct')
        ->atLeast()->once()
        ->andSet('posts', [new WP_Post(3)]);

    Functions\when('get_the_title')->justReturn('Title');

    $field = PostSelect::make('featured');

    expect($field->choices())->toBe([0 => 'None', 3 => 'Title']);

    $field->setMultiple();

    expect($field->choices())->toBe([3 => 'Title']);
});

it('drops the none choice with removeNoneChoice()', function (): void {
    Mockery::mock('overload:' . WP_Query::class)
        ->shouldReceive('__construct')
        ->once()
        ->andSet('posts', [new WP_Post(3)]);

    Functions\when('get_the_title')->justReturn('Title');

    expect(PostSelect::make('featured')->removeNoneChoice()->choices())->toBe([3 => 'Title']);
});
