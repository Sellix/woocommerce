<?php
/**
 * WC_SellixPay_Blocks_Support Class
 * Payments Blocks integration
 * @author: Sellix io 
 * @package: Sellix Pay
 * @since: 1.9.4
*/

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentResult;
use Automattic\WooCommerce\Blocks\Payments\PaymentContext;

final class WC_SellixPay_Blocks_Support extends AbstractPaymentMethodType {
    /**
     * The gateway instance.
     *
     * @var WC_Gateway
     */
    private $gateway;

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'sellix';
    
    /**
     * Initializes the payment method type.
     */
    public function initialize() {
        $this->settings = get_option( 'woocommerce_sellix_settings', [] );
        
        $payment_gateways_class   = WC()->payment_gateways();
        $payment_gateways         = $payment_gateways_class->payment_gateways();

        $this->gateway  = $payment_gateways['sellix'];
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active() {
        return $this->gateway->is_available();
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
        $asset_path   = SELLIX_BASE_PATH . 'build/frontend/blocks.asset.php';
        $version      = SELLIX_VERSION;
        $dependencies = [];
        if ( file_exists( $asset_path ) ) {
            $asset        = require $asset_path;
            $version      = is_array( $asset ) && isset( $asset['version'] )
                    ? $asset['version']
                    : $version;
            $dependencies = is_array( $asset ) && isset( $asset['dependencies'] )
                    ? $asset['dependencies']
                    : $dependencies;
        }
        wp_register_script(
                'wc-sellix-blocks-integration',
                SELLIX_BASE_URL . '/build/frontend/blocks.js',
                $dependencies,
                $version,
                true
        );
        
        if ( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( 'wc-sellix-blocks-integration', 'sellix', SELLIX_BASE_PATH . 'languages/' );
        }

        return [ 'wc-sellix-blocks-integration' ];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data() {
        return [
            'title'       => $this->get_setting( 'title' ),
            'description' => $this->get_setting( 'description' ),
            'supports'    => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] ),
            'logo_url'    => SELLIX_BASE_URL . '/assets/images/logo.png',
        ];
    }
}