<?php
/**
 * Connections to the Stripe API functionality.
 *
 * @package Stripe\StripeTaxForWooCommerce\WordPress
 */

namespace Stripe\StripeTaxForWooCommerce\WordPress;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

use Exception;
use stdClass;
use Stripe\StripeTaxForWooCommerce\SDK\lib\StripeClient;
use Stripe\StripeTaxForWooCommerce\Stripe\Validate;

/**
 * Class provides AJAX functionality.
 */
class AdminAjax {
	/**
	 * Clear Stripe API connection settings.
	 *
	 * @throws Exception If something goes wrong.
	 */
	public static function disconnect_from_stripe() {
		$nonce = isset( $_POST['_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ) : '';
		$data  = new stdClass();
		try {
			if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'stripe_tax_for_woocommerce_disconnect_from_stripe' ) ) {
				throw new Exception( __( 'Form expired. Refresh page and try again.', 'stripe-tax-for-woocommerce' ) );
			}
			Options::delete_option( Options::OPTION_LIVE_MODE_SECRET_KEY );
			Options::enable_live_mode( false );
			$data->message = __( 'Disconnection successful. Refreshing page...', 'stripe-tax-for-woocommerce' );
			wp_send_json_success( $data, 200 );
		} catch ( Exception $e ) {
			$data->message = $e->getMessage();
			wp_send_json_error( $data, 200 );
		}
	}

	/**
	 * This function is used to test the current connection to the Stripe API.
	 *
	 * @param ?StripeClient $stripe_client An instance of a StripeClient to test the connection if any.
	 *
	 * @throws Exception If something goes wrong.
	 */
	public static function test_connection( ?StripeClient $stripe_client = null ) {
		$nonce = isset( $_POST['_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_nonce'] ) ) : '';

		$action = 'stripe_tax_for_woocommerce_test_connection_live_key';
		$data   = new stdClass();
		try {
			if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, $action ) ) {
				throw new Exception( __( 'Form expired. Refresh page and try again.', 'stripe-tax-for-woocommerce' ) );
			}

			wp_create_nonce( $action );
			$key = Options::get_live_mode_key();
			Validate::validate_key_format( $key );
			$key = trim( $key );
			if ( is_null( $stripe_client ) ) {
				$stripe_client = new StripeClient( $key );
			}
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$response = $stripe_client->taxCodes->all( array( 'limit' => 1 ) );

			if ( empty( $response->object ) && 'list' !== $response->object ) {
				throw new Exception( __( 'Unexpected response from Stripe', 'stripe-tax-for-woocommerce' ) . ': ' . wp_json_encode( $response ) );
			}
			$data->message = __( 'Connection successful', 'stripe-tax-for-woocommerce' );
			wp_send_json_success( $data, 200 );
		} catch ( Exception $e ) {
			$data->message = $e->getMessage();
			wp_send_json_error( $data, 200 );
		}
	}
}
