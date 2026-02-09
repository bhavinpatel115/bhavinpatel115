<?php
/**
 * Settings module.
 */

namespace Guido\SponsoredEngine\Admin;

use Guido\SponsoredEngine\Core\Registry;
use Guido\SponsoredEngine\Core\ServiceProvider;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Settings
 *
 * Registers settings for the plugin.
 */
final class Settings extends ServiceProvider
{
    /**
     * Register service.
     */
    public function register(Registry $registry): void
    {
        $registry->set(self::class, $this);
    }

    /**
     * Boot settings hooks.
     */
    public function boot(Registry $registry): void
    {
        add_action('admin_init', [$this, 'registerSettings']);
    }

    /**
     * Register settings fields.
     */
    public function registerSettings(): void
    {
        register_setting('guido_sponsored_settings', 'guido_sponsored_debug');

        add_settings_section(
            'guido_sponsored_general',
            __('General Settings', 'guido-sponsored-engine'),
            '__return_false',
            'guido_sponsored_settings'
        );

        add_settings_field(
            'guido_sponsored_debug',
            __('Enable Debug Mode', 'guido-sponsored-engine'),
            [$this, 'renderDebugField'],
            'guido_sponsored_settings',
            'guido_sponsored_general'
        );
    }

    /**
     * Render debug checkbox.
     */
    public function renderDebugField(): void
    {
        $value = (bool) get_option('guido_sponsored_debug', false);
        echo '<input type="checkbox" name="guido_sponsored_debug" value="1" ' . checked($value, true, false) . ' />';
    }
}
