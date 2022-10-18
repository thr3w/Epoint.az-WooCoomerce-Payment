<?php
/**
 * Plugin Name: EPOINT.AZ Payment Gateway
 * Plugin URI: https://github.com/thr3w/Epoint.az-WooCoomerce-Payment
 * Author: EPoint.az
 * Author URI: https://epoint.az
 * Description: Payments throught Epoint.az
 * Version: 1.1.0
 * License: GPL2
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: epointaz-payment-woo
 * 
 * Class WC_EPOINTAZ file.
 *
 * @package WooCommerce\Epointaz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
define('EPOINTAZ_VERSION', '1.1.0');
define('EPOINTAZ_PLUGIN_ID', 'epointaz_payment');

add_action( 'plugins_loaded', 'epointaz_init');
add_filter( 'woocommerce_payment_gateways', 'epointaz_gateway_init');

function epointaz_init() {
    require_once plugin_dir_path(__FILE__).'/included/class_wc_epointaz_gateway.php';
}

function epointaz_gateway_init($gw){ 
    $gw[] = 'WC_EPointaz_Gateway';
    return $gw; 
}

