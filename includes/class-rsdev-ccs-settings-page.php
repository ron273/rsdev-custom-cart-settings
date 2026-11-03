<?php
/**
 * WooCommerce > Settings > Order Quantities tab.
 *
 * Loaded from the 'woocommerce_get_settings_pages' filter, because WC_Settings_Page
 * only exists on WooCommerce settings screens.
 *
 * @package Rsdev_Custom_Cart_Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings tab for the order quantity rule.
 */
class Rsdev_CCS_Settings_Page extends WC_Settings_Page {

	/**
	 * Tab ID, used in admin.php?page=wc-settings&tab=...
	 */
	public const TAB_ID = 'rsdev_order_quantities';

	/**
	 * Only administrators may change these settings.
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * Custom field type for the live message preview.
	 */
	private const PREVIEW_TYPE = 'rsdev_ccs_preview';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = self::TAB_ID;
		$this->label = __( 'Order Quantities', 'rsdev-custom-cart-settings' );

		parent::__construct();

		add_action( 'woocommerce_admin_field_' . self::PREVIEW_TYPE, array( $this, 'output_preview_field' ) );
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . Rsdev_CCS_Settings::OPTION_MULTIPLIER, array( $this, 'sanitize_multiplier' ), 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . Rsdev_CCS_Settings::OPTION_EXCLUDED_CATS, array( $this, 'sanitize_excluded_cats' ), 10, 3 );
	}

	/**
	 * URL of this tab.
	 *
	 * @return string
	 */
	public static function get_url(): string {
		return admin_url( 'admin.php?page=wc-settings&tab=' . self::TAB_ID );
	}

	/**
	 * Settings for the (only) section of this tab.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {
		return array(
			array(
				'id'    => 'rsdev_ccs_rule',
				'type'  => 'title',
				'title' => __( 'Rule', 'rsdev-custom-cart-settings' ),
				'desc'  => __( 'Customers can only check out when the number of counted products in their cart is a multiple of the product multiplier.', 'rsdev-custom-cart-settings' ),
			),
			array(
				'id'    => Rsdev_CCS_Settings::OPTION_ENABLED,
				'type'  => 'checkbox',
				'title' => __( 'Enable plugin', 'rsdev-custom-cart-settings' ),
				// Stored as 'yes'/'no' by WooCommerce; version 1.x stored '1'/''. Both are understood.
				'value' => Rsdev_CCS_Settings::is_enabled() ? 'yes' : 'no',
			),
			array(
				'id'                => Rsdev_CCS_Settings::OPTION_MULTIPLIER,
				'type'              => 'number',
				'title'             => __( 'Product multiplier', 'rsdev-custom-cart-settings' ),
				'desc'              => __( 'The number of counted products in the cart must be a multiple of this number. Use 1 to allow any quantity.', 'rsdev-custom-cart-settings' ),
				'value'             => (string) Rsdev_CCS_Settings::get_multiplier(),
				'css'               => 'width: 6em;',
				'custom_attributes' => array(
					'min'  => '1',
					'step' => '1',
				),
			),
			array(
				'id'                => Rsdev_CCS_Settings::OPTION_EXCLUDED_CATS,
				'type'              => 'multiselect',
				'title'             => __( 'Excluded categories', 'rsdev-custom-cart-settings' ),
				'desc'              => __( 'Products in these categories do not count towards the multiple.', 'rsdev-custom-cart-settings' ),
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 25em; max-width: 100%;',
				'options'           => $this->get_category_options(),
				'value'             => array_map( 'strval', Rsdev_CCS_Settings::get_excluded_category_ids() ),
				'custom_attributes' => array(
					'data-placeholder' => __( 'Choose categories…', 'rsdev-custom-cart-settings' ),
				),
			),
			array(
				'id'   => 'rsdev_ccs_rule',
				'type' => 'sectionend',
			),
			array(
				'id'    => 'rsdev_ccs_message',
				'type'  => 'title',
				'title' => __( 'Customer message', 'rsdev-custom-cart-settings' ),
				'desc'  => __( 'Shown in the cart and at checkout when the cart does not meet the rule.', 'rsdev-custom-cart-settings' ),
			),
			array(
				'id'          => Rsdev_CCS_Settings::OPTION_MESSAGE,
				'type'        => 'text',
				'title'       => __( 'Error message text', 'rsdev-custom-cart-settings' ),
				/* translators: "%s" is shown literally; it is the placeholder admins type in the message. */
				'desc'        => __( 'Use %s to insert the multiplier value. Leave empty to use the default text.', 'rsdev-custom-cart-settings' ),
				'placeholder' => Rsdev_CCS_Settings::get_default_message_template(),
				'value'       => Rsdev_CCS_Settings::get_message_template(),
				'css'         => 'width: 100%; max-width: 40em;',
			),
			array(
				'id'        => 'rsdev_ccs_preview',
				'type'      => self::PREVIEW_TYPE,
				'title'     => __( 'Preview', 'rsdev-custom-cart-settings' ),
				'is_option' => false,
			),
			array(
				'id'   => 'rsdev_ccs_message',
				'type' => 'sectionend',
			),
		);
	}

	/**
	 * Output the tab, with a warning when the rule has no effect.
	 */
	public function output() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$show_warning = Rsdev_CCS_Settings::is_enabled() && 1 === Rsdev_CCS_Settings::get_multiplier();
		?>
		<div id="rsdev-ccs-multiplier-warning" class="notice notice-warning inline" <?php echo $show_warning ? '' : 'style="display:none"'; ?>>
			<p><?php esc_html_e( 'The plugin is enabled, but with a product multiplier of 1 every quantity is allowed, so the rule has no effect. Set the multiplier to 2 or higher.', 'rsdev-custom-cart-settings' ); ?></p>
		</div>
		<?php
		parent::output();
	}

	/**
	 * Save the tab. WooCommerce already checked the nonce and manage_woocommerce;
	 * this tab additionally requires an administrator.
	 */
	public function save() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		parent::save();
	}

	/**
	 * Output the live preview row. Updated by assets/js/admin.js while typing.
	 *
	 * @param array $field Field definition.
	 */
	public function output_preview_field( $field ): void {
		?>
		<tr>
			<th scope="row" class="titledesc"><?php echo esc_html( $field['title'] ?? '' ); ?></th>
			<td class="forminp">
				<div id="rsdev-ccs-preview" class="rsdev-ccs-preview" role="status" aria-live="polite" data-default-template="<?php echo esc_attr( Rsdev_CCS_Settings::get_default_message_template() ); ?>"><?php echo esc_html( Rsdev_CCS_Settings::get_message() ); ?></div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Sanitize the multiplier: a whole number of at least 1. Invalid input keeps the saved value.
	 *
	 * @param mixed $value     Value after WooCommerce's cleaning.
	 * @param array $option    Field definition.
	 * @param mixed $raw_value Posted value.
	 * @return int|null Null keeps the current value.
	 */
	public function sanitize_multiplier( $value, $option, $raw_value ) {
		$raw_value = is_scalar( $raw_value ) ? trim( (string) $raw_value ) : '';

		if ( '' === $raw_value ) {
			return 1;
		}

		if ( ! ctype_digit( $raw_value ) || (int) $raw_value < 1 ) {
			WC_Admin_Settings::add_error( __( 'The product multiplier must be a whole number of 1 or higher. The previous value has been kept.', 'rsdev-custom-cart-settings' ) );
			return null;
		}

		return (int) $raw_value;
	}

	/**
	 * Sanitize the excluded categories: keep only existing product category IDs.
	 * Stored as a comma separated string, as in version 1.x.
	 *
	 * @param mixed $value     Value after WooCommerce's cleaning (array of IDs).
	 * @param array $option    Field definition.
	 * @param mixed $raw_value Posted value.
	 * @return string
	 */
	public function sanitize_excluded_cats( $value, $option, $raw_value ): string {
		$valid   = array();
		$invalid = array();

		foreach ( Rsdev_CCS_Settings::parse_id_list( is_array( $value ) ? $value : array() ) as $id ) {
			if ( term_exists( $id, 'product_cat' ) ) {
				$valid[] = $id;
			} else {
				$invalid[] = $id;
			}
		}

		if ( $invalid ) {
			WC_Admin_Settings::add_error(
				sprintf(
					/* translators: %s: comma separated list of IDs. */
					__( 'These IDs are not product categories and have been removed: %s', 'rsdev-custom-cart-settings' ),
					implode( ', ', $invalid )
				)
			);
		}

		return implode( ',', $valid );
	}

	/**
	 * Product categories as [term_id => label], sorted by name.
	 * Sub categories are labelled with their parents, e.g. "Wine › Red".
	 *
	 * @return array<int, string>
	 */
	private function get_category_options(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$names   = wp_list_pluck( $terms, 'name', 'term_id' );
		$options = array();

		foreach ( $terms as $term ) {
			$path = array();
			foreach ( array_reverse( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) ) as $ancestor_id ) {
				$path[] = $names[ $ancestor_id ] ?? '';
			}
			$path[] = $term->name;

			$options[ (int) $term->term_id ] = implode( ' › ', array_filter( $path ) );
		}

		natcasesort( $options );

		return $options;
	}
}
