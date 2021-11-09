<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Plugin Name: Sellix WooCommerce Payment Gateway
 * Plugin URI: ''
 * Description:  A payment gateway for Sellix Pay
 * Version: 1.0.0
 */
add_action('plugins_loaded', 'sellixPayment_gateway_load', 0);
function sellixPayment_gateway_load()
{
    if (!class_exists('WC_Payment_Gateway')) {
        // oops!
        return;
    }
    /**
     * Add the gateway to WooCommerce.
     */
    add_filter('woocommerce_payment_gateways', 'add_gateway');
    function add_gateway($classes)
    {
        if (!in_array('WC_Gateway_SellixPayment', $classes)) {
            $classes[] = 'WC_Gateway_SellixPayment';
        }    return $classes;
    }
    class WC_Gateway_SellixPayment extends WC_Payment_Gateway
    {
     
        public function __construct()
        {
            global $woocommerce;
            $this->id = 'sellix';
            $this->icon = apply_filters('woocommerce_sellix_icon', plugins_url() . '/sellix-woocommerce/assets/sellix.png');
            $this->method_title = __('Sellix', 'woocommerce');
            $this->has_fields = true;
            $this->webhook_url = add_query_arg('wc-api', 'sellix_webhook_handler', home_url('/'));
            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->debug_mode = $this->get_option('debug_mode');
            $this->description = $this->get_option('description');
            $this->email = $this->get_option('email');
            $this->api_key = $this->get_option('api_key');
            $this->order_id_prefix = $this->get_option('order_id_prefix');
            $this->confirmations = $this->get_option('confirmations');
            $this->paypal = $this->get_option('paypal') == 'yes' ? true : false;
            $this->bitcoin = $this->get_option('bitcoin') == 'yes' ? true : false;
            $this->litecoin = $this->get_option('litecoin') == 'yes' ? true : false;
            $this->ethereum = $this->get_option('ethereum') == 'yes' ? true : false;
            $this->dash = $this->get_option('dash') == 'yes' ? true : false;
            $this->bitcoin_cash = $this->get_option('bitcoin_cash') == 'yes' ? true : false;
            $this->skrill = $this->get_option('skrill') == 'yes' ? true : false;
            $this->perfectmoney = $this->get_option('perfectmoney') == 'yes' ? true : false;			// Logger
            $this->log = new WC_Logger();
            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
            // Webhook Handler
            add_action('woocommerce_api_sellix_webhook_handler', [$this, 'webhook_handler']);
        }
        public function payment_fields()
        {
            ?>
            <div class="form-row sellix-payment-gateway-form">
                <label for="payment_gateway" class="sellix-payment-gateway-label">
                    Payment Method <abbr class="required" title="required">*</abbr>
                </label>
                <select name="payment_gateway" class="sellix-payment-gateway-select">
                    <?php if ($this->paypal){ ?><option value="PAYPAL">PayPal</option><?php } ?>
                    <?php if ($this->bitcoin){ ?><option value="BITCOIN">Bitcoin</option><?php } ?>
                    <?php if ($this->litecoin){ ?><option value="LITECOIN">Litecoin</option><?php } ?>
                    <?php if ($this->ethereum){ ?><option value="EUTHEREUM">Ethereum</option><?php } ?>
                    <?php if ($this->bitcoin_cash){ ?><option value="BITCOINCASH">Bitcoin Cash</option><?php } ?>
                    <?php if ($this->skrill){ ?><option value="SKRILL">Skrill</option><?php } ?>
                    <?php if ($this->perfectmoney){ ?><option value="PERFECTMONEY">PerfectMoney</option><?php } ?>
                </select>
            </div>
            <?php
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
            <h3><?php _e('Sellix', 'woocommerce'); ?></h3>

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
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable Sellix', 'woocommerce'),
                    'default' => 'yes'
                ],
                'debug_mode' => [
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable Debug Mode', 'woocommerce'),
                    'default' => 'no'
                ],
                'title' => [
                    'title' => __('Title', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                    'default' => __('Sellix Pay', 'woocommerce'),
                    'desc_tip' => true,
                ],
                'description' => [
                    'title' => __('Description', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
                    'default' => __('Pay with PayPal, Bitcoin, Ethereum, Litecoin and many more gateways via Sellix', 'woocommerce')
                ],
                'email' => [
                    'title' => __('Email', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Please enter your Sellix email address.', 'woocommerce'),
                    'default' => '',
                ],
                'api_key' => [
                    'title' => __('API Key', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Please enter your Sellix API Key.', 'woocommerce'),
                    'default' => '',
                ],
                'order_id_prefix' => [
                    'title' => __('Order ID Prefix', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('The prefix before the order number. For example, a prefix of "Order #" and a ID of "10" will result in "Order #10"', 'woocommerce'),
                    'default' => 'Order #',
                ],
                'confirmations' => [
                    'title' => __('Number of confirmations for crypto currencies', 'woocommerce'),
                    'type' => 'number',
                    'description' => __('The default of 1 is advised for both speed and security', 'woocommerce'),
                    'default' => '1'
                ],
                'paypal' => [
                    'title' => __('Accept PayPal', 'woocommerce'),
                    'label' => __('Enable/Disable PayPal', 'woocommerce'),
                    'type' => 'checkbox',
                    'default' => 'no',
                ],
                'bitcoin' => [
                    'title' => __('Accept Bitcoin', 'woocommerce'),
                    'label' => __('Enable/Disable Bitcoin', 'woocommerce'),
                    'type' => 'checkbox',
                    'default' => 'no',
                ],
                'litecoin' => [
                    'title' => __('Accept Litecoin', 'woocommerce'),
                    'label' => __('Enable/Disable Litecoin', 'woocommerce'),
                    'type' => 'checkbox',
                    'default' => 'no',
                ],
                'ethereum' => [
                    'title' => __('Accept Ethereum', 'woocommerce'),
                    'label' => __('Enable/Disable Ethereum', 'woocommerce'),
                    'type' => 'checkbox',
                    'default' => 'no',
                ],
                'bitcoin_cash' => [
                    'title' => __('Accept Bitcoin Cash', 'woocommerce'),
                    'label' => __('Enable/Disable Bitcoin Cash', 'woocommerce'),
                    'type' => 'checkbox',
                    'default' => 'no',
                ],
                'skrill' => [
                    'title' => __('Accept Skrill', 'woocommerce'),
                    'label' => __('Enable/Disable Skrill', 'woocommerce'),
                    'type' => 'checkbox',
                    'default' => 'no',
                ],
                'perfectmoney' => [
                    'title' => __('Accept PerfectMoney', 'woocommerce'),
                    'label' => __('Enable/Disable PerfectMoney', 'woocommerce'),
                    'type' => 'checkbox',
                    'default' => 'no',
                ],
            ];

        }
        function generate_sellix_payment($order)
        {
            $params = [
                'title' => $this->order_id_prefix . $order->get_id(),
                'currency' => $order->get_currency(),
                'return_url' => $this->get_return_url($order),
                'webhook' => add_query_arg('wc_id', $order->get_id(), $this->webhook_url),
                'email' => $order->get_billing_email(),
                'value' => $order->get_total(),
                'gateway' => $_POST['payment_gateway'],
                'confirmations' => $this->confirmations
            ];
            $curl = curl_init('https://dev.sellix.io/v1/payments');
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($curl, CURLOPT_USERAGENT, 'Sellix WooCommerce (PHP ' . PHP_VERSION . ')');
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->api_key]);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BEARER);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            if ($this->debug_mode)
                error_log(print_r('Sellix Payment creation concerning order ' .
                    $order->get_id() . ' returned: ' . $response, true));

            if (curl_errno($curl)) {
                return wc_add_notice(__('Payment error:', 'woothemes') . 'Request error: ' . curl_error($curl), 'error');
            }
            curl_close($curl);
            $response = json_decode($response, true);
            if ($response['error']) {
                return wc_add_notice(__('Payment error:', 'woothemes') . 'Sellix API error: ' . $response['error'], 'error');
            } else {
                return $response['data']['url'];
            }
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
            if ($sellix_order) {
                $order = wc_get_order($_REQUEST['wc_id']);
                if ($this->debug_mode)
                    error_log(print_r('Concerning Wordpress order: ' . $order, true));

                $this->log->add('sellix', 'Order #' . $_REQUEST['wc_id'] . ' (' . $sellix_order['uniqid'] . '). Status: ' . $sellix_order['status']);

                if ($sellix_order['status'] == 'COMPLETED') {
                    $this->complete_order($_REQUEST['wc_id']);
                    $order->payment_complete();
                } elseif ($sellix_order['status'] == 'WAITING_FOR_CONFIRMATIONS') {
                    $order->update_status('on-hold', sprintf(__('Awaiting crypto currency confirmations', 'woocommerce')));
                } elseif ($sellix_order['status'] == 'PARTIAL') {
                    $order->update_status('on-hold', sprintf(__('Cryptocurrency payment only partially paid', 'woocommerce')));
                }
            }
        }
        function valid_sellix_order($order_uniqid)
        {
            $curl = curl_init('https://dev.sellix.io/v1/orders/' . $order_uniqid);
            curl_setopt($curl, CURLOPT_USERAGENT, 'Sellix WooCommerce (PHP ' . PHP_VERSION . ')');
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->api_key]);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BEARER);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);

            curl_close($curl);
            $body = json_decode($response, true);

            if ($this->debug_mode)
                error_log(print_r('Order validation returned: ' . $body, true));

            if ($body['error']) {
                mail(get_option('admin_email'), sprintf(__('Unable to verify order via Sellix Pay API', 'woocommerce'), $order_uniqid));
                return null;
            } else {
                return $body['data']['order'];
            }
        }
        function complete_order($wc_id) {
            global $woocommerce;
            $order = wc_get_order($wc_id);
            $order->update_status('completed');
        }
    }
}
