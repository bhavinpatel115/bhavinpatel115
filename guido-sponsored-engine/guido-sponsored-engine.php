<?php
/**
 * Plugin Name: Guido Smart Sponsored Listings Pro
 * Description: Premium monetization engine for Guido + WP Listing Directory + WooCommerce.
 * Version: 1.0.0
 * Author: Guido Marketplace Team
 * Requires PHP: 8.1
 * Text Domain: guido-sponsored-engine
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/bootstrap.php';

\Guido\SponsoredEngine\Bootstrap::init();

register_activation_hook(__FILE__, ['Guido\\SponsoredEngine\\Bootstrap', 'activate']);
register_deactivation_hook(__FILE__, ['Guido\\SponsoredEngine\\Bootstrap', 'deactivate']);
