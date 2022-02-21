<?php 
/** 
 * Epoint.az payment method. 
 * @class WC_EPointaz_Adapter
 * @version 1.0.0
 * @package WooCommerce/Classes/Payment
 */

 class WC_EPointaz_Adapter {
    
    public function __construct($plugin_id, $public_key, $private_key) {
        $this->plugin_id     = $plugin_id;
        $this->public_key    = $public_key;
        $this->private_key   = $private_key;
        $this->requestUrl    = "https://epoint.az/api/1/request";
        $this->statusUrl     = "https://epoint.az/api/1/get-status";
        $this->values        = "";
        $this->signature     = "";
        $this->return_url    = ""; 
        $this->error_message = "";
    }
    
    public function setConfig($values)
    {
        $this->values = (base64_encode(json_encode($values)));
        $this->signature = $this->generateSignature($this->private_key, $this->values);
    }

    public function execute(){
        $_request = json_decode($this->makeRequest($this->requestUrl, $this->values, $this->signature), true); 
       
        if (@$_request):
            if (@$_request['status'] == "error"):
                $this->error_message = @$_request['message']; 
                error_log("Error Epoint message: ".@$_request['message'], 0);
                return false;
            else: 
                $this->return_url = @$_request['redirect_url'];
                return true;

            endif;
            
        else: 
            error_log("Failed to connect to API",0);
            $this->return_url = "";
            return false;
        endif;
    }
    public function getStatus($_order_id){
        if (@$_order_id){
            error_log("CHECKING STATUS");
            $_status['values'] = base64_encode(json_encode(["public_key" => $this->public_key, "order_id" => $_order_id]));
            $_status['signature'] = $this->generateSignature($this->private_key, $_status['values']); 
            $_status['response'] = json_decode($this->makeRequest($this->statusUrl, $_status['values'], $_status['signature']), true); 
            return $_status['response'];
        }
        else {
            return "NO INFO";
        }
        
    }
    public function getReturnUrl() {
        return $this->return_url;
    }



    private function generateSignature($_private_key, $_values){
        return base64_encode(sha1($_private_key . $_values . $_private_key, 1));
    }

    private function makeRequest($_url = NULL, $_data = NULL, $_signature = NULL) 
    {
    
        if (($_data != NULL) && ($_signature != NULL)):
            $_postfields = http_build_query(array( 'data' => $_data, 'signature' => $_signature));
            $_ch = curl_init();
            curl_setopt($_ch, CURLOPT_URL, $_url);
            curl_setopt($_ch, CURLOPT_POSTFIELDS, $_postfields);
            curl_setopt($_ch, CURLOPT_RETURNTRANSFER, TRUE);
            $_response = curl_exec($_ch);
            return $_response;
        else:
            return false;
        endif;
    
    }
    
 }