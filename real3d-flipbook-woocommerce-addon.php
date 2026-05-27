<?php

/*
 * Plugin Name:   Real3D Flipbook WooCommerce Addon
 * Plugin URI:    https://github.com/chrisfromthelc/real3d-flipbook-woocommerce-addon
 * Description:   Addon for Real3D Flipbook. Checks WC product purchase status and displays flipbook.
 * Version:       1.6.2
 * Author:        chrisfromthelc
 * Author URI:    https://github.com/chrisfromthelc
 * Requires at least: 6.0
 * Requires PHP:  7.4
 * Text Domain:   real3d-flipbook-woocommerce-addon
 * Domain Path:   /languages
 * License:       GPL-2.0-or-later
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'R3D_WOO_VERSION', '1.6.2' );
define( 'R3D_WOO_FILE', __FILE__ );

require_once plugin_dir_path( R3D_WOO_FILE ) . '/includes/main.php';

if ( ! class_exists( 'YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
	require_once __DIR__ . '/lib/plugin-update-checker/plugin-update-checker.php';
}
$r3d_woo_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
	'https://github.com/chrisfromthelc/real3d-flipbook-woocommerce-addon/',
	__FILE__,
	'real3d-flipbook-woocommerce-addon'
);
$r3d_woo_update_checker->setBranch( 'main' );
$r3d_woo_update_checker->getVcsApi()->enableReleaseAssets();

$r3d_woo = R3D_Woo::get_instance();

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
		}
	}
);
