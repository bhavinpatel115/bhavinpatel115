<?php
/**
 * REST API module.
 */

namespace Guido\SponsoredEngine\Api;

use Guido\SponsoredEngine\Core\Registry;
use Guido\SponsoredEngine\Core\ServiceProvider;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Rest
 *
 * Registers REST API endpoints for the sponsored engine.
 */
final class Rest extends ServiceProvider
{
    /**
     * Register service.
     */
    public function register(Registry $registry): void
    {
        $registry->set(self::class, $this);
    }

    /**
     * Boot REST hooks.
     */
    public function boot(Registry $registry): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    /**
     * Register REST routes.
     */
    public function registerRoutes(): void
    {
        register_rest_route('guido-ads/v1', '/status', [
            'methods' => 'GET',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'status'],
        ]);

        register_rest_route('guido-ads/v1', '/activate', [
            'methods' => 'POST',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'activate'],
        ]);

        register_rest_route('guido-ads/v1', '/renew', [
            'methods' => 'POST',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'renew'],
        ]);

        register_rest_route('guido-ads/v1', '/analytics', [
            'methods' => 'GET',
            'permission_callback' => [$this, 'authorizeRequest'],
            'callback' => [$this, 'analytics'],
        ]);
    }

    /**
     * Permission callback for API requests.
     */
    public function authorizeRequest(\WP_REST_Request $request): bool
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        $nonce = $request->get_header('X-WP-Nonce');
        if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
            return false;
        }

        return $this->checkRateLimit(get_current_user_id());
    }

    /**
     * Basic rate limiting via transients.
     */
    private function checkRateLimit(int $userId): bool
    {
        $key = 'guido_api_rate_' . $userId;
        $count = (int) get_transient($key);

        if ($count > 100) {
            return false;
        }

        set_transient($key, $count + 1, MINUTE_IN_SECONDS);

        return true;
    }

    /**
     * Status endpoint.
     */
    public function status(): \WP_REST_Response
    {
        return rest_ensure_response([
            'status' => 'ok',
            'timestamp' => current_time('mysql'),
        ]);
    }

    /**
     * Activate endpoint.
     */
    public function activate(\WP_REST_Request $request): \WP_REST_Response
    {
        $listingId = (int) $request->get_param('listing_id');
        $tier = sanitize_text_field((string) $request->get_param('tier'));

        if ($listingId <= 0 || $tier === '') {
            return new \WP_REST_Response(['error' => 'Invalid payload'], 400);
        }

        update_post_meta($listingId, '_sponsored_level', $tier);
        update_post_meta($listingId, '_sponsored_until', current_time('timestamp') + DAY_IN_SECONDS);

        return rest_ensure_response(['success' => true]);
    }

    /**
     * Renew endpoint.
     */
    public function renew(\WP_REST_Request $request): \WP_REST_Response
    {
        $listingId = (int) $request->get_param('listing_id');
        $days = (int) $request->get_param('days');

        if ($listingId <= 0) {
            return new \WP_REST_Response(['error' => 'Invalid listing'], 400);
        }

        $until = (int) get_post_meta($listingId, '_sponsored_until', true);
        $until = max($until, current_time('timestamp')) + max($days, 1) * DAY_IN_SECONDS;
        update_post_meta($listingId, '_sponsored_until', $until);

        return rest_ensure_response(['success' => true, 'until' => $until]);
    }

    /**
     * Analytics endpoint.
     */
    public function analytics(): \WP_REST_Response
    {
        $events = (array) get_option('guido_sponsored_events', []);

        return rest_ensure_response([
            'count' => count($events),
            'events' => array_slice($events, -50),
        ]);
    }
}
