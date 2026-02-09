<?php
/**
 * Ranking module.
 */

namespace Guido\SponsoredEngine\Modules;

use Guido\SponsoredEngine\Core\Registry;
use Guido\SponsoredEngine\Core\ServiceProvider;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Ranking
 *
 * Handles sponsored listing ranking and query manipulation.
 */
final class Ranking extends ServiceProvider
{
    /**
     * Default tiers and weights.
     *
     * @var array<string, array<string, float|int>>
     */
    private array $tiers = [
        'platinum' => ['priority' => 100, 'weight' => 1.0, 'boost' => 2.0],
        'diamond' => ['priority' => 90, 'weight' => 0.9, 'boost' => 1.8],
        'gold' => ['priority' => 80, 'weight' => 0.8, 'boost' => 1.5],
        'silver' => ['priority' => 70, 'weight' => 0.7, 'boost' => 1.2],
        'bronze' => ['priority' => 60, 'weight' => 0.6, 'boost' => 1.0],
        'trial' => ['priority' => 50, 'weight' => 0.5, 'boost' => 0.8],
    ];

    /**
     * Register service.
     */
    public function register(Registry $registry): void
    {
        $registry->set(self::class, $this);
    }

    /**
     * Boot module hooks.
     */
    public function boot(Registry $registry): void
    {
        add_action('pre_get_posts', [$this, 'filterQueries']);
        add_filter('posts_clauses', [$this, 'applyRankingClauses'], 10, 2);
        add_filter('the_posts', [$this, 'decoratePosts'], 10, 2);
    }

    /**
     * Detect listing post type dynamically.
     */
    public function getListingPostType(): string
    {
        $fallbacks = ['listing', 'job_listing', 'wp_listings'];
        $postType = apply_filters('guido_listing_post_type', $fallbacks[0]);

        if (!is_string($postType) || $postType === '') {
            $postType = $fallbacks[0];
        }

        return $postType;
    }

    /**
     * Ensure only front-end listing queries are affected.
     */
    private function isEligibleQuery(\WP_Query $query): bool
    {
        if (is_admin()) {
            return false;
        }

        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }

        $isElementor = (bool) $query->get('elementor_query');

        if (!$query->is_main_query() && !$isElementor) {
            return false;
        }

        $listingPostType = $this->getListingPostType();
        $postType = $query->get('post_type');

        if (empty($postType)) {
            return $query->is_post_type_archive($listingPostType) || $query->is_tax();
        }

        if (is_array($postType)) {
            return in_array($listingPostType, $postType, true);
        }

        return $postType === $listingPostType;
    }

    /**
     * Intercept eligible queries and prime meta query/order.
     */
    public function filterQueries(\WP_Query $query): void
    {
        if (!$this->isEligibleQuery($query)) {
            return;
        }

        $query->set('meta_key', '_sponsored_score');
        $query->set('orderby', [
            'meta_value_num' => 'DESC',
            'date' => 'DESC',
        ]);

        $metaQuery = (array) $query->get('meta_query');
        $metaQuery[] = [
            'key' => '_sponsored_until',
            'value' => current_time('timestamp'),
            'compare' => '>=',
            'type' => 'NUMERIC',
        ];
        $query->set('meta_query', $metaQuery);
    }

    /**
     * Apply ranking formula for SQL clauses when possible.
     *
     * @param array<string, string> $clauses
     */
    public function applyRankingClauses(array $clauses, \WP_Query $query): array
    {
        if (!$this->isEligibleQuery($query)) {
            return $clauses;
        }

        global $wpdb;

        $scoreColumn = $wpdb->postmeta . '.meta_value';

        $clauses['orderby'] = "CAST({$scoreColumn} AS SIGNED) DESC, {$wpdb->posts}.post_date DESC";

        return $clauses;
    }

    /**
     * Decorate posts with computed score values.
     *
     * @param \WP_Post[] $posts
     * @return \WP_Post[]
     */
    public function decoratePosts(array $posts, \WP_Query $query): array
    {
        if (!$this->isEligibleQuery($query)) {
            return $posts;
        }

        foreach ($posts as $post) {
            $post->guido_sponsored_score = (int) get_post_meta($post->ID, '_sponsored_score', true);
            $post->guido_sponsored_level = (string) get_post_meta($post->ID, '_sponsored_level', true);
        }

        return $posts;
    }

    /**
     * Compute a score from provided signals.
     */
    public function computeScore(float $paymentWeight, float $engagement, float $rating, float $freshness, float $manualBoost): int
    {
        $score = ($paymentWeight * 0.5) + ($engagement * 0.2) + ($rating * 0.15) + ($freshness * 0.1) + ($manualBoost * 0.05);

        return (int) round($score * 100);
    }

    /**
     * Get tier definitions.
     *
     * @return array<string, array<string, float|int>>
     */
    public function getTiers(): array
    {
        return apply_filters('guido_sponsored_tiers', $this->tiers);
    }
}
