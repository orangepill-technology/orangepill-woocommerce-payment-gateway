<?php
/**
 * Plugin Name: Orangepill Wallet Payments for WooCommerce
 * Description: Allow your customers to pay with Orangepill Wallet.
 * Author: Orangepill devs@orangepill.cloud
 * Version: 1.1.1
 *
 */
defined( 'ABSPATH' ) or exit;
define('ORANGEPILL_VERSION', '0.0.1');
if ( ! in_array( 'woocommerce/woocommerce.php',  get_option( 'active_plugins' ) ) ) {
    return;
}
/**
 * Add the gateway to WC Available Gateways
 *  
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + offline gateway
 */
function orangepill_add_to_gateways( $gateways ) {
    $gateways[] = 'Orangepill_Gateway';
    return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'orangepill_add_to_gateways' );

/**
 * Adds plugin page links
 * 
 * @since 1.0.0
 * @param array $plugin_links all plugin links
 * @return array $plugin_links all plugin links + our custom links (i.e., "Settings")
 */
function orangepill_gateway_plugin_links( $plugin_links ) {

    $plugin_links[] =  
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=orangepill_gateway' ) . '">' . __( 'Settings', 'wc-orangepill-gateway' ) . '</a>';

    return $plugin_links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'orangepill_gateway_plugin_links' );
 
add_action( 'plugins_loaded', 'orangepill_gateway_init', 11 );

/**
 * Includes the class of the payment gateway.
 * 
 * @since 1.0.0 
 */ 
function orangepill_gateway_init() {
    require_once "payment-gateway.php";
}

add_action( 'init', 'orangepill_gateway_add_endpoint' ); 


/**
 * Generate QR image with URL
 * 
 * @since 1.1.0 
 */ 
function orangepill_qr_code(){
    require_once "phpqrcode/qrlib.php";
    if(!isset($_GET['qrencode'])) return;
    QRcode::png($_GET['qrencode']);
    die();
}

add_action( 'init', 'orangepill_qr_code');

/**
 * Detects the URL of the endpoint and then proceeds to handle the webhook for OrangePill.
 * 
 * @since 1.0.0 
 */ 
function orangepill_gateway_add_endpoint(){
             
            if( strpos( $_SERVER['REQUEST_URI'], 'orangepill/wallet-payment') !== FALSE ) {
                $jsonData = file_get_contents('php://input');
                if(!$jsonData || trim($jsonData)=='') die('No data');
                $data = json_decode($jsonData, true);
                if(!$data || !isset($data['id'])) die(var_dump($data));
                require_once "payment-gateway.php";
                 
                $pg = new Orangepill_Gateway();
                $pg->process_webhook($data);
                die();
            } 
}
/**
 * Includes the Font Awesome icons library.
 * 
 * @since 1.0.0 
 */ 
function orangepill_gateway_wp_enqueue_scripts(){
    wp_enqueue_style('fawesome','https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',[],ORANGEPILL_VERSION);
}

add_action( 'wp_enqueue_scripts', 'orangepill_gateway_wp_enqueue_scripts');
 
/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
*/
function orangepill_compatibility() { 
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) { 
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
} 
add_action('before_woocommerce_init', 'orangepill_compatibility');


add_action( 'woocommerce_blocks_loaded', 'orange_register_pm_type' );

/**
 * Custom function to register a payment method type
 */
function orange_register_pm_type() { 
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    } 
    require_once plugin_dir_path(__FILE__) . 'includes/class-orangepill-block-checkout.php'; 
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) { 
            $payment_method_registry->register( new Orangepill_Payment_Block );
        }
    );
}

/**
 * Remove pay button in my orders actions when payment method is Orangepill.
 *
 * @param  array $actions
 * @param  WC_Order $order
 * @return array
 */
function orangepill_remove_pay( $actions, $order ) {
	$method = wc_get_payment_gateway_by_order($order->get_id()); 
	if ( $method->id == 'orangepill_gateway' ) {
		unset($actions['pay']);
		
	}

	return $actions;
}

add_filter( 'woocommerce_my_account_my_orders_actions', 'orangepill_remove_pay', 50, 2 );