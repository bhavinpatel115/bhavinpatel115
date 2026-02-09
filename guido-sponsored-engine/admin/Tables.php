<?php
/**
 * Admin tables.
 */

namespace Guido\SponsoredEngine\Admin;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class PromotionsTable
 *
 * Provides a list table for sponsored promotions.
 */
final class PromotionsTable extends \WP_List_Table
{
    /**
     * Prepare items.
     */
    public function prepare_items(): void
    {
        $this->items = $this->getPromotions();
        $this->_column_headers = [$this->get_columns(), [], []];
    }

    /**
     * Define columns.
     *
     * @return array<string, string>
     */
    public function get_columns(): array
    {
        return [
            'listing_id' => __('Listing ID', 'guido-sponsored-engine'),
            'tier' => __('Tier', 'guido-sponsored-engine'),
            'expires' => __('Expires', 'guido-sponsored-engine'),
            'score' => __('Score', 'guido-sponsored-engine'),
        ];
    }

    /**
     * Render column data.
     *
     * @param array<string, mixed> $item
     */
    public function column_default($item, $column_name): string
    {
        return isset($item[$column_name]) ? esc_html((string) $item[$column_name]) : '';
    }

    /**
     * Load promotions data.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getPromotions(): array
    {
        $events = (array) get_option('guido_sponsored_events', []);
        $promotions = [];

        foreach (array_reverse($events) as $event) {
            if (!is_array($event)) {
                continue;
            }

            $promotions[] = [
                'listing_id' => $event['listing_id'] ?? 0,
                'tier' => $event['tier'] ?? '-',
                'expires' => isset($event['until']) ? date_i18n('Y-m-d', (int) $event['until']) : '-',
                'score' => $event['score'] ?? 0,
            ];
        }

        return $promotions;
    }
}
