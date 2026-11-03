<?php
/**
 * Plugin Name:          Custom Cart Settings
 * Plugin URI:           https://www.rsdev.nl/rsdev-custom-cart-settings/
 * Description:          Enables shop owners to force orders in multiples of X (e.g. boxes containing 6 bottles of wine).
 * Version:              1.4.0
 * Requires at least:    6.5
 * Requires PHP:         8.1
 * Requires Plugins:     woocommerce
 * WC requires at least: 8.0
 * WC tested up to:      11.2
 * Author:               Ronald Smit
 * Author URI:           https://www.rsdev.nl/
 * License:              GPL v2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:           https://www.rsdev.nl/rsdev-custom-cart-settings/
 * Text Domain:          rsdev-custom-cart-settings
 * Domain Path:          /languages
 *
 * @package Rsdev_Custom_Cart_Settings
 */

defined( 'ABSPATH' ) || exit;

define( 'RSDEV_CCS_VERSION', '1.4.0' );
define( 'RSDEV_CCS_FILE', __FILE__ );
define( 'RSDEV_CCS_DIR', __DIR__ );

require_once RSDEV_CCS_DIR . '/includes/class-rsdev-ccs-settings.php';
require_once RSDEV_CCS_DIR . '/includes/class-rsdev-ccs-admin.php';
require_once RSDEV_CCS_DIR . '/includes/class-rsdev-ccs-cart-validator.php';

/**
 * Declare compatibility with WooCommerce features (HPOS and cart/checkout blocks).
 */
function rsdev_ccs_declare_wc_compatibility() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', RSDEV_CCS_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', RSDEV_CCS_FILE, true );
	}
}
add_action( 'before_woocommerce_init', 'rsdev_ccs_declare_wc_compatibility' );

/**
 * Load the plugin text domain.
 */
function rsdev_ccs_load_textdomain() {
	load_plugin_textdomain( 'rsdev-custom-cart-settings', false, dirname( plugin_basename( RSDEV_CCS_FILE ) ) . '/languages' );
}
add_action( 'init', 'rsdev_ccs_load_textdomain' );

/**
 * Boot the plugin once all plugins are loaded, so we can check for WooCommerce.
 */
function rsdev_ccs_init() {
	if ( is_admin() ) {
		( new Rsdev_CCS_Admin() )->register_hooks();
	}

	if ( class_exists( 'WooCommerce' ) ) {
		( new Rsdev_CCS_Cart_Validator() )->register_hooks();
	}
}
add_action( 'plugins_loaded', 'rsdev_ccs_init' );
