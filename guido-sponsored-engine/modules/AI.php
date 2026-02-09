<?php
/**
 * AI module.
 */

namespace Guido\SponsoredEngine\Modules;

use Guido\SponsoredEngine\Core\Registry;
use Guido\SponsoredEngine\Core\ServiceProvider;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AI
 *
 * Provides hooks for AI optimization without hard dependencies.
 */
final class AI extends ServiceProvider
{
    /**
     * Register service.
     */
    public function register(Registry $registry): void
    {
        $registry->set(self::class, $this);
    }

    /**
     * Boot AI hooks.
     */
    public function boot(Registry $registry): void
    {
        add_action('guido_sponsored_ai_optimize', [$this, 'dispatchOptimization']);
    }

    /**
     * Dispatch AI optimization event.
     */
    public function dispatchOptimization(): void
    {
        do_action('guido_ai_optimize');
    }
}
