<?php
/**
 * Admin integration: settings tab, assets, Plugins-list link and the old page redirect.
 *
 * @package Rsdev_Custom_Cart_Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hooks the plugin into the WordPress and WooCommerce admin.
 *
 * The settings themselves live in WooCommerce > Settings > Order Quantities
 * (see Rsdev_CCS_Settings_Page). WooCommerce handles the form nonce.
 */
class Rsdev_CCS_Admin {

	/**
	 * Slug of the old Settings > Custom Cart Settings page (version 1.x), redirected to the new tab.
	 */
	private const LEGACY_PAGE_SLUG = 'rsdev-custom-cart-settings';

	/**
	 * Register admin hooks.
	 */
	public function register_hooks(): void {
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 20 );
		add_filter( 'plugin_action_links_' . plugin_basename( RSDEV_CCS_FILE ), array( $this, 'add_settings_link' ) );
		add_action( 'admin_page_access_denied', array( $this, 'redirect_legacy_page' ) );
	}

	/**
	 * Add the Order Quantities tab, for administrators only.
	 *
	 * @param array $pages WooCommerce settings pages.
	 * @return array
	 */
	public function add_settings_page( $pages ) {
		$pages = is_array( $pages ) ? $pages : array();

		if ( class_exists( 'WC_Settings_Page' ) && current_user_can( 'manage_options' ) ) {
			require_once RSDEV_CCS_DIR . '/includes/class-rsdev-ccs-settings-page.php';
			$pages[] = new Rsdev_CCS_Settings_Page();
		}

		return $pages;
	}

	/**
	 * Whether the current admin screen is our settings tab.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return bool
	 */
	private function is_settings_tab( string $hook_suffix ): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only reading which tab is shown.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		return 'woocommerce_page_wc-settings' === $hook_suffix && 'rsdev_order_quantities' === $tab;
	}

	/**
	 * Load the preview/warning script and styles on our tab only.
	 * WooCommerce itself already loads its admin styles and the enhanced select there.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( ! $this->is_settings_tab( $hook_suffix ) ) {
			return;
		}

		wp_enqueue_style(
			'rsdev-ccs-admin',
			plugins_url( 'assets/css/admin.css', RSDEV_CCS_FILE ),
			array( 'woocommerce_admin_styles' ),
			RSDEV_CCS_VERSION
		);

		wp_enqueue_script(
			'rsdev-ccs-admin',
			plugins_url( 'assets/js/admin.js', RSDEV_CCS_FILE ),
			array(),
			RSDEV_CCS_VERSION,
			array( 'in_footer' => true )
		);
	}

	/**
	 * Add a "Settings" link in the Plugins list, before "Deactivate".
	 *
	 * @param array<string, string> $links Existing action links.
	 * @return array<string, string>
	 */
	public function add_settings_link( $links ): array {
		$links = is_array( $links ) ? $links : array();

		if ( ! current_user_can( 'manage_options' ) ) {
			return $links;
		}

		$settings = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wc-settings&tab=rsdev_order_quantities' ) ),
			esc_html__( 'Settings', 'rsdev-custom-cart-settings' )
		);

		return array( 'settings' => $settings ) + $links;
	}

	/**
	 * Send old bookmarks (Settings > Custom Cart Settings) to the new tab.
	 *
	 * The old page is no longer registered, so WordPress denies access; we redirect just before that.
	 */
	public function redirect_legacy_page(): void {
		global $pagenow;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only reading which page was requested.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( 'options-general.php' === $pagenow && self::LEGACY_PAGE_SLUG === $page && current_user_can( 'manage_options' ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=rsdev_order_quantities' ) );
			exit;
		}
	}
}
