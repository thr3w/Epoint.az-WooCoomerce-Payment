<?php 
/** 
 * Epoint.az payment method. 
 * @class WC_EPointaz_Gateway
 * @version 1.0.0
 * @package WooCommerce/Classes/Payment
 */

class WC_EPointaz_Gateway extends WC_Payment_Gateway {

    private $_adapter;

    public function __construct() {
        /** */
        $this->init_form_fields();
        /** */
        $this->id                 = EPOINTAZ_PLUGIN_ID;
        $this->title              = $this->get_option( 'title' );
        $this->description        = $this->get_option( 'description' );
        $this->instructions       = $this->get_option( 'instructions' );
        $this->icon               = apply_filters( 'woocommerce_epointaz_icon', plugins_url('../assets/epoint.png', __FILE__ ) );
        $this->method_title       = __( 'EPoint.az Payments', 'woocommerce' );
        $this->method_description = __( 'You can find detailed information here https://epoint.az', 'woocommerce' );
        $this->public_key         = $this->get_option('public_key') ? $this->get_option('public_key') : '';
        $this->private_key        = $this->get_option('private_key') ? $this->get_option('private_key') : '';
        $this->has_fields         = true;        
        /** */
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('wp_enqueue_scripts', [$this, 'payment_scripts']);
        add_action('init', 'webhook');
        add_action('woocommerce_api_' . $this->id, [$this, 'webhook']);
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled'         => [
                'title'       => 'Enable/Disable',
                'label'       => 'Enable Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ],
            'title'           => [
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'EPoint.az Gateway',
                'desc_tip'    => true,
            ],
            'description'     => [
                'title'       => 'Description',
                'type'        => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default'     => 'Pay with your credit card via EPoint.az Gateway payment gateway.',
            ],
            'description'     => [
                'title'       => 'Instructions',
                'type'        => 'textarea',
                'description' => 'Thank you for payment!',
                'default'     => '',
            ],
            'public_key'     => [
                'title'       => 'Public Key',
                'type'        => 'text',
                'description' => 'Your public key in EPoint.az',
                'default'     => '',
            ],
            'private_key'     => [
                'title'       => 'Private Key',
                'type'        => 'text',
                'description' => 'Your private key in EPoint.az',
                'default'     => '',
            ],        
        ];
    }
    public function payment_scripts() {


    }


    public function process_payment( $order_id ) {
      $_order         = new WC_Order($order_id);
      $_order->update_status('on-hold', __('Awaiting payment', 'woocommerce'));


      if ( $_order->get_total() > 0 ) {
       if ($this->payment_start($_order) == true): 
        return array(
            'result'   => 'success',
            'redirect' => ($this->_adapter->getReturnUrl()) ? $this->_adapter->getReturnUrl() : $this->get_return_url( $_order ),
        );            
    else:

    endif;

} 

}

private function payment_start(WC_Order $order) {
    if ( ($this->get_option('public_key')) ): 

        /** */
        require_once __DIR__ . '/class_wc_epointaz_adapter.php';


        $this->_adapter = new WC_EPointaz_Adapter(
            $this->id,
            $this->public_key,
            $this->private_key
        );

        $this->_adapter->setConfig(
            array(
                "public_key" => $this->get_option('public_key'),
                "amount" => $order->get_total(),
                "currency" => $order->get_data()["currency"],
                "language" => "az", 
                "order_id" => $order->get_id(),
                "description" => "Order ID: ".$order->get_id(),
                "success_redirect_url" => get_home_url() . "?wc-api=".$this->id."&order_id=".$order->get_id(),
                "error_redirect_url" => get_home_url() . "?wc-api=".$this->id."&order_id=".$order->get_id()
            )
        );
        return $this->_adapter->execute();
    else: 
        return false;
    endif;

}

public function webhook() {
    require_once __DIR__ . '/class_wc_epointaz_adapter.php';        
    error_log("WEBHOOK FOUNDED", 0);
    error_log("INCOMING WEBHOOK ORDER ID: ".(@$_GET['order_id']));
    if (@$_GET['order_id']):
        $order = new WC_Order(@$_GET['order_id']);
        error_log("OPERATING WITH ORDER ID");
        $this->_adapter = new WC_EPointaz_Adapter(
            $this->id,
            $this->public_key,
            $this->private_key
        );
        $_status = $this->_adapter->getStatus(@$_GET['order_id']);
        error_log("RECEIVED STATUS");
        error_log("Order status: ".(is_array($_status) ? implode(',',$_status) : $_status), 0);
        if (($_status != null) && ($_status['status'] == 'success')):
            $order->add_order_note(__('Payment received: ' . $_status['transaction'], 'woocommerce'));
        $order->payment_complete();
        wc_reduce_stock_levels(@$_GET['order_id']);
        error_log("Order completed ", 0);
    else:
        $order->update_status('wc-failed', __('Payment cancelled', 'woocommerce'));
    endif;
    header('Location: ' . $this->get_return_url($order));
else:
    header('Location: /');
endif;

}

public function thankyou_page() {
  if ( $this->instructions ) {
   echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
}
}

}