<?php
/**
 * Plugin Name: Sellix Pay
 * Description: Accept Cryptocurrencies, Credit Cards, PayPal and regional banking methods with Sellix Pay.
 * Version: 1.4
 * Author:  Sellix io
 * Author URI: https://sellix.io/
 * Developer: Team Virtina (Harshal)
 * Developer URI: https://virtina.com
 * Text Domain: sellix-pay
 * Domain Path: /languages
 *
 * Requires at least: 4.9
 * Tested up to: 6.0.2
 * WC requires at least: 3.5
 * WC tested up to: 6.9.1
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

define('SELLIX_VERSION', '1.4');
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
                $this->icon = apply_filters('woocommerce_sellix_icon', SELLIX_BASE_URL . '/assets/images/single-black.webp');
                $this->method_title = __('Sellix', 'sellix-pay');
                $this->method_description  = $this->get_option('description');
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
                $this->payment_fields_layout = $this->get_option('payment_fields_layout') == 'yes' ? true : false;
                $this->confirmations = $this->get_option('confirmations');
                $this->paypal = $this->get_option('paypal') == 'yes' ? true : false;
                $this->stripe = $this->get_option('stripe') == 'yes' ? true : false;
                $this->cash_app = $this->get_option('cash_app') == 'yes' ? true : false;
                $this->bitcoin = $this->get_option('bitcoin') == 'yes' ? true : false;
                $this->concordium = $this->get_option('concordium') == 'yes' ? true : false;
                $this->tron = $this->get_option('tron') == 'yes' ? true : false;
                $this->litecoin = $this->get_option('litecoin') == 'yes' ? true : false;
                $this->ethereum = $this->get_option('ethereum') == 'yes' ? true : false;
                $this->dash = $this->get_option('dash') == 'yes' ? true : false;
                $this->bitcoin_cash = $this->get_option('bitcoin_cash') == 'yes' ? true : false;

                $this->usdt = $this->get_option('usdt') == 'yes' ? true : false;
                $this->usdt_erc20 = $this->get_option('usdt_erc20') == 'yes' ? true : false;
                $this->usdt_bep20 = $this->get_option('usdt_bep20') == 'yes' ? true : false;
                $this->usdt_trc20 = $this->get_option('usdt_trc20') == 'yes' ? true : false;

                $this->usdc = $this->get_option('usdc') == 'yes' ? true : false;
                $this->usdc_erc20 = $this->get_option('usdc_erc20') == 'yes' ? true : false;
                $this->usdc_bep20 = $this->get_option('usdc_bep20') == 'yes' ? true : false;

                $this->solana = $this->get_option('solana') == 'yes' ? true : false;
                $this->nano = $this->get_option('nano') == 'yes' ? true : false;
                $this->ripple = $this->get_option('ripple') == 'yes' ? true : false;
                $this->cronos = $this->get_option('cronos') == 'yes' ? true : false;
                $this->binance_coin = $this->get_option('binance_coin') == 'yes' ? true : false;
                $this->binance_pay = $this->get_option('binance_pay') == 'yes' ? true : false;
                $this->monero = $this->get_option('monero') == 'yes' ? true : false;

                $this->skrill = $this->get_option('skrill') == 'yes' ? true : false;
                $this->perfectmoney = $this->get_option('perfectmoney') == 'yes' ? true : false;			
                $this->log = new WC_Logger();     // Logger
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
                        <?php _e( 'Payment Method', 'sellix-pay' );?> <abbr class="required" title="required">*</abbr>
                    </label>



                    <?php
                    if ($this->payment_fields_layout){
                    if ($this->paypal){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels paypal">
                                <label class="paypal">
                                    <input type="radio" name="payment_gateway" value="PAYPAL" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/paypal.png','sellix-pay'); ?>" alt="Paypal" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'PayPal', 'sellix-pay' );?>
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($this->stripe){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels stripe">
                                <label class="stripe">
                                    <input type="radio" name="payment_gateway" value="STRIPE" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/stripe.png','sellix-pay'); ?>" alt="Stripe" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'Stripe', 'sellix-pay' );?> 
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($this->cash_app){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels cash_app">
                                <label class="cash_app">
                                    <input type="radio" name="payment_gateway" value="CASH_APP" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/cash-app.png','sellix-pay'); ?>" alt="Cash App" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'Cash App', 'sellix-pay' );?>
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($this->concordium){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels concordium">
                                <label class="concordium">
                                    <input type="radio" name="payment_gateway" value="CONCORDIUM" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/concordium.png','sellix-pay'); ?>" alt="Concordium" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'Concordium (CCD)', 'sellix-pay' );?>
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($this->bitcoin){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels bitcoin">
                                <label class="bitcoin">
                                    <input type="radio" name="payment_gateway" value="BITCOIN" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/bitcoin.png','sellix-pay'); ?>" alt="Bitcoin" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'Bitcoin (BTC)', 'sellix-pay' );?>
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($this->tron){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels tron">
                                <label class="tron">
                                    <input type="radio" name="payment_gateway" value="TRON" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/tron.png','sellix-pay'); ?>" alt="Tron" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'Tron (TRX)', 'sellix-pay' );?> 
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($this->litecoin){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels litecoin">
                                <label class="litecoin">
                                    <input type="radio" name="payment_gateway" value="LITECOIN" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/litecoin.png','sellix-pay'); ?>" alt="LITECOIN" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'Litecoin (LTC)', 'sellix-pay' );?>
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($this->ethereum){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels ethereum">
                                <label class="ethereum">
                                    <input type="radio" name="payment_gateway" value="ETHEREUM" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/ethereum.png','sellix-pay'); ?>" alt="Ethereum" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'Ethereum (ETH)', 'sellix-pay' );?>
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($this->bitcoin_cash){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels bitcoin_cash">
                                <label class="bitcoin_cash">
                                    <input type="radio" name="payment_gateway" value="BITCOIN_CASH" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/bitcoin-cash.png','sellix-pay'); ?>" alt="Bitcoin Cash" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'Bitcoin Cash (BCH)', 'sellix-pay' );?>
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if (($this->usdt && $this->usdt_erc20 ) || ($this->usdt && $this->usdt_bep20 ) || ($this->usdt && $this->usdt_trc20 ) ){ ?>

                                <?php if ($this->usdt_erc20){ ?>
                                    <div class="payment-labels-container">
                                        <div class="payment-labels usdt_erc20">
                                            <label class="usdt_erc20">
                                                <input type="radio" name="payment_gateway" value="USDT:ERC20" />
                                                <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/usdt.png','sellix-pay'); ?>" alt="Usdt Erc20" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'USDT ERC20', 'sellix-pay' );?> 
                                            </label>
                                        </div>
                                    </div>
                                <?php } ?>


                                <?php if ($this->usdt_bep20){ ?>
                                    <div class="payment-labels-container">
                                        <div class="payment-labels usdt_bep20">
                                            <label class="usdt_bep20">
                                                <input type="radio" name="payment_gateway" value="USDT:BEP20" />
                                                <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/usdt.png','sellix-pay'); ?>" alt="Usdt Bep20" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'USDT BEP20', 'sellix-pay' );?> 
                                            </label>
                                        </div>
                                    </div>
                                <?php } ?>


                                <?php if ($this->usdt_trc20){ ?>
                                    <div class="payment-labels-container">
                                        <div class="payment-labels usdt_trc20">
                                            <label class="usdt_trc20">
                                                <input type="radio" name="payment_gateway" value="USDT:TRC20" />
                                                <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/usdt.png','sellix-pay'); ?>" alt="Usdt Trc20" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'USDT TRC20', 'sellix-pay' );?>
                                            </label>
                                        </div>
                                    </div>
                                <?php } ?>

                    <?php } ?>


                    <?php if (($this->usdc && $this->usdc_erc20) || ($this->usdc && $this->usdc_bep20) ){ ?>
                            <?php if ($this->usdc_erc20){ ?>
                                    <div class="payment-labels-container">
                                        <div class="payment-labels usdc_erc20">
                                            <label class="usdc_erc20">
                                                <input type="radio" name="payment_gateway" value="USDC:ERC20" />
                                                <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/usdc.png','sellix-pay'); ?>" alt="Usdc Erc20" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'USDC ERC20', 'sellix-pay' );?> 
                                            </label>
                                        </div>
                                    </div>
                            <?php } ?>

                                <?php if ($this->usdc_bep20){ ?>
                                    <div class="payment-labels-container">
                                        <div class="payment-labels usdc_bep20">
                                            <label class="usdc_bep20">
                                                <input type="radio" name="payment_gateway" value="USDC:BEP20" />
                                                <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/usdc.png','sellix-pay'); ?>" alt="Usdc Bep20" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'USDC BEP20', 'sellix-pay' );?> 
                                            </label>
                                        </div>
                                    </div>
                                <?php } ?>
  
                    <?php } ?>


                    <?php if ($this->solana){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels solana">
                                <label class="solana">
                                    <input type="radio" name="payment_gateway" value="SOLANA" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/solana.png','sellix-pay'); ?>" alt="Solana" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'Solana (SOL)', 'sellix-pay' );?>
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($this->nano){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels nano">
                                <label class="nano">
                                    <input type="radio" name="payment_gateway" value="NANO" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/nano.png','sellix-pay'); ?>" alt="Nano" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'Nano (XNO)', 'sellix-pay' );?>
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($this->ripple){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels ripple">
                                <label class="ripple">
                                    <input type="radio" name="payment_gateway" value="RIPPLE" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/ripple.png','sellix-pay'); ?>" alt="Ripple" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'Ripple (XRP)', 'sellix-pay' );?>
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($this->cronos){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels cronos">
                                <label class="cronos">
                                    <input type="radio" name="payment_gateway" value="CRONOS" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/cronos.png','sellix-pay'); ?>" alt="Cronos" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'Cronos (CRO)', 'sellix-pay' );?>
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($this->binance_coin){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels binance_coin">
                                <label class="binance_coin">
                                    <input type="radio" name="payment_gateway" value="BINANCE_COIN" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/binance.png','sellix-pay'); ?>" alt="Binance Coin" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'Binance Coin (BNB)', 'sellix-pay' );?>
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($this->binance_pay){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels binance_pay">
                                <label class="binance_pay">
                                    <input type="radio" name="payment_gateway" value="BINANCE_PAY" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/binance.png','sellix-pay'); ?>" alt="Binance Pay" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'Binance Pay (BUSD)', 'sellix-pay' );?> 
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($this->monero){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels monero">
                                <label class="monero">
                                    <input type="radio" name="payment_gateway" value="MONERO" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/monero.png','sellix-pay'); ?>" alt="Monero" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'Monero (XMR)', 'sellix-pay' );?>
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($this->skrill){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels skrill">
                                <label class="skrill">
                                    <input type="radio" name="payment_gateway" value="SKRILL" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/skrill.png','sellix-pay'); ?>" alt="Skrill" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'Skrill', 'sellix-pay' );?>
                                </label>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($this->perfectmoney){ ?>
                        <div class="payment-labels-container">
                            <div class="payment-labels perfectmoney">
                                <label class="perfectmoney">
                                    <input type="radio" name="payment_gateway" value="PERFECTMONEY" />
                                    <img src="<?php _e( SELLIX_BASE_URL. '/assets/images/pm.png','sellix-pay'); ?>" alt="PerfectMoney" style="border-radius: 0px;" width="20" height="20"> <?php _e( 'PerfectMoney', 'sellix-pay' );?> 
                                </label>
                            </div>
                        </div>
                    <?php } 
                    }else{
                    ?>


                    <select name="payment_gateway" class="sellix-payment-gateway-select">
                        <?php if ($this->bitcoin){ ?><option value="BITCOIN"><?php _e( 'Bitcoin (BTC)', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->ethereum){ ?><option value="ETHEREUM"><?php _e( 'Ethereum (ETH)', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->bitcoin_cash){ ?><option value="BITCOIN_CASH"><?php _e( 'Bitcoin Cash (BCH)', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->litecoin){ ?><option value="LITECOIN"><?php _e( 'Litecoin (LTC)', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->concordium){ ?><option value="CONCORDIUM"><?php _e( 'Concordium (CCD)', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->tron){ ?><option value="TRON"><?php _e( 'Tron (TRX)', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->nano){ ?><option value="NANO"><?php _e( 'Nano (XNO)', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->monero){ ?><option value="MONERO"><?php _e( 'Monero (XMR)', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->ripple){ ?><option value="RIPPLE"><?php _e( 'Ripple (XRP)', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->solana){ ?><option value="SOLANA"><?php _e( 'Solana (SOL)', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->cronos){ ?><option value="CRONOS"><?php _e( 'Cronos (CRO)', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->binance_coin){ ?><option value="BINANCE_COIN"><?php _e( 'Binance Coin (BNB)', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->paypal){ ?><option value="PAYPAL"><?php _e( 'PayPal', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->stripe){ ?><option value="STRIPE"><?php _e( 'Stripe', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->cash_app){ ?><option value="CASH_APP"><?php _e( 'Cash App', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->usdt_erc20 && $this->usdt){ ?><option value="USDT:ERC20"><?php _e( 'USDT:ERC20', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->usdt_bep20 && $this->usdt){ ?><option value="USDT:BEP20"><?php _e( 'USDT:BEP20', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->usdt_trc20 && $this->usdt){ ?><option value="USDT:TRC20"><?php _e( 'USDT:TRC20', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->usdc_erc20 && $this->usdc){ ?><option value="USDC:ERC20"><?php _e( 'USDC:ERC20', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->usdc_bep20 && $this->usdc){ ?><option value="USDC:BEP20"><?php _e( 'USDC:BEP20', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->binance_pay){ ?><option value="BINANCE_PAY"><?php _e( 'Binance Pay (BUSD)', 'sellix-pay' );?></option><?php } ?>


                        <?php if ($this->skrill){ ?><option value="SKRILL"><?php _e( 'Skrill', 'sellix-pay' );?></option><?php } ?>
                        <?php if ($this->perfectmoney){ ?><option value="PERFECTMONEY"><?php _e( 'PerfectMoney', 'sellix-pay' );?></option><?php } ?>
                    </select>
                    <?php } ?>
                    <p style="margin-top:10px;"><?php _e( $this->description, 'sellix-pay' ); ?></p>
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
                    'email' => [
                        'title' => __('Email', 'sellix-pay'),
                        'type' => 'text',
                        'description' => __('Please enter your Sellix email address.', 'sellix-pay'),
                        'default' => '',
                    ],
                    'api_key' => [
                        'title' => __('API Key', 'sellix-pay'),
                        'type' => 'text',
                        'description' => __('Please enter your Sellix API Key.', 'sellix-pay'),
                        'default' => '',
                    ],
                    'order_id_prefix' => [
                        'title' => __('Order ID Prefix', 'sellix-pay'),
                        'type' => 'text',
                        'description' => __('The prefix before the order number. For example, a prefix of "Order #" and a ID of "10" will result in "Order #10"', 'sellix-woocommerce'),
                        'default' => 'Order #',
                    ],
                    'payment_fields_layout' => [
                        'title' => __('Radio Box Layout', 'sellix-pay'),
                        'label' => 'Activate/Deactivate',
                        'type' => 'checkbox',
                        'description' => __('Default layout is dropdown', 'sellix-pay'),
                        'default' => 'no',
                        'desc_tip' => true,
                    ],
                    'confirmations' => [
                        'title' => __('Number of confirmations for crypto currencies', 'sellix-pay'),
                        'type' => 'number',
                        'description' => __('The default of 1 is advised for both speed and security', 'sellix-pay'),
                        'default' => '1'
                    ],
                    'paypal' => [
                        'title' => __('Accept PayPal', 'sellix-pay'),
                        'label' => __('Enable/Disable PayPal', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'stripe' => [
                        'title' => __('Accept Stripe', 'sellix-pay'),
                        'label' => __('Enable/Disable Stripe', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'cash_app' => [
                        'title' => __('Accept Cash App', 'sellix-pay'),
                        'label' => __('Enable/Disable Cash App', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'bitcoin' => [
                        'title' => __('Accept Bitcoin', 'sellix-pay'),
                        'label' => __('Enable/Disable Bitcoin', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'concordium' => [
                        'title' => __('Accept Concordium', 'sellix-pay'),
                        'label' => __('Enable/Disable Concordium', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'tron' => [
                        'title' => __('Accept Tron', 'sellix-pay'),
                        'label' => __('Enable/Disable Tron', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'litecoin' => [
                        'title' => __('Accept Litecoin', 'sellix-pay'),
                        'label' => __('Enable/Disable Litecoin', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'ethereum' => [
                        'title' => __('Accept Ethereum', 'sellix-pay'),
                        'label' => __('Enable/Disable Ethereum', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'bitcoin_cash' => [
                        'title' => __('Accept Bitcoin Cash', 'sellix-pay'),
                        'label' => __('Enable/Disable Bitcoin Cash', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'usdt' => [
                        'title' => __('Accept USDT', 'sellix-pay'),
                        'label' => __('Enable/Disable USDT', 'sellix-pay'),
                        'type' => 'checkbox',
                        'description' => __('You have to select one from below USDT:ERC20 , USDT:BEP20 OR USDT:TRC20 if enable usdt', 'sellix-woocommerce'),
                        'default' => 'no',
                    ],
                    'usdt_erc20' => [
                        'title' => __('Accept USDT:ERC20', 'sellix-pay'),
                        'label' => __('Enable/Disable USDT:ERC20', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'usdt_bep20' => [
                        'title' => __('Accept USDT:BEP20', 'sellix-pay'),
                        'label' => __('Enable/Disable USDT:BEP20', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'usdt_trc20' => [
                        'title' => __('Accept USDT:TRC20', 'sellix-pay'),
                        'label' => __('Enable/Disable USDT:TRC20', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'usdc' => [
                        'title' => __('Accept USDC', 'sellix-pay'),
                        'label' => __('Enable/Disable USDC', 'sellix-pay'),
                        'type' => 'checkbox',
                        'description' => __('You have to select one from below USDC:ERC20 OR USDC:BEP20 if enable usdc', 'sellix-pay'),
                        'default' => 'no',
                    ],
                    'usdc_erc20' => [
                        'title' => __('Accept USDC:ERC20', 'sellix-pay'),
                        'label' => __('Enable/Disable USDC:ERC20', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'usdc_bep20' => [
                        'title' => __('Accept USDC:BEP20', 'sellix-pay'),
                        'label' => __('Enable/Disable USDC:BEP20', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'solana' => [
                        'title' => __('Accept Solana', 'sellix-pay'),
                        'label' => __('Enable/Disable Solana', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'nano' => [
                        'title' => __('Accept Nano', 'sellix-pay'),
                        'label' => __('Enable/Disable Nano', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'ripple' => [
                        'title' => __('Accept Ripple', 'sellix-pay'),
                        'label' => __('Enable/Disable Ripple', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'cronos' => [
                        'title' => __('Accept Cronos', 'sellix-pay'),
                        'label' => __('Enable/Disable Cronos', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'binance_coin' => [
                        'title' => __('Accept Binance Coin', 'sellix-pay'),
                        'label' => __('Enable/Disable Binance Coin', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'binance_pay' => [
                        'title' => __('Accept Binance Pay', 'sellix-pay'),
                        'label' => __('Enable/Disable Binance Pay', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'monero' => [
                        'title' => __('Accept Monero', 'sellix-pay'),
                        'label' => __('Enable/Disable Monero', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'skrill' => [
                        'title' => __('Accept Skrill', 'sellix-pay'),
                        'label' => __('Enable/Disable Skrill', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                    'perfectmoney' => [
                        'title' => __('Accept PerfectMoney', 'sellix-pay'),
                        'label' => __('Enable/Disable PerfectMoney', 'sellix-pay'),
                        'type' => 'checkbox',
                        'default' => 'no',
                    ],
                ];

            }


            function generate_sellix_payment($order)
            {
                if (array_key_exists('payment_gateway', $_POST) && filter_var($_POST['payment_gateway'], FILTER_SANITIZE_STRING)) {
                
                    $params = [
                        'title' => $this->order_id_prefix . $order->get_id(),
                        'currency' => $order->get_currency(),
                        'return_url' => $this->get_return_url($order),
                        'webhook' => add_query_arg('wc_id', $order->get_id(), $this->webhook_url),
                        'email' => $order->get_billing_email(),
                        'value' => $order->get_total(),
                        'gateway' => sanitize_text_field($_POST['payment_gateway']),
                        'confirmations' => $this->confirmations
                    ];

                    $route = "/v1/payments";
                    $response = $this->sellix_post_authenticated_json_request($route, $params);

                    if (is_wp_error($response)) {
                        return wc_add_notice(__('Payment error:', 'sellix-pay') . 'Sellix API error: ' . print_r($response->errors, true), 'error');
                    } elseif (isset($response['response']['code']) && $response['response']['code'] == 200) {
                        //  return $response['body'];
                        $responseDecode = json_decode($response['body'], true);
                        return $responseDecode['data']['url'];
                    }
                }else{
                    return wc_add_notice(__('Payment Gateway Error', 'sellix-pay') . 'Sellix Before API error: Payment Method Not Selected OR Something Wrong', 'error');
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

/**
* Loads front side style
*
* @version 1.0
*/
add_action('wp_enqueue_scripts',  'sellix_load_front_styles');
function sellix_load_front_styles() {
    
    if ( is_checkout() ) {         
        wp_enqueue_style( 'sellix-css', SELLIX_BASE_URL.'/assets/css/sellix.css', array(),SELLIX_VERSION,'all' );
    }        
}