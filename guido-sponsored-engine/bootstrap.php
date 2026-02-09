<?php
/**
 * Bootstrap file for Guido Smart Sponsored Listings Pro.
 */

namespace Guido\SponsoredEngine;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class Bootstrap
 *
 * Handles initialization and autoloading for the plugin.
 */
final class Bootstrap
{
    /**
     * Initialize the plugin.
     */
    public static function init(): void
    {
        self::registerAutoloader();

        add_action('init', [self::class, 'loadTextdomain']);

        $kernel = new Core\Kernel();
        $kernel->boot();
    }

    /**
     * Load plugin translations.
     */
    public static function loadTextdomain(): void
    {
        load_plugin_textdomain('guido-sponsored-engine', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Activation hook.
     */
    public static function activate(): void
    {
        self::registerAutoloader();

        if (class_exists(Modules\Expiry::class)) {
            Modules\Expiry::scheduleEvents();
        }
    }

    /**
     * Deactivation hook.
     */
    public static function deactivate(): void
    {
        self::registerAutoloader();

        if (class_exists(Modules\Expiry::class)) {
            Modules\Expiry::clearEvents();
        }
    }

    /**
     * Register PSR-4 compatible autoloader for this plugin.
     */
    private static function registerAutoloader(): void
    {
        spl_autoload_register(static function (string $class): void {
            $prefix = __NAMESPACE__ . '\\';
            $baseDir = __DIR__ . '/';

            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        });
    }
}
