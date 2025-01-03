<?php
/**
 * Class WC_Gateway_SellixPay
 *
 * @category Payment_Gateways
 * @class    WC_Gateway_SellixPay
 * @package  WooCommerce
 * 
 */

class WC_Gateway_SellixPay extends WC_Payment_Gateway
{
	public $webhook_url;
	public $api_key;
	public $debug_mode;
	public $order_id_prefix;
	public $url_branded;
	public $x_merchant;

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

		// Actions
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
		// Webhook Handler
		add_action('woocommerce_api_sellix_webhook_handler', [$this, 'webhook_handler']);
		
		$this->x_merchant = $this->get_option('x_merchant');
	}
	
	public function is_valid_for_use()
	{
		return true;
	}

	/**
	 * Admin Panel Options
	 */
	public function admin_options()
	{
		?>
		<h3><?php esc_html_e('Sellix', 'sellix-pay'); ?></h3>

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
	public function init_form_fields()
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
				'default' => __('Sellix Pay', 'sellix-pay'),
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
				'description' => __('The prefix before the order number. For example, a prefix of "Order #" and a ID of "10" will result in "Order #10"', 'sellix-pay'),
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

	public function generate_sellix_payment($order)
	{
		$params = [
			'title' => $this->order_id_prefix . $order->get_id(),
			'currency' => $order->get_currency(),
			'return_url' => $this->get_return_url($order),
			'webhook' => add_query_arg('wc_id', $order->get_id(), $this->webhook_url),
			'email' => $order->get_billing_email(),
			'value' => $order->get_total(),
			'origin' => 'WOOCOMMERCE',
		];

		$route = "/v1/payments";
		$response = $this->sellix_post_authenticated_json_request($route, $params);

		if (is_wp_error($response)) {
			$errorMessage = __('Payment error', 'sellix-pay'); 
			throw new \Exception(esc_html($errorMessage));

		} else if (isset($response['body']) && !empty($response['body'])) {
			$responseDecode = json_decode($response['body'], true);
			if (isset($responseDecode['error']) && !empty($responseDecode['error'])) {
				$errorMessage = __('Payment Gateway Error: ', 'sellix-pay') . $responseDecode['status'].'-'.$responseDecode['error']; 
				throw new \Exception(esc_html($errorMessage));
			}

			$url = $responseDecode['data']['url'];
			if ($this->url_branded) {
				if (isset($responseDecode['data']['url_branded'])) {
					$url = $responseDecode['data']['url_branded'];
				}
			}
			return $url;
		} else {
			$errorMessage = __('Payment Gateway Error: Empty response received.', 'sellix-pay');
			throw new \Exception(esc_html($errorMessage));
		}
	}
	
	/**
	 * Process the payment and return the result
	 */
	public function process_payment($order_id)
	{
		$order = wc_get_order($order_id);
		try {
			$payment = $this->generate_sellix_payment($order);

			if ($this->debug_mode) {
				$this->log('Payment process concerning order ' .$order_id . ' returned: ' . $payment);
			}
			
			if ($payment) {
				return [
					'result' => 'success',
					'redirect' => $payment
				];
			} else {
				$errorMessage = __('Payment Gateway Error: Empty response received.', 'sellix-pay');
				throw new \Exception(esc_html($errorMessage));
			}
		} catch (\Exception $e) {
            $message = $e->getMessage();
			if ($this->debug_mode) {
				$this->log($message);
			}
			
            WC()->session->set('refresh_totals', true);
            wc_add_notice($message, $notice_type = 'error');
            return array(
                'result' => 'failure',
                'redirect' => wc_get_checkout_url(),
				'message' => $message,
            );
        }
	}
	
	/**
	 * Handle webhooks
	 */
	public function webhook_handler()
	{
		global $woocommerce;
		
		if (isset($_GET['sellixpaynonce']) && !wp_verify_nonce(sanitize_key($_GET['sellixpaynonce']), 'sellixpay-web_hook_handler')) {
			exit;
		}
		
		$data = json_decode(file_get_contents('php://input'), true);
		
		if ($this->debug_mode)
		$this->log('Webhook Handler received data: ' . $data);
		
		
		$sellix_order = $this->valid_sellix_order($data['data']['uniqid']);
		
		if ($this->debug_mode)
		$this->log('Concerning Sellix order: ' . $sellix_order);
		
		$viWcID = 0;

		if (isset($_REQUEST['wc_id'])) {
			$viWcID = sanitize_text_field(wp_unslash($_REQUEST['wc_id']));
		}
		
		if ($sellix_order) {
			$order = wc_get_order($viWcID);
	   
			if ($this->debug_mode)
			$this->log('Concerning Wordpress order: ' . $order);

			$this->log('Order #' . $viWcID . ' (' . $sellix_order['uniqid'] . '). Status: ' . $sellix_order['status']);

			if ($sellix_order['status'] == 'COMPLETED') {
				$this->complete_order($viWcID);
				//$order->payment_complete();
			} elseif ($sellix_order['status'] == 'WAITING_FOR_CONFIRMATIONS') {
				$order->update_status('on-hold', sprintf(__('Awaiting crypto currency confirmations', 'sellix-pay')));
			} elseif ($sellix_order['status'] == 'PARTIAL') {
				$order->update_status('on-hold', sprintf(__('Cryptocurrency payment only partially paid', 'sellix-pay')));
			}
		}
	}
				
	function sellix_post_authenticated_json_request( $route, $body = false, $extra_headers = false, $method="POST")
	{
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
	
	public function valid_sellix_order($order_uniqid)
	{
		$route = "/v1/orders/" . $order_uniqid;
		$response = $this->sellix_post_authenticated_json_request($route,'','','GET');
	   
		if ($this->debug_mode)
			$this->log('Order validation returned: ' . $response['body']);
		
		if (is_wp_error($response)) {
			mail(get_option('admin_email'), __('Unable to verify order via Sellix Pay API', 'sellix-pay'), $order_uniqid);
			return null;
		} elseif (isset($response['response']['code']) && $response['response']['code'] == 200) {
			$responseDecode = json_decode($response['body'], true);
			return $responseDecode['data']['order'];
		}
	}
	
	public function complete_order($wc_id) {
		global $woocommerce;
		$order = wc_get_order($wc_id);
		$order->update_status('completed');
	}

	public function option_exists($option_name) 
	{
		$value = get_option($option_name);
		return $value;
	}

	public function log($content)
    {
        $debug = $this->debug_mode;
        if ($debug == 'yes') {
			if (!$this->option_exists("woocommerce_sellixpay_logfile_prefix")) {
				$logfile_prefix = md5(uniqid(wp_rand(), true));
				update_option('woocommerce_sellixpay_logfile_prefix', $logfile_prefix);
			} else {
				$logfile_prefix = get_option('woocommerce_sellixpay_logfile_prefix');
				if (empty($logfile_prefix)) {
					$logfile_prefix = md5(uniqid(wp_rand(), true));
					update_option('woocommerce_sellixpay_logfile_prefix', $logfile_prefix);
				}
			}
			
			$filename = $logfile_prefix.'_sellixpay_debug.log';

            $file = ABSPATH .'wp-content/uploads/wc-logs/'.$filename;
			
            try {
				/*
				if (!defined( 'FS_CHMOD_FILE' ) ) {
                    define('FS_CHMOD_FILE', 0644);
                }				
				
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
				
				$filesystem = new WP_Filesystem_Direct( false );
				$filesystem->put_contents("\n".gmdate("Y-m-d H:i:s").": ".print_r($content, true));
				*/
				
				// @codingStandardsIgnoreStart
				/*
				We tried to use WP_Filesystem methods, look at the above commented out code block.
				But this put_contents method just writing the code not appending to the file.
				So we have only the last written content in the file.
				Because in the below method fopen initiated with 'wb' mode instead of 'a' or 'a+', otherwise this core method must be modified to able to pass the file open mode from the caller.
				public function put_contents( $file, $contents, $mode = false ) {
				$fp = @fopen( $file, 'wb' );
				*/
				$fp = fopen($file, 'a+');
                if ($fp) {
                    fwrite($fp, "\n".gmdate("Y-m-d H:i:s").": ".print_r($content, true));
                    fclose($fp);
                }
				// @codingStandardsIgnoreEnd
				
            } catch (\Exception $e) {}
        }
    }
}