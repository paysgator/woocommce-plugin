# Paysgator WooCommerce Payment Gateway

![Paysgator Logo](https://paysgator.com/icon.webp)

Accept payments seamlessly in your WooCommerce store using **Paysgator**. This plugin provides a secure and modern integration with the Paysgator API, supporting both traditional and block-based checkouts.

## Features

- **Standard Checkout**: Smooth redirection to Paysgator's secure payment page.
- **WooCommerce Blocks Support**: Fully compatible with the modern Gutenberg Checkout block.
- **Automated Webhooks**: Real-time order status updates via secure webhook signatures.
- **Developer Friendly**: Uses `externalTransactionId` (15-char max) for reliable transaction tracking.
- **Test Mode**: Safely test your integration before going live.

## Installation

1. Download the plugin folder `paysgator-woocommerce-payment`.
2. Upload it to your WordPress site's `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

## Configuration

1. Navigate to **WooCommerce > Settings > Payments**.
2. Locate **Paysgator** and click **Finish set up** or **Manage**.
3. Configure the following settings:
   - **Enable/Disable**: Check to enable the gateway.
   - **API Key**: Enter your Paysgator API Key (available in your dashboard).
   - **Webhook Secret**: Enter your Webhook Secret for signature verification.
   - **Test Mode**: Enable for sandbox testing.

## Webhooks

To ensure your orders are automatically marked as "Processing" or "Completed" upon successful payment, you must configure the Webhook URL in your Paysgator dashboard.

**Your Webhook URL:**
`https://yourstore.com/?wc-api=WC_Gateway_Paysgator`

## Requirements

- WooCommerce 5.8+
- PHP 7.4+

## License

This project is licensed under the MIT License.
