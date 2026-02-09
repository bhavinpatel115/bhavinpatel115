<?php
/**
 * WooCommerce integration module.
 */

namespace Guido\SponsoredEngine\Modules;

use Guido\SponsoredEngine\Core\Registry;
use Guido\SponsoredEngine\Core\ServiceProvider;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WooCommerce
 *
 * Handles WooCommerce order events for sponsored promotions.
 */
final class WooCommerce extends ServiceProvider
{
    /**
     * Register service.
     */
    public function register(Registry $registry): void
    {
        $registry->set(self::class, $this);
    }

    /**
     * Boot WooCommerce hooks.
     */
    public function boot(Registry $registry): void
    {
        if (!class_exists('WooCommerce')) {
            return;
        }

        add_action('woocommerce_order_status_completed', [$this, 'handleOrder']);
        add_action('woocommerce_order_status_processing', [$this, 'handleOrder']);
        add_action('woocommerce_subscription_renewal_payment_complete', [$this, 'handleRenewal'], 10, 2);
        add_filter('woocommerce_product_data_tabs', [$this, 'registerProductTab']);
        add_action('woocommerce_product_data_panels', [$this, 'renderProductPanel']);
        add_action('woocommerce_admin_process_product_object', [$this, 'saveProductMeta']);
    }

    /**
     * Handle order completion/processing.
     */
    public function handleOrder(int $orderId): void
    {
        if (!function_exists('wc_get_order')) {
            return;
        }

        $order = wc_get_order($orderId);
        if (!$order) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) {
                continue;
            }

            $listingId = (int) $product->get_meta('_promo_listing_id');
            $tier = (string) $product->get_meta('_promo_tier');
            $days = (int) $product->get_meta('_promo_days');
            $weight = (float) $product->get_meta('_promo_weight');
            $autoRenew = $product->get_meta('_promo_auto_renew') === 'yes';

            if ($listingId <= 0) {
                continue;
            }

            $this->activatePromotion($listingId, $tier, $days, $weight, $autoRenew);
        }
    }

    /**
     * Handle subscription renewal events.
     */
    public function handleRenewal($subscription, $lastOrder): void
    {
        if (!$lastOrder || !method_exists($lastOrder, 'get_items')) {
            return;
        }

        foreach ($lastOrder->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) {
                continue;
            }

            $listingId = (int) $product->get_meta('_promo_listing_id');
            $tier = (string) $product->get_meta('_promo_tier');
            $days = (int) $product->get_meta('_promo_days');
            $weight = (float) $product->get_meta('_promo_weight');

            if ($listingId <= 0) {
                continue;
            }

            $this->extendPromotion($listingId, $tier, $days, $weight);
        }
    }

    /**
     * Activate sponsorship for a listing.
     */
    private function activatePromotion(int $listingId, string $tier, int $days, float $weight, bool $autoRenew): void
    {
        $now = current_time('timestamp');
        $duration = max($days, 1) * DAY_IN_SECONDS;
        $until = $now + $duration;

        update_post_meta($listingId, '_sponsored_level', sanitize_text_field($tier));
        update_post_meta($listingId, '_sponsored_until', $until);
        update_post_meta($listingId, '_sponsored_weight', $weight);
        update_post_meta($listingId, '_sponsored_auto_renew', $autoRenew ? 'yes' : 'no');

        $score = $this->calculateScore($listingId, $weight);
        update_post_meta($listingId, '_sponsored_score', $score);

        do_action('guido_sponsored_activated', $listingId, $tier, $until, $score);
    }

    /**
     * Extend sponsorship for a listing.
     */
    private function extendPromotion(int $listingId, string $tier, int $days, float $weight): void
    {
        $existingUntil = (int) get_post_meta($listingId, '_sponsored_until', true);
        $base = max($existingUntil, current_time('timestamp'));
        $duration = max($days, 1) * DAY_IN_SECONDS;
        $until = $base + $duration;

        update_post_meta($listingId, '_sponsored_level', sanitize_text_field($tier));
        update_post_meta($listingId, '_sponsored_until', $until);
        update_post_meta($listingId, '_sponsored_weight', $weight);

        $score = $this->calculateScore($listingId, $weight);
        update_post_meta($listingId, '_sponsored_score', $score);

        do_action('guido_sponsored_extended', $listingId, $tier, $until, $score);
    }

    /**
     * Calculate score using base formula and available signals.
     */
    private function calculateScore(int $listingId, float $paymentWeight): int
    {
        $engagement = (float) get_post_meta($listingId, '_engagement_score', true);
        $rating = (float) get_post_meta($listingId, '_rating_score', true);
        $freshness = (float) get_post_meta($listingId, '_freshness_score', true);
        $manualBoost = (float) get_post_meta($listingId, '_manual_boost', true);

        $score = ($paymentWeight * 0.5) + ($engagement * 0.2) + ($rating * 0.15) + ($freshness * 0.1) + ($manualBoost * 0.05);

        return (int) round($score * 100);
    }

    /**
     * Register custom product data tab for directory promotions.
     *
     * @param array<string, array<string, mixed>> $tabs
     * @return array<string, array<string, mixed>>
     */
    public function registerProductTab(array $tabs): array
    {
        $tabs['guido_directory_promo'] = [
            'label' => __('Directory Promotion', 'guido-sponsored-engine'),
            'target' => 'guido_directory_promo_data',
            'class' => ['show_if_simple', 'show_if_subscription'],
        ];

        return $tabs;
    }

    /**
     * Render custom product data panel.
     */
    public function renderProductPanel(): void
    {
        if (!function_exists('woocommerce_wp_text_input')) {
            return;
        }

        echo '<div id="guido_directory_promo_data" class="panel woocommerce_options_panel hidden">';
        woocommerce_wp_text_input([
            'id' => '_promo_listing_id',
            'label' => __('Listing ID', 'guido-sponsored-engine'),
            'desc_tip' => true,
            'description' => __('Select listing ID for promotion (AJAX selector can be wired via JS).', 'guido-sponsored-engine'),
            'type' => 'number',
        ]);
        woocommerce_wp_select([
            'id' => '_promo_tier',
            'label' => __('Tier', 'guido-sponsored-engine'),
            'options' => [
                'platinum' => __('Platinum', 'guido-sponsored-engine'),
                'diamond' => __('Diamond', 'guido-sponsored-engine'),
                'gold' => __('Gold', 'guido-sponsored-engine'),
                'silver' => __('Silver', 'guido-sponsored-engine'),
                'bronze' => __('Bronze', 'guido-sponsored-engine'),
                'trial' => __('Trial', 'guido-sponsored-engine'),
            ],
        ]);
        woocommerce_wp_text_input([
            'id' => '_promo_days',
            'label' => __('Duration (days)', 'guido-sponsored-engine'),
            'type' => 'number',
        ]);
        woocommerce_wp_text_input([
            'id' => '_promo_weight',
            'label' => __('Priority Weight', 'guido-sponsored-engine'),
            'type' => 'number',
            'custom_attributes' => ['step' => '0.1'],
        ]);
        woocommerce_wp_checkbox([
            'id' => '_promo_auto_renew',
            'label' => __('Auto Renew', 'guido-sponsored-engine'),
        ]);
        echo '</div>';
    }

    /**
     * Save product panel meta.
     */
    public function saveProductMeta(\WC_Product $product): void
    {
        if (!current_user_can('edit_product', $product->get_id())) {
            return;
        }

        $nonce = isset($_POST['woocommerce_meta_nonce']) ? wp_unslash($_POST['woocommerce_meta_nonce']) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'woocommerce_save_data')) {
            return;
        }

        $fields = ['_promo_listing_id', '_promo_tier', '_promo_days', '_promo_weight', '_promo_auto_renew'];

        foreach ($fields as $field) {
            if (!isset($_POST[$field])) {
                if ($field === '_promo_auto_renew') {
                    $product->update_meta_data($field, 'no');
                }
                continue;
            }

            $rawValue = wc_clean(wp_unslash($_POST[$field]));
            switch ($field) {
                case '_promo_listing_id':
                case '_promo_days':
                    $value = (string) max(0, (int) $rawValue);
                    break;
                case '_promo_weight':
                    $value = (string) max(0, (float) $rawValue);
                    break;
                case '_promo_auto_renew':
                    $value = $rawValue ? 'yes' : 'no';
                    break;
                default:
                    $value = sanitize_text_field((string) $rawValue);
                    break;
            }

            $product->update_meta_data($field, $value);
        }
    }
}
