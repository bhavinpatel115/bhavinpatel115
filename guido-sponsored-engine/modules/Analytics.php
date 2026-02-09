<?php
/**
 * Analytics module.
 */

namespace Guido\SponsoredEngine\Modules;

use Guido\SponsoredEngine\Core\Registry;
use Guido\SponsoredEngine\Core\ServiceProvider;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Analytics
 *
 * Collects analytics and provides reporting hooks.
 */
final class Analytics extends ServiceProvider
{
    /**
     * Register service.
     */
    public function register(Registry $registry): void
    {
        $registry->set(self::class, $this);
    }

    /**
     * Boot analytics hooks.
     */
    public function boot(Registry $registry): void
    {
        add_action('guido_sponsored_activated', [$this, 'trackActivation'], 10, 4);
        add_action('guido_sponsored_extended', [$this, 'trackExtension'], 10, 4);
        add_action('guido_sponsored_expired', [$this, 'trackExpiry'], 10, 2);
    }

    /**
     * Track activation event.
     */
    public function trackActivation(int $listingId, string $tier, int $until, int $score): void
    {
        $this->appendEvent('activation', $listingId, $tier, $until, $score);
    }

    /**
     * Track extension event.
     */
    public function trackExtension(int $listingId, string $tier, int $until, int $score): void
    {
        $this->appendEvent('extension', $listingId, $tier, $until, $score);
    }

    /**
     * Track expiry event.
     */
    public function trackExpiry(int $listingId, string $context): void
    {
        $this->appendEvent('expiry', $listingId, $context, 0, 0);
    }

    /**
     * Append event to analytics store.
     */
    private function appendEvent(string $type, int $listingId, string $tier, int $until, int $score): void
    {
        $events = (array) get_option('guido_sponsored_events', []);
        $events[] = [
            'timestamp' => current_time('mysql'),
            'type' => sanitize_text_field($type),
            'listing_id' => $listingId,
            'tier' => sanitize_text_field($tier),
            'until' => $until,
            'score' => $score,
        ];

        update_option('guido_sponsored_events', array_slice($events, -500));
    }
}
