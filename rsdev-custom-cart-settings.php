<?php

/*
 * Plugin Name:       Custom Cart Settings
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       Enables shop users to force orders in multiple of X (e.g. boxes containing 6 bottles of wine).
 * Version:           1.0.1
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Ronald Smit
 * Author URI:        https://www.rsdev.nl/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://www.rsdev.nl/rsdev-custom-cart-settings/
 * Text Domain:       rsdev-custom-cart-settings
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

/** Required admin class and initialisation of the admin settings in the backend */
require_once(dirname(__FILE__) . '/admin.php');
$cartSettingsPlugin = new RsdevCustomCartSettingsPlugin();

/** Load plugin textdomain */
function cart_settings_text_domain_load() {
    load_plugin_textdomain('rsdev-custom-cart-settings', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action('init','cart_settings_text_domain_load');

/** Function to display the notice above the cart when conditions are not met */
function add_custom_notice_text() {
    $text = get_option('ccsp_error_message');
    $message = sprintf( $text, get_option('ccsp_multiplier') );
    echo '<div class="woocommerce-info">' .esc_html($message). "</div>";
}

/** Function to remove the checkout button */
function remove_default_checkout_button() {
    remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 10);
}

/** Function to add a disabled checkout button for consistent user experience */
function add_disabled_checkout_button() {
    echo '<a href="#" style="cursor:not-allowed;" class="button disabled not-allowed checkout-button" style="pointer-events: none;">' .
            __('Proceed to Checkout','rsdev-custom-cart-settings') .
        '</a>';
}

/**
 * This function counts all items in the cart, but not counting items that belong to categories that have been excluded in the backend settings.
 */
function count_cart_items() {
    // Get the current WooCommerce cart
    if ( is_null( WC()->cart ) ) wc_load_cart();
    $cart = WC()->cart;

    // Define the category IDs to be excluded
    $excluded_category_ids = explode(",", get_option('ccsp_excluded_cats'));

    // Initialize a variable to store the item count
    $item_count = 0;

    // Loop through each cart item
    foreach ( $cart->get_cart_contents() as $cart_item_key => $cart_item ) {
        // Get the product ID for the cart item
        $product_id = $cart_item['product_id'];

        // Get the product object
        $product = wc_get_product( $product_id );

        // Get the product categories for the current item
        $product_categories = get_the_terms( $product_id, 'product_cat' );
        //$category_ids = wp_list_pluck( $product_categories, 'term_id' );

        // Check if the product has categories
        
        if ( $product_categories && ! is_wp_error( $product_categories ) ) {
            // Extract category IDs from the categories
            $category_ids = wp_list_pluck( $product_categories, 'term_id' );

            // Check if any of the excluded category IDs match the product categories
            $intersect = array_intersect( $excluded_category_ids, $category_ids );

            // If there is no intersection, increment the item count
            if ( empty( $intersect ) ) {
                $item_count += $cart_item['quantity'];
            }
        }
    }
    // Return the final item count
    return $item_count;
}

function display_custom_cart_notice() {
    //pull data from woocommerce and settings database
    $multiple = get_option('ccsp_multiplier');
    
    //Get total number of items in cart, not counting excluded categories
    $cart_total = count_cart_items();

    //If cart # of cart items not a multiple of 6, show notice and disable checkout button
    if (is_cart() && $cart_total % $multiple !== 0) {    
        // Output your custom notice
        add_action('woocommerce_before_cart','add_custom_notice_text', 10);

        // Remove the checkout button
        remove_action( 'woocommerce_proceed_to_checkout','woocommerce_button_proceed_to_checkout', 20);
        
        //Add a disabled checkout button for better user experience
        add_action('woocommerce_proceed_to_checkout', 'add_disabled_checkout_button', 20);
    }
} 
/** Only use this plugin when specifically activated in the backend */ 
//if ( get_option('ccsp_enable_plugin') == "1" ) {
    add_action('wp', 'display_custom_cart_notice');
//} 



