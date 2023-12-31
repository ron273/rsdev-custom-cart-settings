<?php
/** Admin class */
defined( 'ABSPATH' ) || exit;

class RsdevCustomCartSettingsPlugin{
    function __construct() {
        add_action('admin_menu', array($this, 'adminPage'));
        add_action('admin_init',array($this, 'settings'));
    }

    function settings() {
        add_settings_section('ccsp_first_section', null, null, 'rsdev-custom-cart-settings');

        /* Add setting to enable or disable the custom cart settings plugin */
        add_settings_field(
            'ccsp_enable_plugin',
            __('Enable Plugin', 'rsdev-custom-cart-settings'),
            array($this, 'enablePluginHTML'),
            'rsdev-custom-cart-settings',
            'ccsp_first_section'
        );
        register_setting(
            'cartsettingsplugin', 
            'ccsp_enable_plugin', 
            array(
                'sanitize_callback' => 'sanitize_text_field', 
                'default' => '1'
            ));
        
        /* Add setting to enter the multiplier used to determine a valid cart content */
        add_settings_field(
            'ccsp_multiplier',
            __('Product Multiplier', 'rsdev-custom-cart-settings'),
            array($this, 'multiplierHTML'),
            'rsdev-custom-cart-settings',
            'ccsp_first_section'
        );
        register_setting(
            'cartsettingsplugin', 
            'ccsp_multiplier', 
            array(
                'sanitize_callback' => 'sanitize_text_field', 
                'default' => ''
            ));

        /* Add setting for the error message displayed above the cart if quantity does not match the rules */
        add_settings_field(
            'ccsp_error_message',
            __('Error Message Text', 'rsdev-custom-cart-settings'),
            array($this, 'errorMessageHTML'),
            'rsdev-custom-cart-settings',
            'ccsp_first_section'
        );
        register_setting(
            'cartsettingsplugin', 
            'ccsp_error_message', 
            array(
                'sanitize_callback' => 'sanitize_text_field', 
                'default' => 'You can only order in multiples of X'
            ));

        /* Add setting for the category ID's that should not follow the rules, like wooden boxes, openers, special arrangements etc */
        add_settings_field(
            'ccsp_excluded_cats',
            __('Excluded Categories', 'rsdev-custom-cart-settings'),
            array($this, 'excludedCatsHTML'),
            'rsdev-custom-cart-settings',
            'ccsp_first_section'
        );
        register_setting(
            'cartsettingsplugin', 
            'ccsp_excluded_cats', 
            array(
                'sanitize_callback' => 'sanitize_text_field', 
                'default' => ''
            ));
    
    }

    function enablePluginHTML() {
        ?>
            <input type="checkbox" name="ccsp_enable_plugin" value="1" <?php checked(get_option('ccsp_enable_plugin'), '1');?>>
         <?php
    }

    function excludedCatsHTML() {
        ?> 
            <input size="10" type="text" name="ccsp_excluded_cats" value="<?php echo esc_attr(get_option('ccsp_excluded_cats'));?>">
            <p style="font-style:italic; font-size:smaller"><?php echo $this->returnProductCategoriesSelect();?></p>
        <?php
    }

    function multiplierHTML() {
        ?> 
            <input size="4" type="text" name="ccsp_multiplier" value="<?php echo esc_attr(get_option('ccsp_multiplier'));?>">
        <?php
    }

    function errorMessageHTML() {
        ?> 
            <input size="60" type="text" name="ccsp_error_message" value="<?php echo esc_attr(get_option('ccsp_error_message'));?>"><br/>
            <p style="font-style:italic; font-size:smaller"><?php _e('Use %s to insert multiplier value','rsdev-custom-cart-settings');?></p>
        <?php
    }

    function adminPage() {
        add_options_page(
            __('Custom Cart Settings',''),
            __('Custom Cart Settings',''),
            'manage_options',
            'rsdev-custom-cart-settings',
            array($this, 'adminPageHTML'));
    }
    
    function adminPageHTML() {
        ?>
            <div class="wrap">
                <h1>Custom Cart Settings</h1>
                <form action="options.php" method="POST">
                    <?php
                    settings_fields('cartsettingsplugin');
                    do_settings_sections('rsdev-custom-cart-settings');
                    submit_button();
                    ?>
                </form>
            </div>
        <?php
    }

    function returnProductCategoriesSelect() {
        // Get product categories
        $product_categories = get_terms('product_cat', array(
            'orderby'    => 'ID',
            'order'      => 'ASC',
            'hide_empty' => true,
        ));

        // Check if categories are found
        if (!empty($product_categories)) {
            $output = "";

            // Loop through each category and add an option for the select element
            foreach ($product_categories as $category) {
                $output .= $category->name." (".$category->term_id.") / ";
            }
        } else {
            $output = "<p>".__('No product categories found.')."</p>";
        }
        return $output;
    }
}