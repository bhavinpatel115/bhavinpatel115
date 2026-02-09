<?php
/**
 * Service registry container.
 */

namespace Guido\SponsoredEngine\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Registry
 *
 * Lightweight service container for plugin components.
 */
final class Registry
{
    /** @var array<string, object> */
    private array $services = [];

    /**
     * Register a service instance.
     */
    public function set(string $id, object $service): void
    {
        $this->services[$id] = $service;
    }

    /**
     * Get a service instance.
     */
    public function get(string $id): ?object
    {
        return $this->services[$id] ?? null;
    }
}
