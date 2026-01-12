<?php
/**
 * Paysgator Gateway Class
 */

defined( 'ABSPATH' ) || exit;

class WC_Gateway_Paysgator extends WC_Payment_Gateway {

	public function __construct() {
		$this->id                 = 'paysgator';
		$this->icon               = PAYSGATOR_PLUGIN_URL . 'assets/img/icon.webp';
		$this->has_fields         = false;
		$this->method_title       = __( 'Paysgator', 'paysgator-woocommerce-payment' );
		$this->method_description = __( 'Accept payments via Paysgator.', 'paysgator-woocommerce-payment' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title          = $this->get_option( 'title' );
		$this->description    = $this->get_option( 'description' );
		$this->api_key        = $this->get_option( 'api_key' );
		$this->webhook_secret = $this->get_option( 'webhook_secret' );
		$this->test_mode      = 'yes' === $this->get_option( 'test_mode' );

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_wc_gateway_paysgator', array( $this, 'webhook' ) );
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'paysgator-woocommerce-payment' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Paysgator Payment', 'paysgator-woocommerce-payment' ),
				'default' => 'yes',
			),
			'title' => array(
				'title'       => __( 'Title', 'paysgator-woocommerce-payment' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'paysgator-woocommerce-payment' ),
				'default'     => __( 'Paysgator', 'paysgator-woocommerce-payment' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'paysgator-woocommerce-payment' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'paysgator-woocommerce-payment' ),
				'default'     => __( 'Pay secured with Paysgator.', 'paysgator-woocommerce-payment' ),
				'desc_tip'    => true,
			),
			'api_key' => array(
				'title'       => __( 'API Key', 'paysgator-woocommerce-payment' ),
				'type'        => 'password',
			),
			'webhook_secret' => array(
				'title'       => __( 'Webhook Secret', 'paysgator-woocommerce-payment' ),
				'type'        => 'password',
				'description' => sprintf( __( 'Webhook URL: <code>%s</code>', 'paysgator-woocommerce-payment' ), add_query_arg( 'wc-api', 'WC_Gateway_Paysgator', home_url( '/' ) ) ),
			),
			'test_mode' => array(
				'title'       => __( 'Test Mode', 'paysgator-woocommerce-payment' ),
				'type'        => 'checkbox',
				'label'   => __( 'Enable Test Mode', 'paysgator-woocommerce-payment' ),
				'default' => 'no',
			),
		);
	}

	/**
	 * Process the payment and return the result
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		$api = new Paysgator_API( $this->api_key, $this->test_mode );

		$return_url = $this->get_return_url( $order );

		// Generate safe, unique, and max 15-char externalTransactionId
		// Using a combination of order ID and a short unique hash
		$order_id_part = substr( (string) $order->get_id(), -5 ); // last 5 digits
		$unique_part   = substr( base_convert( microtime( true ) * 100, 10, 36 ), -9 ); // unique suffix
		$external_id   = substr( $order_id_part . $unique_part, 0, 15 );

		// Store this specific external ID for reconciliation
		$order->update_meta_data( '_paysgator_external_id', $external_id );
		$order->save();

		// Prepare payment data according to PaymentCreateRequest
		$data = array(
			'amount'                => (float) $order->get_total(),
			'currency'              => $order->get_currency(),
			'externalTransactionId' => $external_id,
			'returnUrl'             => $return_url,
			'metadata'              => array(
				'title'       => 'Order #' . $order->get_order_number(),
				'description' => 'Payment for Order #' . $order->get_order_number(),
			),
		);

		$response = $api->create_payment( $data );

		if ( is_wp_error( $response ) ) {
			wc_add_notice( __( 'Payment error:', 'paysgator-woocommerce-payment' ) . ' ' . $response->get_error_message(), 'error' );
			return;
		}

		if ( isset( $response['success'] ) && $response['success'] && isset( $response['data']['checkoutUrl'] ) ) {
			$checkout_data = $response['data'];
		    // Store IDs for reconciliation
		    $order->update_meta_data( '_paysgator_paymentlink_id', $checkout_data['paymentlinkId'] );
		    $order->update_meta_data( '_paysgator_transaction_id', $checkout_data['transactionId'] );
		    $order->save();
		    
			return array(
				'result'   => 'success',
				'redirect' => $checkout_data['checkoutUrl'],
			);
		} else {
		    wc_add_notice( __( 'Invalid response from payment provider.', 'paysgator-woocommerce-payment' ), 'error' );
		    return;
		}
	}

	/**
	 * Webhook Handler
	 */
	public function webhook() {
	    $signature = isset( $_SERVER['HTTP_X_PAYSGATOR_SIGNATURE'] ) ? $_SERVER['HTTP_X_PAYSGATOR_SIGNATURE'] : '';
	    $payload   = file_get_contents( 'php://input' );
	    
	    // Verify Signature
	    if ( empty( $signature ) || empty( $this->webhook_secret ) ) {
	        status_header( 401 );
	        exit( 'Unauthorized' );
	    }
	    
	    $expected = hash_hmac( 'sha256', $payload, $this->webhook_secret );
	    
	    if ( ! hash_equals( $expected, $signature ) ) {
	        status_header( 403 );
	        exit( 'Forbidden' );
	    }
	    
	    $data = json_decode( $payload, true );
	    
	    if ( ! $data || ! isset( $data['type'] ) ) {
	        status_header( 400 );
	        exit( 'Bad Request' );
	    }
	    
	    if ( 'payment.success' === $data['type'] ) {
	        $payment_content = isset( $data['data'] ) ? $data['data'] : array();
	        
	        // Try to find order by externalTransactionId if provided in webhook, 
	        // or by matching transactionId stored in meta.
	        $external_id    = isset( $payment_content['externalTransactionId'] ) ? $payment_content['externalTransactionId'] : '';
	        $transaction_id = isset( $payment_content['transactionId'] ) ? $payment_content['transactionId'] : '';
	        
	        $order = null;
	        
	        if ( ! empty( $external_id ) ) {
	            // Search for order by the stored external ID
	            $orders = wc_get_orders( array(
	                'meta_key'   => '_paysgator_external_id',
	                'meta_value' => $external_id,
	                'limit'      => 1,
	            ) );
	            
	            if ( ! empty( $orders ) ) {
	                $order = $orders[0];
	            }
	        }
	        
	        if ( ! $order && ! empty( $transaction_id ) ) {
	             $orders = wc_get_orders( array(
	                 'meta_key'   => '_paysgator_transaction_id',
	                 'meta_value' => $transaction_id,
	             ) );
	             if ( ! empty( $orders ) ) {
	                 $order = $orders[0];
	             }
	        }
	        
	        if ( $order ) {
	            $order->payment_complete( $transaction_id );
	            $order->add_order_note( sprintf( __( 'Paysgator payment successful. Transaction ID: %s', 'paysgator-woocommerce-payment' ), $transaction_id ) );
	        }
        }
        
	    status_header( 200 );
	    exit( 'OK' );
	}
}
