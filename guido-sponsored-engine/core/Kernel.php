<?php
/**
 * Plugin kernel.
 */

namespace Guido\SponsoredEngine\Core;

use Guido\SponsoredEngine\Modules\AI;
use Guido\SponsoredEngine\Modules\Analytics;
use Guido\SponsoredEngine\Modules\Expiry;
use Guido\SponsoredEngine\Modules\Guido;
use Guido\SponsoredEngine\Modules\Ranking;
use Guido\SponsoredEngine\Modules\WooCommerce;
use Guido\SponsoredEngine\Admin\Dashboard;
use Guido\SponsoredEngine\Admin\Settings;
use Guido\SponsoredEngine\Api\Rest;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Kernel
 *
 * Coordinates core bootstrapping and module registration.
 */
final class Kernel
{
    private Registry $registry;

    /**
     * Boot plugin services.
     */
    public function boot(): void
    {
        $this->registry = new Registry();

        $providers = [
            new Ranking(),
            new WooCommerce(),
            new Expiry(),
            new Analytics(),
            new AI(),
            new Guido(),
            new Dashboard(),
            new Settings(),
            new Rest(),
        ];

        foreach ($providers as $provider) {
            if ($provider instanceof ServiceProvider) {
                $provider->register($this->registry);
            }
        }

        foreach ($providers as $provider) {
            if ($provider instanceof ServiceProvider) {
                $provider->boot($this->registry);
            }
        }
    }
}
