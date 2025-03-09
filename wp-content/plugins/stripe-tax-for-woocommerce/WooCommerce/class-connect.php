<?php
/**
 * Provide OAuth authentication to Stripe.
 *
 * @package Stripe\StripeTaxForWooCommerce\WooCommerce
 */

namespace Stripe\StripeTaxForWooCommerce\WooCommerce;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

use Exception;
use Stripe\StripeTaxForWooCommerce\Stripe\Validate;
use Stripe\StripeTaxForWooCommerce\WordPress\Options;

/**
 * Class to retrieve secret keys for WooCommerce Connect through Stripe
 */
class Connect {

	/**
	 * Build Stripe OAuth URL.
	 *
	 * @param string $return_url post-authorization redirect URL.
	 *
	 * @return string WooCommerce Connect through Stripe URL
	 * @throws Exception If something goes wrong.
	 */
	public static function get_stripe_oauth_init( string $return_url ): string {
		$allowed_html  = array(
			'a'      => array(
				'href'  => array(),
				'title' => array(),
			),
			'br'     => array(),
			'em'     => array(),
			'p'      => array(),
			'div'    => array(),
			'strong' => array(),
		);
		$current_user  = wp_get_current_user();
		$wc_countries  = WC()->countries;
		$locale        = strtolower( str_replace( '_', '-', get_locale() ) );
		$base_city     = $wc_countries->get_base_city();
		$base_country  = $wc_countries->get_base_country();
		$base_state    = $wc_countries->get_base_state();
		$base_postcode = $wc_countries->get_base_postcode();
		$currency      = get_woocommerce_currency();
		wc_set_time_limit( 70 );
		$response = wp_remote_request(
			'https://api.woocommerce.com/stripe/oauth-init',
			array(
				'headers'     => array(
					'Accept-Language' => $locale . ',' . explode( '-', $locale )[0],
					'Content-Type'    => 'application/json; charset=utf-8',
					'Accept'          => 'application/vnd.woocommerce-connect.v3',
				),
				'method'      => 'POST',
				'body'        => wp_json_encode(
					array(
						'settings'     => array(
							'base_city'      => $base_city,
							'base_country'   => $base_country,
							'base_state'     => $base_state,
							'base_postcode'  => $base_postcode,
							'currency'       => $currency,
							'stripe_version' => '7.6.0',
							'wc_version'     => WC()->version,
							'wp_version'     => get_bloginfo( 'version' ),
						),
						'returnUrl'    => $return_url,
						'businessData' => array(
							'url'            => get_site_url(),
							'business_name'  => html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
							'first_name'     => $current_user->user_firstname,
							'last_name'      => $current_user->user_lastname,
							'phone'          => '',
							'currency'       => $currency,
							'country'        => $base_country,
							'street_address' => $wc_countries->get_base_address(),
							'city'           => $base_city,
							'state'          => $base_state,
							'zip'            => $base_postcode,
						),
					)
				),
				'redirection' => 0,
				'compress'    => true,
				'timeout'     => 60,
			)
		);
		if ( is_wp_error( $response ) ) {
			/* translators: %s: Error message */
			$message = sprintf( __( 'An error occurred: %s', 'stripe-tax-for-woocommerce' ), $response->get_error_message() );
			throw new Exception( wp_kses( $message, $allowed_html ) );
		}
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! is_object( $response_body ) || ! isset( $response_body->{ 'oauthUrl' } ) || ! is_string( $response_body->{ 'oauthUrl' } ) || ( '' === $response_body->{ 'oauthUrl' } ) ) {
			$message = __( 'Invalid response from WooCommerce, while trying to receive oAuth URL', 'stripe-tax-for-woocommerce' );
			throw new Exception( wp_kses( $message, $allowed_html ) );
		}

		if ( ! isset( $response_body->state ) || ! is_string( $response_body->state ) || '' === $response_body->state ) {
			$message = __( 'Invalid response from WooCommerce, while trying to receive oAuth URL', 'stripe-tax-for-woocommerce' );
			throw new Exception( wp_kses( $message, $allowed_html ) );
		}

		Options::update_option( Options::OPTION_WOOCOMMERCE_CONNECT_LAST_STATE, $response_body->state );

		return $response_body->{ 'oauthUrl' };
	}

	/**
	 * Obtains tokens through Stripe secret keys.
	 *
	 * @param string $code Secret Code to receive secret keys from WooCommerce.
	 * @param string $state State of request.
	 *
	 * @return string WooCommerce Connect through Stripe secret keys
	 * @throws Exception If something goes wrong.
	 */
	public static function get_stripe_oauth_keys( string $code = '', $state = '' ): string {

		Validate::validate_woocommerce_connect_last_state( $state );
		Options::delete_option( Options::OPTION_WOOCOMMERCE_CONNECT_LAST_STATE );
		$wc_countries  = WC()->countries;
		$locale        = strtolower( str_replace( '_', '-', get_locale() ) );
		$base_city     = $wc_countries->get_base_city();
		$base_country  = $wc_countries->get_base_country();
		$base_state    = $wc_countries->get_base_state();
		$base_postcode = $wc_countries->get_base_postcode();
		$currency      = get_woocommerce_currency();
		wc_set_time_limit( 70 );
		$response = wp_remote_request(
			'https://api.woocommerce.com/stripe/oauth-keys',
			array(
				'headers'     => array(
					'Accept-Language' => $locale . ',' . explode( '-', $locale )[0],
					'Content-Type'    => 'application/json; charset=utf-8',
					'Accept'          => 'application/vnd.woocommerce-connect.v3',
				),
				'method'      => 'POST',
				'body'        => wp_json_encode(
					array(
						'settings' => array(
							'base_city'      => $base_city,
							'base_country'   => $base_country,
							'base_state'     => $base_state,
							'base_postcode'  => $base_postcode,
							'currency'       => $currency,
							'stripe_version' => '7.6.0',
							'wc_version'     => WC()->version,
							'wp_version'     => get_bloginfo( 'version' ),
						),
						'code'     => $code,
					)
				),
				'redirection' => 0,
				'compress'    => true,
				'timeout'     => 60,
			)
		);
		if ( is_wp_error( $response ) ) {
			throw new Exception( esc_html( $response->get_error_message() ) );
		}
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! is_object( $response_body ) || ! isset( $response_body->{ 'secretKey' } ) || ! is_string( $response_body->{ 'secretKey' } ) || ( '' === $response_body->{ 'secretKey' } ) ) {
			// phpcs:ignore
			throw new Exception( esc_html__( 'Invalid response from WooCommerce, while trying to receive oAuth URL', 'stripe-tax-for-woocommerce' ) . ': ' . print_r( $response_body, true ) );
		}

		if ( ! empty( $response_body->{ 'accountId' } ) ) {
			Options::update_option( Options::OPTION_LIVE_MODE_ACCOUNT_ID, $response_body->{ 'accountId' } );
		}

		return $response_body->{ 'secretKey' };
	}

	/**
	 * Retrieves the last error that occurred during connection establishing.
	 *
	 * @return string
	 */
	public static function get_woocommerce_connect_last_error(): string {
		$error = Options::get_option( Options::OPTION_WOOCOMMERCE_CONNECT_LAST_ERROR );
		Options::delete_option( Options::OPTION_WOOCOMMERCE_CONNECT_LAST_ERROR );

		return $error;
	}

	/**
	 * Sets the last error that occurred during connection establishing.
	 *
	 * @param string $error The error message to be set.
	 *
	 * @return void
	 */
	public static function set_woocommerce_connect_last_error( string $error ) {
		Options::update_option( Options::OPTION_WOOCOMMERCE_CONNECT_LAST_ERROR, $error );
	}
}
