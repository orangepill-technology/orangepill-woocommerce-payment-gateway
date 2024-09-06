<?php

/** 
 *
 * The main class of the Orangepill payment method
 *
 * @class       Orangepill_Gateway
 * @extends     WC_Payment_Gateway 
 */

    class Orangepill_Gateway extends WC_Payment_Gateway {

        /**
         * Constructor for the gateway.
         */
        public function __construct() {
      
            $this->id                 = 'orangepill_gateway'; 
            $this->has_fields         = false;
            $this->method_title       = __( 'Orangepill', 'wc-orangepill-gateway' );
            $this->method_description = __( 'This will allow your customers to pay using Orangepill API.', 'wc-orangepill-gateway' );
          
            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();
          
            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->icon = $this->get_option( 'icon', plugin_dir_url(__FILE__).'img/orangepill_logo.jpg');
            $this->project_id = $this->get_option( 'project_id' );
            $this->username = $this->get_option( 'username' );
            $this->password = $this->get_option( 'password' );
            $this->merchant_name = $this->get_option( 'merchant_name' );
            $this->merchant_alias = $this->get_option( 'merchant_alias' );
            $this->whatsapp_number = $this->get_option( 'whatsapp_number' );
           
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) ); 
            add_action( 'woocommerce_checkout_create_order', [$this,'save_customid'], 20, 2 );
            add_action( 'woocommerce_thankyou', [$this,'orangepill_render'], 20 );
            add_action( 'woocommerce_view_order', [$this,'orangepill_render'], 20 );

        }   

        function orangepill_render( $order_id ){ 
            if(isset($GLOBALS['drawn'])) return;
            $GLOBALS['drawn']=true;
            $tempid = get_post_meta($order_id, 'paymentidfororangepill', true);
            $id = get_post_meta($order_id, 'requestidfromorangepill', true);
            
            $method = wc_get_payment_gateway_by_order($order_id);  
            if($method->id != 'orangepill_gateway') return;
            $title = $this->get_option('title');
            $qr = trim($this->get_qr($id),'"');
         ?>
         <h2 class="woocommerce-column__title"><?php _e('Pay with '.$title, 'wc-orangepill-gateway'); ?></h2>
            <table class="shop_table shop_table_responsive additional_info">
                <tbody>
                    <tr>
                         <td colspan="2"><?php 

                            $string='<style>
                .fas { 
  font-family: "Font Awesome 5 Brands";
}
.fa-copy{
    cursor: pointer;
}
#copied,#copied2{
    visibility: hidden;
    font-size: .9em;
}
.woocommerce-checkout #payment ul.payment_methods li #requestidfromorangepill{
    margin:0 0 0 .5em;
}
.orangepill-pay-whatsapp,
.orangepill-pay-code{
    display: flex;
    align-items: center;
    flex-direction: column;
}
.orangepill-pay-whatsapp{
    margin-top: .5em;

}
.orangepill-payment-area{
    display: flex;flex-direction: column; align-items:center;width:100%
}

#requestidfromorangepill,
#orangepill-whatsapp-number{
    margin:0 !important;
}

            </style>
            <script>async function copyToClipboard(textToCopy,n) {
    // Navigator clipboard api needs a secure context (https)
    if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(textToCopy);
    } else {  
        const textArea = document.createElement("textarea");
        textArea.value = textToCopy;
             
        textArea.style.position = "absolute";
        textArea.style.left = "-999999px";
            
        document.body.prepend(textArea);
        textArea.select();

        try {
            document.execCommand(\'copy\');
            jQuery(\'#copied\'+n).css(\'visibility\',\'visible\');setTimeout(function(){ jQuery(\'#copied\').css(\'visibility\',\'hidden\'); },3000);
        } catch (error) {
            console.error(error);
        } finally {
            textArea.remove();
        }
    }
}</script>
            <div class="orangepill-payment-area order_details">
            <div class="orangepill-pay-code">
            
                <strong>'.__('Payment Request Code', 'wc-orangepill-gateway').'</strong>
                <input type="hidden" name="paymentidfororangepill" value="'.$tempid.'">
                <span class="orangepill-copy-area"><input type="text" readonly id="requestidfromorangepill" name="requestidfromorangepill" value="'.$id.'"> <i class="fa-solid fa-copy" title="Copy" onclick="copyToClipboard(\''.$id.'\',\'\');"></i> </span>
                <span id="copied">'.__('Copied!', 'wc-orangepill-gateway').'</span>
            </div>
            <div class="orangepill-pay-whatsapp">
                        <strong style="font-size:1.25em;">'.__('Pay with WhatsApp', 'wc-orangepill-gateway').'  <i class="fas fa-whatsapp"></i></strong>
                        <span class="orangepill-copy-area"><a id="orangepill-whatsapp-number" class="orangepill-whatsapp" target="_blank" href="https://wa.me/'.$this->get_option( 'whatsapp_number' ).'?text=payment code '.$id.'">'.$this->get_option( 'whatsapp_number' ).'</a> <i class="fa-solid fa-copy" title="Copy" onclick="copyToClipboard(\''.$this->get_option( 'whatsapp_number' ).'\',\'2\');"></i></span>
            </div>
                <span id="copied2">'.__('Copied!', 'wc-orangepill-gateway').'</span>
            
                <img style="margin: 8px; padding: 0;width:200px;" src="'.site_url('?qrencode='.urlencode('https://wa.me/'.$this->get_option( 'whatsapp_number' ).'?text=payment code '.$id)).'">
            
            </div>';
            
                echo $string; ?></td>
                    </tr>
                </tbody>
            </table>
        <?php }
    
        /**
         * Initialize Gateway Settings Form Fields
         */
        public function init_form_fields() {
      
            $this->form_fields = apply_filters( 'wc_orangepill_form_fields', array(
          
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', 'wc-orangepill-gateway' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Orangepill Payment', 'wc-orangepill-gateway' ),
                    'default' => 'yes'
                ),
                
                'title' => array(
                    'title'       => __( 'Payment Method Title', 'wc-orangepill-gateway' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-orangepill-gateway' ),
                    'default'     => __( 'Orangepill', 'wc-orangepill-gateway' ),
                    'desc_tip'    => true,
                ),
                
                'icon' => array(
                    'title'       => __( 'Payment Method Icon', 'wc-orangepill-gateway' ),
                    'type'        => 'text',
                    'description' => __( 'URL to the icon image that the customer will see on your checkout.', 'wc-orangepill-gateway' ),
                    'default'     => plugin_dir_url(__FILE__).'img/orangepill_logo.jpg',
                    'desc_tip'    => true,
                ),
                
                'description' => array(
                    'title'       => __( 'Payment Method Description', 'wc-orangepill-gateway' ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-orangepill-gateway' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                
                'username' => array(
                    'title'       => __( 'Orangepill API Username', 'wc-orangepill-gateway' ),
                    'type'        => 'text',
                    'description' => __( 'Your Orangepill Project Username on https://orangepill.cloud', 'wc-orangepill-gateway' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                
                'password' => array(
                    'title'       => __( 'Orangepill API Password', 'wc-orangepill-gateway' ),
                    'type'        => 'password',
                    'description' => __( 'Your Orangepill Project Password on https://orangepill.cloud', 'wc-orangepill-gateway' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                
                'project_id' => array(
                    'title'       => __( 'Orangepill Project Key', 'wc-orangepill-gateway' ),
                    'type'        => 'text',
                    'description' => __( 'Your Orangepill Project Key on https://orangepill.cloud', 'wc-orangepill-gateway' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                
                'merchant_name' => array(
                    'title'       => __( 'Merchant Name', 'wc-orangepill-gateway' ),
                    'type'        => 'text',
                    'description' => __( 'Name of the merchant company.', 'wc-orangepill-gateway' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                
                'merchant_alias' => array(
                    'title'       => __( 'Merchant Alias', 'wc-orangepill-gateway' ),
                    'type'        => 'text',
                    'description' => __( 'Alias of the merchant on https://orangepill.cloud', 'wc-orangepill-gateway' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),

                'whatsapp_number' => array(
                    'title'       => __( 'Conversational Wallet WhatsApp Number (optional)', 'wc-orangepill-gateway' ),
                    'type'        => 'text',
                    'description' => __( 'WhatsApp number of your chatbot wallet.', 'wc-orangepill-gateway' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            ) );
        }

        /**
         * Builds the Orangepill API Key, required for authentication in every request.
         */
        public function get_api_key(){
            $project_id = $this->get_option( 'project_id' );
            $username = $this->get_option( 'username' );
            $password = $this->get_option( 'password' );
            $key = $project_id.':'.$username.':'.$password;
            return base64_encode($key);
        }

        /**
         * Uses OrangePill API to  create a Payment Request
         * @return string The request ID.
         */
        public function get_request_id($data){
            $url = "https://api.orangepill.cloud/v1/apps/payment";
            $rs = wp_remote_post($url,array( 
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'headers' => array("Content-Type" => "application/json; charset=utf-8",
                                            'x-api-key' => $this->get_api_key() ),
                        'body' => json_encode($data)
                        ));
            if ( is_wp_error( $rs ) ) {
                die('ERROR');
            }
            else{
                $response = json_decode($rs['body']);  
                return $response->id;
            }
        }

        /**
         * Fetches the QR source using the payment request ID.
         * @return string image content ready to be displayed into src attribute.
         */
        public function get_qr($id){
            $url = "https://api.orangepill.cloud/v1/apps/payment/$id/qr/base64/300";
            $rs = wp_remote_get($url,array( 
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'headers' => array("Content-type" => "application/json",
                                            'x-api-key' => $this->get_api_key() )
                        ));
            if ( is_wp_error( $rs ) ) {
                die('ERROR');
            }
            else{
                return $rs['body']; 
            }
        }

        /**
         * Fetches the Orangepill payment  using the payment request ID.
         * @return object the payment request .
         */
        public function get_payment($id){
            $url = "https://api.orangepill.cloud/v1/apps/payment/$id";
            $rs = wp_remote_get($url,array( 
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'headers' => array("Content-type" => "application/json",
                                            'x-api-key' => $this->get_api_key() )
                        ));
            if ( is_wp_error( $rs ) ) {
                die('ERROR');
            }
            else{
                $response = json_decode($rs['body']); 
                var_dump($response); 
            }
        }

        /**
         * Fetches the Orangepill payment status using the payment request ID.
         * @return string the payment request status.
         */
        public function get_payment_status($id){
            $url = "https://api.orangepill.cloud/v1/apps/payment/$id";
            $rs = wp_remote_get($url,array( 
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'headers' => array("Content-type" => "application/json",
                                            'x-api-key' => $this->get_api_key() )
                        ));
            if ( is_wp_error( $rs ) ) {
                die('ERROR');
            }
            else{
                $response = json_decode($rs['body']); 
                return $response->status; 
            }
        }

        /**
         * Updates order to processing once Orangepill pings site.
         */
        public function process_webhook($data){
        
            $request_id = $data['id']; 
            global $wpdb;
            $post = $wpdb->get_row("SELECT  post_id FROM {$wpdb->postmeta} WHERE meta_value='$request_id' AND meta_key='requestidfromorangepill'");


            if(!$post){ 
            echo json_encode(['success'=>false,'message'=>'Order not found']);
                  exit();
            } 
            $order_id = $post->post_id;
            $order = wc_get_order( $order_id );
            $status = $this->get_payment_status($request_id);

            if( strtoupper($status) == 'DONE'){
                $order->update_status( 'processing' ); 
                $mailer = WC()->mailer();
                $mails = $mailer->get_emails();
                if ( ! empty( $mails ) ) {                
                    foreach ( $mails as $mail ) {
                        if ( $mail->id == 'customer_processing_order' ){
                            $mail->trigger( $order->id );                    
                        }                 
                    }            
                }
            } 
            echo json_encode(['success'=>true]);
            exit();
        }

        /**
         * Renders Orangepill form in WooCommerce checkout.
         */
        public function payment_fields($return_html=false) {
            if(!$this->get_option( 'merchant_alias' ) 
                || trim($this->get_option( 'merchant_alias' )) == ''
                || !$this->get_option( 'merchant_name' ) 
                || trim($this->get_option( 'merchant_name' )) == ''
                || !$this->get_option( 'username' ) 
                || trim($this->get_option( 'username' )) == ''
                || !$this->get_option( 'password' ) 
                || trim($this->get_option( 'password' )) == ''
                || !$this->get_option( 'project_id' ) 
                || trim($this->get_option( 'project_id' )) == '' ){
                $string = '<p>' ._e('Orangepill payment settings are incomplete. Please complete them before enabling the payment method','wc-orangepill-gateway').'</p>';
                if($return_html){
                    return $string;
                }
                echo $string;
                return;
            }
            //$qr = trim($this->get_qr($id),'"');
            $string='<i>'.$this->get_option( 'description' ).'</i>';
            if($return_html){
                    return $string;
                }
                echo $string;
        }

        function save_customid( $order, $data ) {
            if( ! isset($_POST['paymentidfororangepill']) ) return;

            if( ! empty($_POST['paymentidfororangepill']) ){
                $order->update_meta_data( 'paymentidfororangepill', sanitize_text_field( $_POST['paymentidfororangepill'] ) );
            }
            if( ! empty($_POST['requestidfromorangepill']) ){
                $order->update_meta_data( 'requestidfromorangepill', sanitize_text_field( $_POST['requestidfromorangepill'] ) );
            }
        }

        /**
         * Process the payment and return the result
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment( $order_id ) {
            
            $exists = get_post_meta($order_id,'requestidfromorangepill',true);
            if($exists && trim($exists)!=''){
                return array(
                    'result'    => 'success',
                    'redirect'  => $this->get_return_url( $order )
                );
            }

            $order = wc_get_order( $order_id );

            $tempid = substr(md5(rand(0,99999999)),0,6); 
            $amount = $order->get_total();
            $currency = get_option('woocommerce_currency');
            $store_name = get_bloginfo( 'name' );
            $merchant_alias = $this->get_option( 'merchant_alias' );
            $merchant_name = $this->get_option( 'merchant_name' );
            $data = [
                        'version' => '1.0',
                        'destination' => 
                                        ['alias' => $merchant_alias],
                        'amount' => $amount,
                        'asset' => $currency,
                        'description' => "Order $order_id from WooCommerce",
                        'data' => [
                                    'callback' => site_url('/orangepill/wallet-payment'),
                                    'system' => 'WOOCOMMERCE',
                                    'store' => $store_name,
                                    'merchant' => $merchant_name,
                                    'order' => $order_id
                                    ]
                    ];

            $id = $this->get_request_id($data);
            
            update_post_meta($order_id,'requestidfromorangepill',$id);
            
                $mailer = WC()->mailer();
                $mails = $mailer->get_emails();
                if ( ! empty( $mails ) ) {                
                    foreach ( $mails as $mail ) {
                        if ( $mail->id == 'new_order' ){
                            $mail->trigger( $order->id );                    
                        }                 
                    }            
                }
            
            WC()->cart->empty_cart();
            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
            );
 
        }
    
  } // end \Orangepill_Gateway class