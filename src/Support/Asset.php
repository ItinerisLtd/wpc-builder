<?php

declare(strict_types=1);

namespace Itineris\WpcBuilder\Support;

use function _doing_it_wrong;
use function content_url;
use function defined;
use function dirname;
use function esc_html;
use function file_exists;
use function filemtime;
use function realpath;
use function rtrim;
use function str_starts_with;
use function strlen;
use function substr;

final class Asset
{
    public static function packageRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * The URL for a file inside this package, or '' when the package sits
     * outside WP_CONTENT_DIR and therefore has no addressable URL at all.
     *
     * Resolved from the package's own filesystem position relative to
     * WP_CONTENT_DIR, paired with content_url(), rather than
     * get_stylesheet_directory()/get_template_directory(), which assumes
     * the package sits under the theme directory. That's true on Sage 10
     * but false on Sage 9 (theme dir is `themes/<t>/resources`, Composer
     * installs to `themes/<t>/vendor/…`, a sibling not a descendant), so
     * that comparison used to silently ship no CSS/JS at all on Sage 9.
     * WP_CONTENT_DIR is layout-independent: Bedrock, non-Bedrock, a child
     * theme, a plugin and an mu-plugin all sit somewhere underneath it.
     *
     * Both sides are normalised through realpath() before comparing (see
     * resolve()), since __DIR__ is symlink-resolved by PHP but
     * WP_CONTENT_DIR may not be. A Trellis releases/+current deploy needs
     * this.
     *
     * The '' return is kept rather than falling back to a best guess:
     * callers (Customizer::enqueueDependencies(),
     * Registrars\ControlAssetRegistrar) skip an empty src. See
     * announceUnresolvable() below for why this is no longer silent.
     *
     * @param string      $relativePath Path inside this package, e.g.
     *                                  'dist/css/controls.css'.
     * @param string|null $packageRoot Test seam; defaults to packageRoot().
     * @param string|null $contentDir  Test seam; defaults to WP_CONTENT_DIR
     *                                 (a constant, so it can't be stubbed
     *                                 the way a function can).
     */
    public static function url(
        string $relativePath,
        ?string $packageRoot = null,
        ?string $contentDir = null
    ): string {
        $packageRoot = self::normalisedPackageRoot($packageRoot);
        $contentDir = rtrim($contentDir ?? self::contentDir(), '/');

        if ('' === $contentDir) {
            self::announceUnresolvable($relativePath, $packageRoot, '(WP_CONTENT_DIR is not defined)');

            return '';
        }

        $suffix = self::suffixWithin(self::resolve($packageRoot), $contentDir);

        if (null === $suffix) {
            self::announceUnresolvable($relativePath, $packageRoot, $contentDir);

            return '';
        }

        return rtrim(content_url(), '/') . $suffix . '/' . $relativePath;
    }

    /**
     * WP_CONTENT_DIR as a string, or '' when not defined, meaning this
     * package is being exercised outside a booted WordPress (its own
     * tests do exactly that, via the $contentDir seam), not on a real but
     * broken site.
     */
    private static function contentDir(): string
    {
        if (! defined('WP_CONTENT_DIR')) {
            return '';
        }

        return WP_CONTENT_DIR;
    }

    /**
     * Announces a mis-installed package: an empty src otherwise makes
     * wp_enqueue_style()/wp_enqueue_script() emit no tag at all, so
     * nothing 404s, errors, or gets logged. Uses _doing_it_wrong() rather
     * than error_log(), since this is a caller/installation mistake, not
     * an environment condition (see resolve() below). Not deduplicated,
     * since a broken install only emits a handful of lines, once.
     */
    private static function announceUnresolvable(
        string $relativePath,
        string $packageRoot,
        string $contentDir
    ): void {
        _doing_it_wrong(
            'Itineris\WpcBuilder\Support\Asset::url',
            'itinerisltd/wpc-builder is installed at ' . esc_html($packageRoot)
            . ', which is outside WP_CONTENT_DIR (' . esc_html($contentDir)
            . '). No URL can be derived for ' . esc_html($relativePath)
            . ', so this asset is not enqueued and the control that needs it will not work. '
            . 'Install the package somewhere under WP_CONTENT_DIR: a theme, a child theme, a '
            . 'plugin or an mu-plugin all qualify.',
            '',
        );
    }

    private static function suffixWithin(string $resolvedPackageRoot, string $root): ?string
    {
        $resolvedRoot = self::resolve(rtrim($root, '/'));

        if (! str_starts_with($resolvedPackageRoot, $resolvedRoot . '/')) {
            return null;
        }

        return substr($resolvedPackageRoot, strlen($resolvedRoot));
    }

    /**
     * Resolve a path through symlinks, falling back to the raw path when
     * realpath() returns false. That fallback is correct for a
     * non-existent path, but realpath() also fails when a parent
     * directory lacks traverse permission (open_basedir, a restrictive
     * PHP-FPM pool user), which silently undoes the symlink normalisation
     * this method exists for. So the fallback announces itself via
     * error_log() (an environment condition, not a caller mistake), only
     * for a path that actually exists: file_exists() succeeding while
     * realpath() fails is precisely the permission case.
     */
    private static function resolve(string $path): string
    {
        $resolved = realpath($path);

        if (false !== $resolved) {
            return $resolved;
        }

        if (file_exists($path)) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log(
                'itinerisltd/wpc-builder: realpath() failed for an existing path ('
                . $path
                . '). Symlink normalisation is disabled for it, so control assets may fail to '
                . 'resolve on a symlinked deploy. Check open_basedir and directory traverse '
                . 'permissions for every parent of this path.',
            );
        }

        return $path;
    }

    /**
     * The absolute filesystem path for a file or directory inside this
     * package, e.g. 'dist/css/controls.css' or 'dist/js'. Unlike url(),
     * this needs no WP_CONTENT_DIR/symlink resolution; that machinery
     * exists only because a URL must be derived from content_url(), a
     * concern that doesn't apply to a plain filesystem path.
     */
    public static function path(string $relativePath, ?string $packageRoot = null): string
    {
        return self::normalisedPackageRoot($packageRoot) . '/' . $relativePath;
    }

    private static function normalisedPackageRoot(?string $packageRoot): string
    {
        return rtrim($packageRoot ?? self::packageRoot(), '/');
    }

    public static function version(string $relativePath, ?string $packageRoot = null): ?int
    {
        $path = self::path($relativePath, $packageRoot);

        if (! file_exists($path)) {
            return null;
        }

        $mtime = filemtime($path);

        return false === $mtime ? null : $mtime;
    }
}
