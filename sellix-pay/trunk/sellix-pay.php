<?php
/**
 * Plugin Name: Sellix Pay
 * Description: Accept Cryptocurrencies, Credit Cards, PayPal and regional banking methods with Sellix Pay.
 * Version: 1.9.6
 * Author:  Sellix io
 * Author URI: https://sellix.io/
 * Developer: Team Virtina (Harshal)
 * Developer URI: https://virtina.com
 * Text Domain: sellix-pay
 * Domain Path: /languages
 *
 * Requires at least: 4.9
 * Tested up to: 6.4.2
 * WC requires at least: 3.5
 * WC tested up to: 8.4.0
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

define('SELLIX_VERSION', '1.9.5');
define('SELLIX_PLUGIN_DIR', untrailingslashit( dirname(__FILE__)));
define('SELLIX_DIR_NAME', plugin_basename(dirname(__FILE__)));
define('SELLIX_BASE_URL', plugins_url() . '/' . SELLIX_DIR_NAME);
define('SELLIX_BASE_PATH', plugin_dir_path( __FILE__ ));

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

register_activation_hook( __FILE__, 'activate_sellix_pay');


/*
 * The code that runs during plugin activation.
*/
function activate_sellix_pay() {

    // we need to display a error message before the plugin is installed.
    sellix_check_woocommerce_is_installed();
    // good to go -> activate the plugin.
}


function sellix_check_woocommerce_is_installed() {
    if (!sellix_check_woocommerce_plugin_status()) {
        // Deactivate the plugin
        deactivate_plugins(__FILE__);
        $error_message = __('The Sellix Pay plugin requires the <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugin to be active!', 'sellix-pay');
        wp_die($error_message);
    }
    return true;
}



/**
 * @return bool
 */
function sellix_check_woocommerce_plugin_status()
{
    // Test to see if WooCommerce is active (including network activated). we're good.
    if (in_array('woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option('active_plugins')))) {
        return true;
    }
    if (!is_multisite()) return false;
    $plugins = get_site_option( 'active_sitewide_plugins');
    return isset($plugins['woocommerce/woocommerce.php']);
}


// plugins loaded callback
add_action('plugins_loaded', 'sellix_on_all_plugins_loaded', 12);


/**
* Initialize the gateway.
*
* @version  1.0
*/
function sellix_on_all_plugins_loaded() {
    if (sellix_check_woocommerce_plugin_status()) {
        if (!class_exists('WC_Payment_Gateway')) {
            // oops!
            return;
        }
		require_once SELLIX_BASE_PATH. 'includes/regular-checkout.php';
    }
}

/**
* Return an instance of the gateway for those loader functions that need it
* so we don't keep creating it over and over again.
*
* @version  1.0
*/
add_filter( 'woocommerce_payment_gateways', 'sellix_add_gateway_class' );
function sellix_add_gateway_class( $methods ) {
    if (!in_array('WC_Gateway_SellixPay', $methods)) {
        $methods[] = 'WC_Gateway_SellixPay'; 
        return $methods;
    }
}

/**
* Register the payment method for blocks
*/
add_action( 'woocommerce_blocks_loaded', 'woocommerce_sellix_blocks_support' );
function woocommerce_sellix_blocks_support() {
    if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        require_once SELLIX_BASE_PATH. 'includes/blocks-checkout.php';
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                $payment_method_registry->register( new WC_SellixPay_Blocks_Support );
            }
        );
    }
}