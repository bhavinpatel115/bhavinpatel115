<?php
/**
 * Admin dashboard module.
 */

namespace Guido\SponsoredEngine\Admin;

use Guido\SponsoredEngine\Core\Registry;
use Guido\SponsoredEngine\Core\ServiceProvider;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Dashboard
 *
 * Creates admin UI for the plugin.
 */
final class Dashboard extends ServiceProvider
{
    /**
     * Register service.
     */
    public function register(Registry $registry): void
    {
        $registry->set(self::class, $this);
    }

    /**
     * Boot admin hooks.
     */
    public function boot(Registry $registry): void
    {
        add_action('admin_menu', [$this, 'registerMenu']);
    }

    /**
     * Register admin menu pages.
     */
    public function registerMenu(): void
    {
        add_menu_page(
            __('Guido Ads Manager', 'guido-sponsored-engine'),
            __('Guido Ads Manager', 'guido-sponsored-engine'),
            'manage_options',
            'guido-ads-manager',
            [$this, 'renderDashboard'],
            'dashicons-megaphone',
            56
        );

        add_submenu_page(
            'guido-ads-manager',
            __('Active Promotions', 'guido-sponsored-engine'),
            __('Active Promotions', 'guido-sponsored-engine'),
            'manage_options',
            'guido-ads-promotions',
            [$this, 'renderPromotions']
        );

        add_submenu_page(
            'guido-ads-manager',
            __('Logs', 'guido-sponsored-engine'),
            __('Logs', 'guido-sponsored-engine'),
            'manage_options',
            'guido-ads-logs',
            [$this, 'renderLogs']
        );
    }

    /**
     * Render dashboard page.
     */
    public function renderDashboard(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap"><h1>' . esc_html__('Guido Ads Manager', 'guido-sponsored-engine') . '</h1>';
        echo '<p>' . esc_html__('Monitor sponsored listings, revenue, and performance metrics.', 'guido-sponsored-engine') . '</p></div>';
    }

    /**
     * Render promotions list table.
     */
    public function renderPromotions(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $table = new PromotionsTable();
        $table->prepare_items();

        echo '<div class="wrap"><h1>' . esc_html__('Active Promotions', 'guido-sponsored-engine') . '</h1>';
        $table->display();
        echo '</div>';
    }

    /**
     * Render logs page.
     */
    public function renderLogs(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $logs = (array) get_option('guido_sponsored_logs', []);

        echo '<div class="wrap"><h1>' . esc_html__('Guido Sponsored Logs', 'guido-sponsored-engine') . '</h1>';
        echo '<ul>';
        foreach (array_reverse($logs) as $log) {
            if (!is_array($log)) {
                continue;
            }
            echo '<li>' . esc_html($log['timestamp'] ?? '') . ' - ' . esc_html($log['message'] ?? '') . '</li>';
        }
        echo '</ul></div>';
    }
}
