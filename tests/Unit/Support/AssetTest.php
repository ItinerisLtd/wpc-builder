<?php

declare(strict_types=1);

use Brain\Monkey\Functions;
use Itineris\WpcBuilder\Support\Asset;

/**
 * Asset::url() derives every URL from the package's own filesystem
 * position relative to WP_CONTENT_DIR, paired with content_url(). That
 * is layout-independent, unlike the theme-relative resolution it
 * replaced. See its own docblock. WP_CONTENT_DIR is a constant, so
 * it's injected through the $contentDir seam rather than stubbed;
 * content_url() is a function and IS stubbed.
 */
$stubContentUrl = function (string $uri): void {
    Functions\when('content_url')->justReturn($uri);

    Functions\when('esc_html')->returnArg();
};

it('builds a URL for a package installed under a SAGE 9 theme, outside the theme directory', function () use (
    $stubContentUrl,
): void {
    $stubContentUrl('https://example.test/app');

    $themeDir = '/srv/site/web/app/themes/example-theme/resources';
    $packageRoot = '/srv/site/web/app/themes/example-theme/vendor/itinerisltd/wpc-builder';

    expect(str_starts_with($packageRoot, $themeDir . '/'))->toBeFalse();

    $url = Asset::url(
        'dist/css/controls.css',
        $packageRoot,
        '/srv/site/web/app',
    );

    expect($url)->toBe(
        'https://example.test/app/themes/example-theme/vendor/itinerisltd/wpc-builder'
            . '/dist/css/controls.css',
    );
});

it('builds a URL for a package installed in a PLUGIN or mu-plugin', function () use ($stubContentUrl): void {
    $stubContentUrl('https://example.test/app');

    expect(Asset::url(
        'dist/js/repeater.js',
        '/srv/site/web/app/plugins/acme-settings/vendor/itinerisltd/wpc-builder',
        '/srv/site/web/app',
    ))->toBe(
        'https://example.test/app/plugins/acme-settings/vendor/itinerisltd/wpc-builder'
            . '/dist/js/repeater.js',
    );

    expect(Asset::url(
        'dist/js/repeater.js',
        '/srv/site/web/app/mu-plugins/acme-settings/vendor/itinerisltd/wpc-builder',
        '/srv/site/web/app',
    ))->toBe(
        'https://example.test/app/mu-plugins/acme-settings/vendor/itinerisltd/wpc-builder'
            . '/dist/js/repeater.js',
    );
});

it('builds a URL on a non-Bedrock wp-content layout', function () use ($stubContentUrl): void {
    $stubContentUrl('https://example.test/wp-content');

    $url = Asset::url(
        'dist/css/toggle.css',
        '/var/www/html/wp-content/themes/acme/vendor/itinerisltd/wpc-builder',
        '/var/www/html/wp-content',
    );

    expect($url)->toBe(
        'https://example.test/wp-content/themes/acme/vendor/itinerisltd/wpc-builder'
            . '/dist/css/toggle.css',
    );
});

it('normalises trailing slashes on both the content directory and content_url()', function () use (
    $stubContentUrl,
): void {
    $stubContentUrl('https://example.test/app/');

    $url = Asset::url(
        'dist/css/toggle.css',
        '/srv/site/web/app/themes/acme/vendor/itinerisltd/wpc-builder/',
        '/srv/site/web/app/',
    );

    expect($url)->toBe(
        'https://example.test/app/themes/acme/vendor/itinerisltd/wpc-builder'
            . '/dist/css/toggle.css',
    );
});

it('returns an empty string when the package is genuinely outside WP_CONTENT_DIR', function () use (
    $stubContentUrl,
): void {
    $stubContentUrl('https://example.test/app');
    Functions\expect('_doing_it_wrong')->once();

    $url = Asset::url(
        'dist/css/toggle.css',
        '/srv/site/vendor/itinerisltd/wpc-builder',
        '/srv/site/web/app',
    );

    expect($url)->toBe('');
});

it('returns an empty string, and says so, when WP_CONTENT_DIR is unavailable', function () use (
    $stubContentUrl,
): void {
    $stubContentUrl('https://example.test/app');
    Functions\expect('_doing_it_wrong')->once();

    expect(Asset::url(
        'dist/css/toggle.css',
        '/srv/site/web/app/themes/acme/vendor/itinerisltd/wpc-builder',
        '',
    ))->toBe('');
});

it('announces the unresolvable install rather than failing silently', function () use ($stubContentUrl): void {
    $stubContentUrl('https://example.test/app');

    Functions\expect('_doing_it_wrong')
        ->once()
        ->with(
            'Itineris\WpcBuilder\Support\Asset::url',
            Mockery::on(static fn (string $message): bool => str_contains(
                $message,
                '/srv/site/vendor/itinerisltd/wpc-builder',
            ) && str_contains($message, '/srv/site/web/app')),
            '',
        );

    $url = Asset::url(
        'dist/css/toggle.css',
        '/srv/site/vendor/itinerisltd/wpc-builder',
        '/srv/site/web/app',
    );

    expect($url)->toBe('');
});

it(
    'resolves a symlinked content directory so a package genuinely inside it is still recognised',
    function () use ($stubContentUrl): void {
        $base = sys_get_temp_dir() . '/asset-url-test-' . uniqid('', true);
        $realContent = $base . '/releases/20260802010203/web/app';
        $packageRoot = $realContent . '/themes/acme/vendor/itinerisltd/wpc-builder';
        $symlinkedContent = $base . '/current-app';

        mkdir($packageRoot, 0755, true);

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
        set_error_handler(static fn (): bool => true);
        $symlinked = symlink($realContent, $symlinkedContent);
        restore_error_handler();

        $cleanUp = static function () use ($base, $packageRoot, $symlinkedContent): void {
            if (is_link($symlinkedContent)) {
                unlink($symlinkedContent);
            }

            $dir = $packageRoot;

            while (str_starts_with($dir, $base)) {
                if (is_dir($dir)) {
                    rmdir($dir);
                }

                $dir = dirname($dir);
            }
        };

        if (! $symlinked) {
            $cleanUp();

            test()->markTestSkipped('This platform cannot create symlinks.');
        }

        try {
            $stubContentUrl('https://example.test/app');

            $url = Asset::url('dist/css/toggle.css', $packageRoot, $symlinkedContent);

            expect($url)->toBe(
                'https://example.test/app/themes/acme/vendor/itinerisltd/wpc-builder'
                    . '/dist/css/toggle.css',
            );
        } finally {
            $cleanUp();
        }
    },
);

it('falls back to WP_CONTENT_DIR when no content directory is passed', function () use ($stubContentUrl): void {
    $stubContentUrl('https://example.test/app');

    $url = Asset::url('dist/css/controls.css');

    expect($url)->toStartWith('https://example.test/app/')
        ->and($url)->toEndWith('/dist/css/controls.css');
});

it('resolves a filesystem path for a file inside the package, defaulting to packageRoot()', function (): void {
    expect(Asset::path('dist/css/controls.css'))
        ->toBe(Asset::packageRoot() . '/dist/css/controls.css');
});

it('resolves a filesystem path using an explicit packageRoot override', function (): void {
    expect(Asset::path('dist/js', '/srv/site/web/app/themes/acme/vendor/itinerisltd/wpc-builder'))
        ->toBe('/srv/site/web/app/themes/acme/vendor/itinerisltd/wpc-builder/dist/js');
});

it('trims a trailing slash on an explicit packageRoot before resolving a path', function (): void {
    expect(Asset::path('dist/js', '/srv/site/web/app/themes/acme/vendor/itinerisltd/wpc-builder/'))
        ->toBe('/srv/site/web/app/themes/acme/vendor/itinerisltd/wpc-builder/dist/js');
});
