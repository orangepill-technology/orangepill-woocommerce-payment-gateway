<?php use 

Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
final class Orangepill_Payment_Block extends AbstractPaymentMethodType {
    private $gateway;
    protected $name = 'orangepill_gateway';// your payment gateway name
    public function initialize() {
        $this->settings = get_option( 'woocommerce_orangepill_gateway_settings', [] );
        $this->gateway = new Orangepill_Gateway();
    }
    public function is_active() {
        return $this->gateway->is_available();
    }
    public function get_payment_method_script_handles() {
        wp_register_script(
            'wc-orangepill-blocks-integration',
            plugin_dir_url(__FILE__) . 'block/checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            '1.0.7',
            true
        ); 
        return [ 'wc-orangepill-blocks-integration' ];
    }
    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'html'=> $this->gateway->payment_fields(true)
        ];
    }
}