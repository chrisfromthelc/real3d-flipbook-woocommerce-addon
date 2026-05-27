<?php
/**
 * Fired when the WooCommerce addon is uninstalled.
 *
 * @package Real3DFlipbookWooCommerceAddon
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall cleanup runs once; caching is pointless.

$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('r3d_flipbook_id', 'r3d_preview_flipbook_id', 'r3d_show_thankyou_flipbook')" );

delete_option( 'r3d_woo_show_thankyou_flipbook' );

$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_flipbook'" );

$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = 'flipbook_id'" );

$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_r3d_flipbooks_%' OR option_name LIKE '_transient_timeout_r3d_flipbooks_%'"
);

// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching

// Flush rewrite rules to remove the 'flipbooks' endpoint.
flush_rewrite_rules();
