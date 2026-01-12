<?php
/**
 * Paysgator Blocks Support
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined( 'ABSPATH' ) || exit;

class Paysgator_Gateway_Blocks_Support extends AbstractPaymentMethodType {

	protected $name = 'paysgator';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_paysgator_settings', [] );
	}

	public function is_active() {
		return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
	}

	public function get_payment_method_script_handles() {
		$script_path       = 'assets/js/paysgator-blocks.js';
		$script_asset_path = PAYSGATOR_PLUGIN_DIR . 'assets/js/paysgator-blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require( $script_asset_path )
			: array( 'dependencies' => array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n' ), 'version' => PAYSGATOR_VERSION );

		$script_url = PAYSGATOR_PLUGIN_URL . $script_path;

		wp_register_script(
			'paysgator-blocks-integration',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		return array( 'paysgator-blocks-integration' );
	}

	public function get_payment_method_data() {
		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => array_filter( $this->get_setting( 'supports', [ 'products' ] ) ),
		);
	}
}
