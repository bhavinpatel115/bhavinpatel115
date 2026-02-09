<?php
/**
 * Uninstall cleanup for Guido Smart Sponsored Listings Pro.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('guido_sponsored_logs');
delete_option('guido_sponsored_events');
delete_option('guido_sponsored_debug');

$hooks = ['guido_sponsored_daily_scan', 'guido_sponsored_hourly_validation'];
foreach ($hooks as $hook) {
    $timestamp = wp_next_scheduled($hook);
    if ($timestamp) {
        wp_unschedule_event($timestamp, $hook);
    }
}
