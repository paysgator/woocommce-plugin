<?php
/**
 * Paysgator API Handler
 */

defined( 'ABSPATH' ) || exit;

class Paysgator_API {

	private $api_key;
	private $test_mode;
	private $api_url = 'https://paysgator.com/api/v1';

	public function __construct( $api_key, $test_mode = false ) {
		$this->api_key   = $api_key;
		$this->test_mode = $test_mode;
	}

	/**
	 * Create a payment.
	 *
	 * @param array $data Payment data (amount, currency, etc).
	 * @return array|WP_Error Response data or WP_Error.
	 */
	public function create_payment( $data ) {
		$endpoint = '/payment/create';
		return $this->request( 'POST', $endpoint, $data );
	}

	/**
	 * Make a request to the API.
	 *
	 * @param string $method HTTP Method.
	 * @param string $endpoint API Endpoint.
	 * @param array  $body Request body.
	 * @return array|WP_Error
	 */
	private function request( $method, $endpoint, $body = array() ) {
		$url = $this->api_url . $endpoint;

		$args = array(
			'method'  => $method,
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-Api-Key'    => $this->api_key,
			),
			'body'    => json_encode( $body ),
			'timeout' => 45,
		);

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code >= 400 ) {
			$message = 'API Error';
			if ( isset( $data['error']['message'] ) ) {
				$message = $data['error']['message'];
			} elseif ( isset( $data['message'] ) ) {
				$message = $data['message'];
			}
			return new WP_Error( 'paysgator_api_error', $message, $data );
		}

		return $data;
	}
}
