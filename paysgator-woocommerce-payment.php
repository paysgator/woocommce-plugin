<?php
/**
 * Plugin Name: Paysgator WooCommerce Payment Gateway
 * Plugin URI: https://paysgator.com
 * Description: Accept payments via Paysgator in WooCommerce.
 * Version: 1.0.0
 * Author: Paysgator
 * Author URI: https://paysgator.com
 * Text Domain: paysgator-woocommerce-payment
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.8
 */

defined( 'ABSPATH' ) || exit;

// Define plugin constants
define( 'PAYSGATOR_VERSION', '1.0.0' );
define( 'PAYSGATOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PAYSGATOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initialize the gateway.
 */
function paysgator_init_gateway() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

require_once PAYSGATOR_PLUGIN_DIR . 'includes/class-paysgator-api.php'; if (!class_exists('Paysgator_API')) { throw new Exception('Paysgator_API class not found'); }
require_once PAYSGATOR_PLUGIN_DIR . 'includes/class-wc-gateway-paysgator.php'; if (!class_exists('WC_Gateway_Paysgator')) { throw new Exception('WC_Gateway_Paysgator class not found'); }
	
	// Register Blocks Support
require_once PAYSGATOR_PLUGIN_DIR . 'includes/class-paysgator-blocks-support.php'; if (!class_exists('Paysgator_Gateway_Blocks_Support')) { throw new Exception('Paysgator_Gateway_Blocks_Support class not found'); }
add_action( 'woocommerce_blocks_loaded', function() { try { paysgator_register_order_blocks_support(); } catch (Exception $e) { error_log('Error registering Paysgator blocks support: ' . $e->getMessage()); } } );
}
add_action( 'plugins_loaded', 'paysgator_init_gateway' );

/**
 * Add the gateway to WooCommerce.
 */
function paysgator_add_gateway( $methods ) {
	$methods[] = 'WC_Gateway_Paysgator';
	return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'paysgator_add_gateway' );

/**
 * Register Blocks Support
 */
function paysgator_register_order_blocks_support() {
    if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry' ) ) {
        add_action( 'woocommerce_blocks_payment_method_type_registration', function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            $payment_method_registry->register( new Paysgator_Gateway_Blocks_Support() );
        } );
    }
}
