<?php
/**
 * Cart validation: enforce that the cart quantity is a multiple of X.
 *
 * @package Rsdev_Custom_Cart_Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * Validates the cart for both the classic (shortcode) and the block based cart and checkout.
 */
class Rsdev_CCS_Cart_Validator {

	/**
	 * Notice/error code.
	 */
	private const ERROR_CODE = 'rsdev_ccs_invalid_quantity';

	/**
	 * Register WooCommerce hooks.
	 */
	public function register_hooks(): void {
		// Block cart & checkout (Store API).
		add_action( 'woocommerce_store_api_cart_errors', array( $this, 'add_store_api_error' ), 10, 2 );

		// Classic cart & checkout page, and classic order submission.
		add_action( 'woocommerce_check_cart_items', array( $this, 'add_classic_notice' ) );

		// Classic cart: replace the checkout button with a disabled one.
		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'maybe_disable_classic_checkout_button' ), 1 );

		// Block cart: grey out the checkout button.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_cart_block_assets' ) );

		// Any cart type: send visitors back to the cart when they open the checkout with an invalid cart.
		add_action( 'template_redirect', array( $this, 'maybe_redirect_checkout' ) );
	}

	/**
	 * Redirect the checkout page to the cart while the cart does not meet the rule.
	 *
	 * Covers the checkout button, the mini-cart and typing the URL. Endpoints such as
	 * order-received and order-pay are left alone.
	 */
	public function maybe_redirect_checkout(): void {
		if ( ! is_checkout() || is_wc_endpoint_url() || is_customize_preview() ) {
			return;
		}

		$cart = WC()->cart;
		if ( ! $cart instanceof WC_Cart || $cart->is_empty() || $this->is_cart_valid( $cart ) ) {
			return;
		}

		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}

	/**
	 * Load the script and styles that grey out the block cart's checkout button.
	 */
	public function enqueue_cart_block_assets(): void {
		if ( ! is_cart() || ! Rsdev_CCS_Settings::is_enabled() || ! has_block( 'woocommerce/cart', wc_get_page_id( 'cart' ) ) ) {
			return;
		}

		wp_enqueue_style(
			'rsdev-ccs-cart-block',
			plugins_url( 'assets/css/cart-block.css', RSDEV_CCS_FILE ),
			array(),
			RSDEV_CCS_VERSION
		);

		wp_enqueue_script(
			'rsdev-ccs-cart-block',
			plugins_url( 'assets/js/cart-block.js', RSDEV_CCS_FILE ),
			array( 'wp-data', 'wc-blocks-data-store' ),
			RSDEV_CCS_VERSION,
			array( 'in_footer' => true )
		);
	}

	/**
	 * Store API: add an error, which blocks checkout and shows a notice in the blocks.
	 *
	 * @param WP_Error $errors Errors object.
	 * @param WC_Cart  $cart   Cart.
	 */
	public function add_store_api_error( $errors, $cart ): void {
		if ( $errors instanceof WP_Error && $cart instanceof WC_Cart && ! $this->is_cart_valid( $cart ) ) {
			$errors->add( self::ERROR_CODE, Rsdev_CCS_Settings::get_message() );
		}
	}

	/**
	 * Classic cart/checkout: add an error notice, which blocks checkout.
	 */
	public function add_classic_notice(): void {
		// The Store API is handled by add_store_api_error().
		if ( WC()->is_store_api_request() ) {
			return;
		}

		$cart = WC()->cart;
		if ( ! $cart instanceof WC_Cart || $this->is_cart_valid( $cart ) ) {
			return;
		}

		$message = Rsdev_CCS_Settings::get_message();
		if ( ! wc_has_notice( $message, 'error' ) ) {
			wc_add_notice( $message, 'error', array( 'id' => self::ERROR_CODE ) );
		}
	}

	/**
	 * Classic cart: show a disabled button instead of the "Proceed to checkout" link.
	 */
	public function maybe_disable_classic_checkout_button(): void {
		$cart = WC()->cart;
		if ( ! $cart instanceof WC_Cart || $this->is_cart_valid( $cart ) ) {
			return;
		}

		remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );

		printf(
			'<span class="checkout-button button alt wc-forward disabled" aria-disabled="true" style="cursor:not-allowed;">%s</span>',
			esc_html__( 'Proceed to checkout', 'rsdev-custom-cart-settings' )
		);
	}

	/**
	 * Whether the cart meets the rule.
	 *
	 * @param WC_Cart $cart Cart.
	 * @return bool
	 */
	public function is_cart_valid( WC_Cart $cart ): bool {
		if ( ! Rsdev_CCS_Settings::is_enabled() ) {
			return true;
		}

		$multiplier = Rsdev_CCS_Settings::get_multiplier();
		if ( $multiplier <= 1 ) {
			return true;
		}

		return 0 === $this->count_counted_items( $cart ) % $multiplier;
	}

	/**
	 * Count the cart quantities, skipping products in excluded categories.
	 *
	 * Products without categories are counted.
	 *
	 * @param WC_Cart $cart Cart.
	 * @return int
	 */
	public function count_counted_items( WC_Cart $cart ): int {
		$excluded = Rsdev_CCS_Settings::get_excluded_category_ids();
		$count    = 0;

		foreach ( $cart->get_cart() as $cart_item ) {
			$quantity = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 0;

			if ( $excluded ) {
				// Variations inherit the categories of their parent product.
				$product_id   = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
				$category_ids = wc_get_product_term_ids( $product_id, 'product_cat' );

				if ( array_intersect( $excluded, $category_ids ) ) {
					continue;
				}
			}

			$count += $quantity;
		}

		return $count;
	}
}
