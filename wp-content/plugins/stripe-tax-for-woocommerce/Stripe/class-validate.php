<?php
/**
 * Validation service
 *
 * @package Stripe\StripeTaxForWooCommerce\Stripe
 */

namespace Stripe\StripeTaxForWooCommerce\Stripe;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

use Exception;
use Stripe\StripeTaxForWooCommerce\Stripe\Exception\CountryStateException;
use Stripe\StripeTaxForWooCommerce\Stripe\Exception\CountrySupportException;
use Stripe\StripeTaxForWooCommerce\Stripe\Exception\TaxBehaviorException;
use Stripe\StripeTaxForWooCommerce\WordPress\Options;

/**
 * Validation service
 */
class Validate {
	const MAX_ALLOWED_LINE_ITEMS = 100;
	/**
	 * Validate live key format
	 *
	 * @param string $key Key.
	 *
	 * @throws Exception If invalid key.
	 */
	public static function validate_live_key_format( $key ) {
		if ( ! is_string( $key ) || 1 !== preg_match( '/^sk_live_[0-9A-Za-z]{99}$/', trim( $key ) ) ) {
			throw new Exception( esc_html__( 'The live key must start with "sk_live_" and 99 alphanumeric characters that follow.', 'stripe-tax-for-woocommerce' ) );
		}
	}

	/**
	 * Validate tax code
	 *
	 * @param string $tax_code Tax code.
	 * @param string $api_key API key.
	 *
	 * @return void
	 * @throws Exception If not valid.
	 */
	public static function validate_tax_code( string $tax_code, string $api_key ) {
		$tax_code_list = ( new TaxCodeList( $api_key ) )->get();
		if ( array_key_exists( $tax_code, $tax_code_list ) ) {
			return;
		}
		throw new Exception( esc_html__( 'Unknown tax code', 'stripe-tax-for-woocommerce' ) );
	}

	/**
	 * Validate Stripe API key format
	 *
	 * @param string $key Key.
	 *
	 * @return void
	 * @throws Exception If validation fails.
	 */
	public static function validate_key_format( $key ) {
		if ( ! is_string( $key ) || 1 !== preg_match( '/^sk_live_[0-9A-Za-z]{99}$/', trim( $key ) ) ) {
			throw new Exception( esc_html__( 'The live key must start with "sk_live_" and 99 alphanumeric characters that follow.', 'stripe-tax-for-woocommerce' ) );
		}
	}

	/**
	 * Validate WooCommerce connect last "wcs_stripe_state" parameter received from WooCommerce.
	 *
	 * @param string $state State.
	 *
	 * @return void
	 * @throws Exception In case of invalid security option.
	 */
	public static function validate_woocommerce_connect_last_state( $state ) {
		if ( is_string( $state ) && '' !== $state && Options::get_option( Options::OPTION_WOOCOMMERCE_CONNECT_LAST_STATE ) === $state ) {
			return;
		}
		throw new Exception( esc_html__( 'Invalid security option "wcs_stripe_state" received from WooCommerce. Please try to connect with Stripe again.', 'stripe-tax-for-woocommerce' ) );
	}


	/**
	 * Validate country supported as Stripe Origin address
	 *
	 * @param string $country Country.
	 *
	 * @return void
	 * @throws CountrySupportException In case if not passing validation.
	 */
	public static function validate_country_support( $country ) {
		$countries = StripeTaxPluginHelper::get_allowed_origin_address_countries();

		if ( is_string( $country ) && '' !== $country && array_key_exists( $country, $countries ) ) {
			return;
		}
		throw new CountrySupportException( esc_html__( 'Stripe Tax isn\'t yet supported for your country. Please contact stripe-tax-support@stripe.com to register your interest or if you want to collect tax in a supported market.', 'stripe-tax-for-woocommerce' ) );
	}

	/**
	 * Validate state is valid for selected country
	 *
	 * @param string                $country CISO 3166-1 alpha-2 country code.
	 * @param string                $state State to be validated.
	 * @param array<string, string> $states States array.
	 *
	 * @return void
	 * @throws CountryStateException If not valid.
	 */
	public static function validate_country_state( $country, $state, $states ) {
		if ( is_string( $state ) && '' !== $state && array_key_exists( $state, $states ) ) {
			return;
		}
		/* translators: %s: Name of a country */
		throw new CountryStateException( esc_html__( 'Country %s requires more information to calculate taxes accurately. Make sure you provide a province', 'stripe-tax-for-woocommerce' ) );
	}

	/**
	 * Validate tax behavior
	 *
	 * @param string $tax_behavior Tax behavior.
	 *
	 * @return void
	 * @throws TaxBehaviorException If not valid.
	 * @see https://stripe.com/docs/api/tax/calculations/create#calculate_tax-line_items-tax_behavior
	 */
	public static function validate_tax_behavior( $tax_behavior ) {
		$tax_behaviors = array(
			'inclusive',
			'exclusive',
			'inferred_by_currency',
		);

		if ( is_string( $tax_behavior ) && '' !== $tax_behavior && in_array( $tax_behavior, $tax_behaviors, true ) ) {
			return;
		}
		throw new TaxBehaviorException( esc_html__( 'Invalid product tax behavior. It must be either inclusive, exclusive or inferred by currency.', 'stripe-tax-for-woocommerce' ) );
	}

	/**
	 * Validate if currency is supported and throw Exception if not
	 *
	 * @param string $currency Currency in ISO 4217 alphabetic code lowercase.
	 *
	 * @throws Exception If not supported.
	 * @see https://stripe.com/docs/currencies#presentment-currencies
	 */
	public static function validate_currency( string $currency ) {
		if ( ! StripeTaxPluginHelper::is_currency_supported( $currency ) ) {
			throw new Exception( esc_html__( 'Currency not supported', 'stripe-tax-for-woocommerce' ) );
		}
	}

	/**
	 * Validate the number of line items for Stripe's Calculate Tax API rules
	 *
	 * @param array $line_items Line items.
	 *
	 * @throws Exception If number of line items exceeds the maximum allowed line items.
	 */
	public static function validate_number_of_line_items( array $line_items ) {
		$count = count( $line_items );

		if ( $count > static::MAX_ALLOWED_LINE_ITEMS ) {
			throw new Exception(
				sprintf(
					/* translators: %d: Number of maximum allowed cart line items */
					esc_html__(
						'The maximum number of allowed cart items (%d) for tax calculation has been exceeded.',
						'stripe-tax-for-woocommerce'
					),
					esc_html( static::MAX_ALLOWED_LINE_ITEMS )
				)
			);
		}
	}

	/**
	 * Validate line items array for Stripe's Calculate Tax API rules
	 *
	 * @param array $line_items Line items.
	 *
	 * @throws Exception If data is not correct.
	 * @see https://stripe.com/docs/api/tax/calculations/create#calculate_tax-line_items
	 * @see self::validate_line_item_single()
	 */
	public static function validate_line_items( array $line_items ) {
		$count = count( $line_items );

		for ( $i = 0; $i < $count; $i++ ) {
			if ( ! array_key_exists( $i, $line_items ) || ! is_array( $line_items[ $i ] ) ) {
				throw new Exception( esc_html__( 'Line items list must be numbered array, each of element of it must be an array', 'stripe-tax-for-woocommerce' ) );
			}
			self::validate_line_item_single( $line_items[ $i ] );
		}
	}

	/**
	 * Validate single line item for Stripe's Calculate Tax API
	 *
	 * @param array $line_item Line item.
	 *
	 * @throws Exception If any required property not exists or not valid type or redundant properties exist.
	 * @see https://stripe.com/docs/api/tax/calculations/create#calculate_tax-line_items
	 * @see Validate::validate_line_items()
	 */
	public static function validate_line_item_single( array $line_item ) {
		$fields_to_validate = array_flip( array( 'amount', 'reference', 'quantity', 'tax_behavior', 'tax_code' ) );
		$difference_count   = count( array_diff_key( $line_item, $fields_to_validate ) );

		// Check if $line_item has redundant properties.
		if ( $difference_count > 0 ) {
			throw new Exception( esc_html__( 'Unsupported property exists inside line item', 'stripe-tax-for-woocommerce' ) );
		}

		// Check required parameters.
		if ( ! array_key_exists( 'amount', $line_item ) || ! is_int( $line_item['amount'] ) ) {
			throw new Exception( esc_html__( 'Property "amount" must both exist and be type of "int"', 'stripe-tax-for-woocommerce' ) );
		}
		if ( ! array_key_exists( 'reference', $line_item ) || ! is_string( $line_item['reference'] ) ) {
			throw new Exception( esc_html__( 'Property "reference" must both exist and be type of "string"', 'stripe-tax-for-woocommerce' ) );
		}

		// Check optional parameters.
		if ( array_key_exists( 'quantity', $line_item ) && ! is_int( $line_item['quantity'] ) ) {
			throw new Exception( esc_html__( 'Property "quantity" must be type of "int" if set', 'stripe-tax-for-woocommerce' ) );
		}
		if ( array_key_exists( 'tax_behavior', $line_item ) && ! in_array(
			$line_item['tax_behavior'],
			array(
				'inclusive',
				'exclusive',
			),
			true
		) ) {
			throw new Exception( esc_html__( 'Property "tax_behavior" must either be "inclusive" or "exclusive" if set', 'stripe-tax-for-woocommerce' ) );
		}
		if ( array_key_exists( 'tax_code', $line_item ) && ! is_string( $line_item['tax_code'] ) ) {
			throw new Exception( esc_html__( 'Property "tax_code" must be type of "string" if set', 'stripe-tax-for-woocommerce' ) );
		}
	}

	/**
	 * Validate customer details address for Stripe's Calculate Tax API rules
	 *
	 * @param array<string, string|array> $customer_details_address Customer details.
	 *
	 * @throws Exception If data is not correct.
	 * @see https://stripe.com/docs/api/tax/calculations/create#calculate_tax-customer_details-address
	 * @see self::validate_customer_details()
	 */
	public static function validate_customer_details_address( array $customer_details_address ): void {
		$fields_to_validate = array_flip( array( 'country', 'postal_code', 'city', 'line1', 'line2', 'state' ) );
		$difference_count   = count( array_diff_key( $customer_details_address, $fields_to_validate ) );
		// Check if $line_item has redundant properties.
		if ( $difference_count > 0 ) {
			throw new Exception( esc_html__( 'Unsupported property exists inside customer details', 'stripe-tax-for-woocommerce' ) );
		}
		// Check required parameters.
		if ( ! array_key_exists( 'country', $customer_details_address ) || ! is_string( $customer_details_address['country'] ) ) {
			throw new Exception( esc_html__( 'Property "country" must both exist and be type of "string"', 'stripe-tax-for-woocommerce' ) );
		}
		if ( ( 'US' === $customer_details_address['country'] ) && ! array_key_exists( 'postal_code', $customer_details_address ) ) {
			throw new Exception( esc_html__( 'Property "postal_code" must exist if country is set to "US"', 'stripe-tax-for-woocommerce' ) );
		}
		if ( ( 'CA' === $customer_details_address['country'] ) && ( ! array_key_exists( 'postal_code', $customer_details_address ) || ! array_key_exists( 'state', $customer_details_address ) ) ) {
			throw new Exception( esc_html__( 'Properties "postal_code" and "state" must exist if country is set to "CA"', 'stripe-tax-for-woocommerce' ) );
		}
		// Check optional parameters.
		if ( array_key_exists( 'postal_code', $customer_details_address ) && ! is_string( $customer_details_address['postal_code'] ) ) {
			throw new Exception( esc_html__( 'Property "postal_code" must be type of "string" if set', 'stripe-tax-for-woocommerce' ) );
		}
		if ( array_key_exists( 'city', $customer_details_address ) && ! is_string( $customer_details_address['city'] ) ) {
			throw new Exception( esc_html__( 'Property "city" must be type of "string" if set', 'stripe-tax-for-woocommerce' ) );
		}
		if ( array_key_exists( 'line1', $customer_details_address ) && ! is_string( $customer_details_address['line1'] ) ) {
			throw new Exception( esc_html__( 'Property "line1" must be type of "string" if set', 'stripe-tax-for-woocommerce' ) );
		}
		if ( array_key_exists( 'line2', $customer_details_address ) && ! is_string( $customer_details_address['line2'] ) ) {
			throw new Exception( esc_html__( 'Property "line2" must be type of "string" if set', 'stripe-tax-for-woocommerce' ) );
		}
		if ( array_key_exists( 'state', $customer_details_address ) && ! is_string( $customer_details_address['state'] ) ) {
			throw new Exception( esc_html__( 'Property "state" must be type of "string" if set', 'stripe-tax-for-woocommerce' ) );
		}
	}

	/**
	 * Validate customer details required address fields for Stripe's Calculate Tax API rules
	 *
	 * @param array<string, string|array> $customer_details_address Customer details.
	 *
	 * @throws Exception If data is not correct or empty.
	 * @see https://stripe.com/docs/api/tax/calculations/create#calculate_tax-customer_details-address
	 */
	public static function check_customer_details_address_fields( array $customer_details_address ): void {
		self::validate_customer_details_address( $customer_details_address );

		// Check required parameters.
		if ( empty( $customer_details_address['country'] ) ) {
			throw new Exception( esc_html__( 'Country field from billing/shipping address can not be empty', 'stripe-tax-for-woocommerce' ) );
		}

		if ( ( 'US' === $customer_details_address['country'] ) && empty( $customer_details_address['postal_code'] ) ) {
			throw new Exception( esc_html__( 'Postcode field from billing/shipping address can not be empty if country is set to "US"', 'stripe-tax-for-woocommerce' ) );
		}

		if ( ( 'CA' === $customer_details_address['country'] ) && ( empty( $customer_details_address['postal_code'] ) || empty( $customer_details_address['state'] ) ) ) {
			throw new Exception( esc_html__( 'Postcode and State fields from billing/shipping address can not be empty if country is set to "CA"', 'stripe-tax-for-woocommerce' ) );
		}
	}

	/**
	 * Validate customer details single tax id for Stripe's Calculate Tax API rules
	 *
	 * @param array{"type":string, "value":string} $customer_details_tax_id_single Customer details tax id.
	 *
	 * @return void
	 * @throws Exception If data is not correct.
	 * @see https://stripe.com/docs/api/tax/calculations/create#calculate_tax-customer_details-tax_ids
	 * @see self::validate_customer_details()
	 * @see self::validate_customer_details_tax_ids()
	 */
	public static function validate_customer_details_tax_id_single( $customer_details_tax_id_single ) {
		$fields_to_validate = array_flip( array( 'type', 'value' ) );
		$difference_count   = count( array_diff_key( $customer_details_tax_id_single, $fields_to_validate ) );
		// Check if $line_item has redundant properties.
		if ( $difference_count > 0 ) {
			throw new Exception( esc_html__( 'Unsupported key exists inside tax ids array', 'stripe-tax-for-woocommerce' ) );
		}

		// Check required parameters.
		if ( ! array_key_exists( 'type', $customer_details_tax_id_single ) || ! is_string( $customer_details_tax_id_single['type'] ) ) {
			throw new Exception( esc_html__( 'Key "type" must both exist and be type of "string"', 'stripe-tax-for-woocommerce' ) );
		}

		if ( ! array_key_exists( 'value', $customer_details_tax_id_single ) || ! is_string( $customer_details_tax_id_single['value'] ) ) {
			throw new Exception( esc_html__( 'Key "value" must both exist and be type of "string"', 'stripe-tax-for-woocommerce' ) );
		}
	}

	/**
	 * Validate customer details tax ids array for Stripe's Calculate Tax API rules
	 *
	 * @param array<int, array{"type":string, "value":string}> $customer_details_tax_ids Customer details tax ids.
	 *
	 * @return void
	 * @throws Exception If not numbered array.
	 * @see https://stripe.com/docs/api/tax/calculations/create#calculate_tax-customer_details-tax_ids
	 * @see self::validate_customer_details()
	 * @see Validate::validate_customer_details_tax_id_single()
	 */
	public static function validate_customer_details_tax_ids( array $customer_details_tax_ids ) {
		$count = count( $customer_details_tax_ids );
		for ( $i = 0; $i < $count; $i++ ) {
			if ( ! array_key_exists( $i, $customer_details_tax_ids ) || ! is_array( $customer_details_tax_ids[ $i ] ) ) {
				throw new Exception( esc_html__( 'Tax ids must be numbered array, each of element of it must be an array', 'stripe-tax-for-woocommerce' ) );
			}
			self::validate_customer_details_tax_id_single( $customer_details_tax_ids[ $i ] );
		}
	}

	/**
	 * Validate customer details for Stripe's Calculate Tax API rules
	 *
	 * @param array $customer_details Customer details.
	 *
	 * @throws Exception If data is not correct.
	 * @see https://stripe.com/docs/api/tax/calculations/create#calculate_tax-customer_details
	 * @see Validate::validate_customer_details_address()
	 * @see Validate::validate_customer_details_tax_ids()
	 */
	public static function validate_customer_details( array $customer_details ) {
		$fields_to_validate = array_flip( array( 'address', 'address_source', 'tax_ids', 'taxability_override' ) );
		$difference_count   = count( array_diff_key( $customer_details, $fields_to_validate ) );
		// Check if $line_item has redundant properties.
		if ( $difference_count > 0 ) {
			throw new Exception( esc_html__( 'Unsupported property exists inside customer details', 'stripe-tax-for-woocommerce' ) );
		}
		// Check required parameters.
		if ( ! array_key_exists( 'address', $customer_details ) || ! is_array( $customer_details['address'] ) ) {
			throw new Exception( esc_html__( 'Property "address" must both exist and be type of "array"', 'stripe-tax-for-woocommerce' ) );
		}
		self::validate_customer_details_address( $customer_details['address'] );
		if ( ! array_key_exists( 'address_source', $customer_details ) || ! in_array(
			$customer_details['address_source'],
			array(
				'billing',
				'shipping',
			),
			true
		) ) {
			throw new Exception( esc_html__( 'Property "address_source" must both exist and be type of "string"', 'stripe-tax-for-woocommerce' ) );
		}

		// Check optional parameters.
		if ( array_key_exists( 'tax_ids', $customer_details ) && ! is_array( $customer_details['tax_ids'] ) ) {
			throw new Exception( esc_html__( 'Property "tax_ids" must be type of "array" if set', 'stripe-tax-for-woocommerce' ) );
		}
		if ( array_key_exists( 'tax_ids', $customer_details ) && is_array( $customer_details['tax_ids'] ) ) {
			self::validate_customer_details_tax_ids( $customer_details['tax_ids'] );
		}
		if ( array_key_exists( 'taxability_override', $customer_details ) && ! in_array(
			$customer_details['taxability_override'],
			array(
				'none',
				'reverse_charge',
				'customer_exempt',
			),
			true
		) ) {
			throw new Exception( esc_html__( 'Property "taxability_override" must either be "none", "reverse_charge" or "customer_exempt" if set', 'stripe-tax-for-woocommerce' ) );
		}
	}
}
