<?php
/**
 * Base service provider.
 */

namespace Guido\SponsoredEngine\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ServiceProvider
 *
 * Defines a contract for service providers.
 */
abstract class ServiceProvider
{
    /**
     * Register services.
     */
    abstract public function register(Registry $registry): void;

    /**
     * Boot services.
     */
    public function boot(Registry $registry): void
    {
        // Optional.
    }
}
