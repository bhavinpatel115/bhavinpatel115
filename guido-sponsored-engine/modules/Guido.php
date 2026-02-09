<?php
/**
 * Guido theme integration module.
 */

namespace Guido\SponsoredEngine\Modules;

use Guido\SponsoredEngine\Core\Registry;
use Guido\SponsoredEngine\Core\ServiceProvider;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Guido
 *
 * Handles theme-level presentation for sponsored listings.
 */
final class Guido extends ServiceProvider
{
    /**
     * Register service.
     */
    public function register(Registry $registry): void
    {
        $registry->set(self::class, $this);
    }

    /**
     * Boot integration hooks.
     */
    public function boot(Registry $registry): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('guido_before_listing_loop', [$this, 'openSponsoredWrapper']);
        add_action('guido_after_listing_loop', [$this, 'closeSponsoredWrapper']);
        add_filter('the_content', [$this, 'appendSponsoredBadge']);
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueueAssets(): void
    {
        wp_enqueue_style(
            'guido-sponsored-engine',
            plugins_url('../assets/css/sponsored.css', __FILE__),
            [],
            '1.0.0'
        );
    }

    /**
     * Open wrapper around listing loop.
     */
    public function openSponsoredWrapper(): void
    {
        echo '<div class="guido-sponsored-loop" data-sponsored="1">';
    }

    /**
     * Close wrapper around listing loop.
     */
    public function closeSponsoredWrapper(): void
    {
        echo '</div>';
    }

    /**
     * Append sponsored badge to listing content when appropriate.
     */
    public function appendSponsoredBadge(string $content): string
    {
        if (!is_singular()) {
            return $content;
        }

        $level = (string) get_post_meta(get_the_ID(), '_sponsored_level', true);
        $until = (int) get_post_meta(get_the_ID(), '_sponsored_until', true);

        if ($level === '') {
            return $content;
        }

        $remaining = max($until - current_time('timestamp'), 0);
        $badge = sprintf(
            '<div class="guido-sponsored-badge"><span>%s</span><strong>%s</strong></div>',
            esc_html__('Sponsored', 'guido-sponsored-engine'),
            esc_html(ucfirst($level))
        );

        $timer = sprintf(
            '<div class="guido-sponsored-timer" data-remaining="%d">%s</div>',
            $remaining,
            esc_html__('Promo active', 'guido-sponsored-engine')
        );

        return $badge . $timer . $content;
    }
}
