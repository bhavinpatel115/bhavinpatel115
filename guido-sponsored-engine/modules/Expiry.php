<?php
/**
 * Expiry module.
 */

namespace Guido\SponsoredEngine\Modules;

use Guido\SponsoredEngine\Core\Registry;
use Guido\SponsoredEngine\Core\ServiceProvider;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Expiry
 *
 * Handles expiry, renewal, and cleanup via WP-Cron.
 */
final class Expiry extends ServiceProvider
{
    private const DAILY_HOOK = 'guido_sponsored_daily_scan';
    private const HOURLY_HOOK = 'guido_sponsored_hourly_validation';

    /**
     * Schedule cron events on activation.
     */
    public static function scheduleEvents(): void
    {
        if (!wp_next_scheduled(self::DAILY_HOOK)) {
            wp_schedule_event(time(), 'daily', self::DAILY_HOOK);
        }

        if (!wp_next_scheduled(self::HOURLY_HOOK)) {
            wp_schedule_event(time(), 'hourly', self::HOURLY_HOOK);
        }
    }

    /**
     * Clear cron events on deactivation.
     */
    public static function clearEvents(): void
    {
        foreach ([self::DAILY_HOOK, self::HOURLY_HOOK] as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
        }
    }

    /**
     * Register service.
     */
    public function register(Registry $registry): void
    {
        $registry->set(self::class, $this);
    }

    /**
     * Boot cron scheduling and hooks.
     */
    public function boot(Registry $registry): void
    {
        add_action('init', [$this, 'schedule']);
        add_action(self::DAILY_HOOK, [$this, 'runDailyScan']);
        add_action(self::HOURLY_HOOK, [$this, 'runHourlyValidation']);
    }

    /**
     * Schedule cron events.
     */
    public function schedule(): void
    {
        self::scheduleEvents();
    }

    /**
     * Daily scan for expired promotions.
     */
    public function runDailyScan(): void
    {
        $this->processExpiredListings('daily');
    }

    /**
     * Hourly validation for promotions nearing expiry.
     */
    public function runHourlyValidation(): void
    {
        $this->processExpiredListings('hourly');
    }

    /**
     * Process expired listings with grace period and logging.
     */
    private function processExpiredListings(string $context): void
    {
        $listingType = apply_filters('guido_listing_post_type', 'listing');
        $now = current_time('timestamp');
        $gracePeriod = (int) apply_filters('guido_sponsored_grace_period', DAY_IN_SECONDS);

        $query = new \WP_Query([
            'post_type' => $listingType,
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'meta_query' => [
                [
                    'key' => '_sponsored_until',
                    'value' => $now - $gracePeriod,
                    'compare' => '<',
                    'type' => 'NUMERIC',
                ],
            ],
            'fields' => 'ids',
        ]);

        if (!$query->have_posts()) {
            return;
        }

        foreach ($query->posts as $listingId) {
            $this->expireListing((int) $listingId, $context);
        }
    }

    /**
     * Expire a listing sponsorship.
     */
    private function expireListing(int $listingId, string $context): void
    {
        delete_post_meta($listingId, '_sponsored_level');
        delete_post_meta($listingId, '_sponsored_score');
        delete_post_meta($listingId, '_sponsored_weight');

        do_action('guido_sponsored_expired', $listingId, $context);

        $this->logFailure("Expired listing {$listingId} via {$context} scan.");
    }

    /**
     * Log failures or actions to option storage.
     */
    private function logFailure(string $message): void
    {
        $logs = (array) get_option('guido_sponsored_logs', []);
        $logs[] = [
            'timestamp' => current_time('mysql'),
            'message' => sanitize_text_field($message),
        ];

        update_option('guido_sponsored_logs', array_slice($logs, -200));
    }
}
