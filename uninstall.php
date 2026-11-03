<?php
/**
 * Remove plugin options when the plugin is deleted.
 *
 * @package Rsdev_Custom_Cart_Settings
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

foreach ( array( 'ccsp_enable_plugin', 'ccsp_multiplier', 'ccsp_error_message', 'ccsp_excluded_cats' ) as $rsdev_ccs_option ) {
	delete_option( $rsdev_ccs_option );
}
