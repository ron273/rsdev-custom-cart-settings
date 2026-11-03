<?php
/**
 * Access to the plugin settings, with safe defaults.
 *
 * @package Rsdev_Custom_Cart_Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read-only access to the stored plugin options.
 *
 * Option names are kept from version 1.0 so existing settings keep working.
 */
class Rsdev_CCS_Settings {

	public const OPTION_ENABLED       = 'ccsp_enable_plugin';
	public const OPTION_MULTIPLIER    = 'ccsp_multiplier';
	public const OPTION_MESSAGE       = 'ccsp_error_message';
	public const OPTION_EXCLUDED_CATS = 'ccsp_excluded_cats';

	/**
	 * Placeholder in the message that is replaced by the multiplier.
	 */
	public const PLACEHOLDER = '%s';

	/**
	 * Whether the cart rule is enabled. Enabled when never saved.
	 *
	 * WooCommerce settings store 'yes'/'no'; version 1.x stored '1'/''. Both are accepted.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return in_array( (string) get_option( self::OPTION_ENABLED, 'yes' ), array( 'yes', '1' ), true );
	}

	/**
	 * The multiplier (X). Always a whole number of at least 1.
	 *
	 * @return int
	 */
	public static function get_multiplier(): int {
		return max( 1, absint( get_option( self::OPTION_MULTIPLIER, 1 ) ) );
	}

	/**
	 * The raw message template as stored (may be empty).
	 *
	 * @return string
	 */
	public static function get_message_template(): string {
		return (string) get_option( self::OPTION_MESSAGE, '' );
	}

	/**
	 * The default message template, translatable.
	 *
	 * @return string
	 */
	public static function get_default_message_template(): string {
		/* translators: %s: the number of products the cart must be a multiple of. */
		return __( 'You can only order in multiples of %s.', 'rsdev-custom-cart-settings' );
	}

	/**
	 * The message shown to customers, with the multiplier filled in.
	 *
	 * Uses a plain replacement instead of sprintf(), so a stray "%" in the
	 * message can never cause a fatal error.
	 *
	 * @return string
	 */
	public static function get_message(): string {
		$template = self::get_message_template();
		if ( '' === trim( $template ) ) {
			$template = self::get_default_message_template();
		}

		return str_replace( self::PLACEHOLDER, (string) self::get_multiplier(), $template );
	}

	/**
	 * Excluded product category IDs.
	 *
	 * @return int[]
	 */
	public static function get_excluded_category_ids(): array {
		return self::parse_id_list( get_option( self::OPTION_EXCLUDED_CATS, '' ) );
	}

	/**
	 * Turn a comma separated string (or array) into a list of unique positive integers.
	 *
	 * @param mixed $value Raw value.
	 * @return int[]
	 */
	public static function parse_id_list( $value ): array {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}
		if ( ! is_array( $value ) ) {
			return array();
		}

		$ids = array_map( 'absint', array_map( 'trim', array_map( 'strval', $value ) ) );

		return array_values( array_unique( array_filter( $ids ) ) );
	}
}
