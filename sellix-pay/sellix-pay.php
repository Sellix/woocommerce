<?php
/**
 * Plugin Name: Sellix Pay
 * Description: Accept Cryptocurrencies, Credit Cards, PayPal and regional banking methods with Sellix Pay.
 * Version: 1.9.3
 * Author:  Sellix io
 * Author URI: https://sellix.io/
 * Developer: Team Virtina (Harshal)
 * Developer URI: https://virtina.com
 * Text Domain: sellix-pay
 * Domain Path: /languages
 *
 * Requires at least: 4.9
 * Tested up to: 6.2.1
 * WC requires at least: 3.5
 * WC tested up to: 7.5.1
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

define('SELLIX_VERSION', '1.9.3');
define('SELLIX_PLUGIN_DIR', untrailingslashit( dirname(__FILE__)));
define('SELLIX_DIR_NAME', plugin_basename(dirname(__FILE__)));
define('SELLIX_BASE_URL', plugins_url() . '/' . SELLIX_DIR_NAME);
define('SELLIX_BASE_PATH', plugin_dir_path( __FILE__ ));


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
        sellix_init_gateway_class();
    }
}

/**
 * Class WC_Gateway_SellixPay
 *
 * @category Payment_Gateways
 * @class    WC_Gateway_SellixPay
 * @package  WooCommerce
 * 
 */
function sellix_init_gateway_class() {
    
    class WC_Gateway_SellixPay extends WC_Payment_Gateway
        {

            public function __construct()
            {
                global $woocommerce;
                $this->id = 'sellix';
                $this->icon = apply_filters('woocommerce_sellix_icon', SELLIX_BASE_URL . '/assets/images/logo.png');
                $this->method_title = __('Sellix', 'sellix-pay');
                $this->method_description  = $this->get_option('description');
                $this->has_fields = true;
                $this->webhook_url = add_query_arg('wc-api', 'sellix_webhook_handler', home_url('/'));
                $this->init_form_fields();
                $this->init_settings();
                $this->title = $this->get_option('title');
                $this->debug_mode = $this->get_option('debug_mode');
                $this->description = $this->get_option('description');
                $this->api_key = $this->get_option('api_key');
                $this->order_id_prefix = $this->get_option('order_id_prefix');
                $this->url_branded = $this->get_option('url_branded') == 'yes' ? true : false;
                $this->log = new WC_Logger();     // Logger
                // Actions
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
                // Webhook Handler
                add_action('woocommerce_api_sellix_webhook_handler', [$this, 'webhook_handler']);
                
                $this->x_merchant = $this->get_option('x_merchant');
            }
            
            function is_valid_for_use()
            {
                return true;
            }

            /**
             * Admin Panel Options
             */
            public function admin_options()
            {
                ?>
                <h3><?php _e('Sellix', 'sellix-pay'); ?></h3>

                <table class="form-table">
                    <?php
                    $this->generate_settings_html();
                    ?>
                </table>
                <?php
            }
            /**
             * Initialise settings
             */
            function init_form_fields()
            {
                $this->form_fields = [
                    'enabled' => [
                        'title' => __('Enable/Disable', 'sellix-pay'),
                        'type' => 'checkbox',
                        'label' => __('Enable Sellix', 'sellix-pay'),
                        'default' => 'yes'
                    ],
                    'debug_mode' => [
                        'title' => __('Enable/Disable', 'sellix-pay'),
                        'type' => 'checkbox',
                        'label' => __('Enable Debug Mode', 'sellix-pay'),
                        'default' => 'no'
                    ],
                    'title' => [
                        'title' => __('Title', 'sellix-pay'),
                        'type' => 'text',
                        'description' => __('This controls the title which the user sees during checkout.', 'sellix-pay'),
                        'default' => __('Sellix Pay', 'woocommerce'),
                        'desc_tip' => true,
                    ],
                    'description' => [
                        'title' => __('Description', 'sellix-pay'),
                        'type' => 'textarea',
                        'description' => __('This controls the description which the user sees during checkout.', 'sellix-pay'),
                        'default' => __('Pay with PayPal, Bitcoin, Ethereum, Litecoin and many more gateways via Sellix', 'sellix-pay')
                    ],
                    'api_key' => [
                        'title' => __('API Key', 'sellix-pay'),
                        'type' => 'text',
                        'description' => __('Please enter your Sellix API Key.', 'sellix-pay'),
                        'default' => '',
                    ],
                    'url_branded' => [
                        'title' => __('Branded URL', 'sellix-pay'),
                        'label' => __('Enable/Disable Sellix Pay Checkout Branded URL', 'sellix-pay'),
                        'type' => 'checkbox',
                        'description' => __('If this is enabled, customer will be redirected to your branded sellix pay checkout url', 'sellix-pay'),
                        'default' => 'no',
                    ],
                    'order_id_prefix' => [
                        'title' => __('Order ID Prefix', 'sellix-pay'),
                        'type' => 'text',
                        'description' => __('The prefix before the order number. For example, a prefix of "Order #" and a ID of "10" will result in "Order #10"', 'sellix-woocommerce'),
                        'default' => 'Order #',
                    ],
                    'x_merchant' => [
                        'title' => __('X-Sellix-Merchant', 'sellix-pay'),
                        'type' => 'text',
                        'description' => __('If you have more than one shop (merchant) under your Sellix account, you can send API requests with their authorization by passing theX-Sellix-Merchant header to each request.', 'sellix-pay').' '.
                        'For example if your Sellix account has two merchants (1. Jack, 2. James) and you want to make API requests as James, you need to pass the X-Sellix-Merchant header with value James to able to authenticate as different stores',
                        'default' => '',
                    ],
                ];

            }


            function generate_sellix_payment($order)
            {
                /*if (array_key_exists('payment_gateway', $_POST) && filter_var($_POST['payment_gateway'], FILTER_SANITIZE_STRING)) {*/
                
                    $params = [
                        'title' => $this->order_id_prefix . $order->get_id(),
                        'currency' => $order->get_currency(),
                        'return_url' => $this->get_return_url($order),
                        'webhook' => add_query_arg('wc_id', $order->get_id(), $this->webhook_url),
                        'email' => $order->get_billing_email(),
                        'value' => $order->get_total(),
                    ];

                    $route = "/v1/payments";
                    $response = $this->sellix_post_authenticated_json_request($route, $params);

                    if (is_wp_error($response)) {
                        return wc_add_notice(__('Payment error:', 'sellix-pay') . 'Sellix API error: ' . print_r($response->errors, true), 'error');
                    } else if (isset($response['body']) && !empty($response['body'])) {
                        $responseDecode = json_decode($response['body'], true);
                        if (isset($responseDecode['error']) && !empty($responseDecode['error'])) {
                            return wc_add_notice(__('Payment Gateway Error: ', 'sellix-pay') . $responseDecode['status'].'-'.$responseDecode['error'], 'error');
                        }

                        $url = $responseDecode['data']['url'];
                        if ($this->url_branded) {
                            if (isset($responseDecode['data']['url_branded'])) {
                                $url = $responseDecode['data']['url_branded'];
                            }
                        }
                        return $url;
                    } else {
                        return wc_add_notice(__('Payment Gateway Error: Empty response received.', 'sellix-pay'));
                    }
                /*} else{
                    return wc_add_notice(__('Payment Gateway Error', 'sellix-pay') . 'Sellix Before API error: Payment Method Not Selected OR Something Wrong', 'error');
                }*/
                 
            }
            /**
             * Process the payment and return the result
             */
            function process_payment($order_id)
            {
                $order = wc_get_order($order_id);
                $payment = $this->generate_sellix_payment($order);

                if ($this->debug_mode)
                    error_log(print_r('Payment process concerning order ' .
                        $order_id . ' returned: ' . $payment, true));
                
                if ($payment) {
                    return [
                        'result' => 'success',
                        'redirect' => $payment
                    ];
                } else {
                    return;
                }
            }
            /**
             * Handle webhooks
             */
            function webhook_handler()
            {
                
                global $woocommerce;			
                $data = json_decode(file_get_contents('php://input'), true);
                
                if ($this->debug_mode)
                    error_log(print_r('Webhook Handler received data: ' . $data, true));
                
                
                $sellix_order = $this->valid_sellix_order($data['data']['uniqid']);
                
                if ($this->debug_mode)
                    error_log(print_r('Concerning Sellix order: ' . $sellix_order, true));
                
                $viWcID = sanitize_text_field($_REQUEST['wc_id']);
                if ($sellix_order) {
                    $order = wc_get_order($viWcID);
               
                    if ($this->debug_mode)
                        error_log(print_r('Concerning Wordpress order: ' . $order, true));

                    $this->log->add('sellix', 'Order #' . $viWcID . ' (' . $sellix_order['uniqid'] . '). Status: ' . $sellix_order['status']);

                    if ($sellix_order['status'] == 'COMPLETED') {
                        $this->complete_order($viWcID);
                        $order->payment_complete();
                    } elseif ($sellix_order['status'] == 'WAITING_FOR_CONFIRMATIONS') {
                        $order->update_status('on-hold', sprintf(__('Awaiting crypto currency confirmations', 'sellix-pay')));
                    } elseif ($sellix_order['status'] == 'PARTIAL') {
                        $order->update_status('on-hold', sprintf(__('Cryptocurrency payment only partially paid', 'sellix-pay')));
                    }
                }
            }
            
                        
            function sellix_post_authenticated_json_request( $route, $body = false, $extra_headers = false, $method="POST") {

	    
                $server = 'https://dev.sellix.io'; // Api Url
		
		$url = $server . $route;
                
                $uaString = 'Sellix WooCommerce (PHP ' . PHP_VERSION . ')';
                $apiKey = $this->api_key;
                $headers = array(
                    'Content-Type'  => 'application/json',
                    'User-Agent' => $uaString,
                    'Authorization' => 'Bearer ' . $apiKey,
                );
               
                if (!empty($this->x_merchant)) {
                    $headers['X-Sellix-Merchant'] = sanitize_text_field($this->x_merchant);
                }
                
		if($extra_headers && is_array($extra_headers)) {
			$headers = array_merge($headers, $extra_headers);
		}
		$options = array(
			'method'  => $method,
                        'timeout' => 10,
			'headers' => $headers,
		);

		if ( ! empty( $body ) ) {
			$options['body'] = wp_json_encode( $body );
		}

		return wp_safe_remote_post( $url, $options );
            }
            
            function valid_sellix_order($order_uniqid)
            {
                
                $route = "/v1/orders/" . $order_uniqid;
                $response = $this->sellix_post_authenticated_json_request($route,'','','GET');
               
                if ($this->debug_mode)
                    error_log(print_r('Order validation returned: ' . $response['body'], true));
                
                if (is_wp_error($response)) {
                    mail(get_option('admin_email'), __('Unable to verify order via Sellix Pay API', 'sellix-pay'), $order_uniqid);
                    return null;
                } elseif (isset($response['response']['code']) && $response['response']['code'] == 200) {
                    $responseDecode = json_decode($response['body'], true);
                    return $responseDecode['data']['order'];
                }
                
            }
            
            function complete_order($wc_id) {
                global $woocommerce;
                $order = wc_get_order($wc_id);
                $order->update_status('completed');
            }
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
