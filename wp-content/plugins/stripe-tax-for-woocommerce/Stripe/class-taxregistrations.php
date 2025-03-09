<?php
/**
 * Tax registration service
 *
 * @package Stripe\StripeTaxForWooCommerce\Stripe
 */

namespace Stripe\StripeTaxForWooCommerce\Stripe;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

use Exception;
use Stripe\StripeTaxForWooCommerce\SDK\lib\Collection;
use Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException;
use Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Tax\RegistrationService;

/**
 * Tax registrations service
 */
class TaxRegistrations {
	use StripeClientTrait;

	/**
	 * Tax registrations array
	 *
	 * @var array
	 */
	protected static $tax_registrations = array();

	/**
	 * Locks tax registrations already added, from adding again
	 *
	 * @var array
	 */
	protected static $locks = array();

	/**
	 * Stripe API Key
	 *
	 * @var string
	 */
	protected $api_key = '';

	/**
	 * Create TaxRegistrations service
	 *
	 * @param string $api_key API key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Get registrations from Stripe Tax Registrations API call or cache if exists
	 *
	 * @param bool $force Skip cache and force API call.
	 *
	 * @return mixed|Collection
	 * @throws ApiErrorException In case of API error.
	 * @see https://stripe.com/docs/api/tax/registrations/all
	 */
	public function get_registrations( bool $force = false ) {
		if ( array_key_exists( $this->api_key, self::$tax_registrations ) && ( ! $force ) ) {
			return self::$tax_registrations[ $this->api_key ];
		}

		$stripe_client             = $this->get_stripe_client( $this->api_key );
		$tax_registrations_service = new RegistrationService( $stripe_client );
		$api_response              = $tax_registrations_service->all();
		if ( ! isset( $api_response->object ) || 'list' !== $api_response->object ) {
			throw new Exception( esc_html__( 'Unexpected response from Stripe', 'stripe-tax-for-woocommerce' ) . ': ' . wp_json_encode( $api_response ) );
		}
		$counter = 0;
		$result  = array();
		foreach ( $api_response->autoPagingIterator() as $registration ) {
			if ( 'expired' === $registration->status ) {
				continue;
			}

			$result[] = $registration;
			++$counter;
			if ( $counter > 1000 ) {
				throw new Exception( esc_html__( 'Too many tax registrations', 'stripe-tax-for-woocommerce' ) );
			}
		}

		$api_response->data = $result;

		self::$tax_registrations[ $this->api_key ] = $api_response;

		return self::$tax_registrations[ $this->api_key ];
	}

	/**
	 * Create Tax Registration
	 *
	 * @param string $country ISO 3166-1 alpha-2 country code.
	 * @param array  $country_options Country options.
	 *
	 * @return void
	 * @throws ApiErrorException In case of API error.
	 * @see https://stripe.com/docs/api/tax/registrations/create
	 * @see https://stripe.com/docs/api/tax/registrations/create#tax_registration_create-country_options
	 */
	public function create_registration( $country, $country_options ) {
		$request = array(
			'country'         => $country,
			'country_options' => array(
				strtolower( $country ) => $country_options,
			),
			'active_from'     => 'now',
		);

		$stripe_client             = $this->get_stripe_client( $this->api_key );
		$tax_registrations_service = new RegistrationService( $stripe_client );
		$tax_registrations_service->create( $request );
		if ( array_key_exists( $this->api_key, self::$tax_registrations ) ) {
			unset( self::$tax_registrations[ $this->api_key ] );
		}
	}

	/**
	 * Immediately end Tax Registration
	 *
	 * @param int $tax_registration_id Tax registration ID.
	 *
	 * @return void
	 * @throws ApiErrorException In case of API error.
	 * @see https://stripe.com/docs/api/tax/registrations/update
	 */
	public function end_immediately_registration( $tax_registration_id ) {
		$client                    = $this->get_stripe_client( $this->api_key );
		$tax_registrations_service = new RegistrationService( $client );
		$data                      = array( 'expires_at' => 'now' );
		try {
			$tax_registrations_service->update( $tax_registration_id, $data );
		} catch ( Exception $exception ) {
			\WC_Admin_Settings::add_error( __( 'Some wrong, try again later.', 'stripe-tax-for-woocommerce' ) );
		}
		$this->get_registrations( true );
	}

	/**
	 * Prepares information to lock merchant from creating already existent tax registrations.
	 * Used also to prepare data about existent tax registrations for Stripe Tax page that uses this function for:
	 * 1. Pre-render checkboxes (making them checked and disabled) to visualize which tax registrations already exists.
	 * 2. Change checkboxes status on-the-fly, on tax registration change on Stripe Tax settings page.
	 *
	 * @return array
	 * @throws ApiErrorException In case of API error.
	 */
	public function get_locks(): array {
		$tax_registrations = $this->get_registrations();
		$locks             = array();
		$locks[ StripeTaxPluginHelper::LOCK_COUNTRIES ]            = array();
		$locks[ StripeTaxPluginHelper::LOCK_US_STATES ]            = array();
		$locks[ StripeTaxPluginHelper::LOCK_OSS_UNION ]            = false;
		$locks[ StripeTaxPluginHelper::LOCK_OSS_NON_UNION ]        = false;
		$locks[ StripeTaxPluginHelper::LOCK_IOSS ]                 = false;
		$locks[ StripeTaxPluginHelper::LOCK_CA_PROVINCES ]         = array();
		$locks[ StripeTaxPluginHelper::LOCK_CHICAGO_LEASE ]        = array();
		$locks[ StripeTaxPluginHelper::LOCK_CHICAGO_AMUSEMENT ]    = array();
		$locks[ StripeTaxPluginHelper::LOCK_BLOOMINGTON ]          = array();
		$locks[ StripeTaxPluginHelper::LOCK_EAST_DUNDEE ]          = array();
		$locks[ StripeTaxPluginHelper::LOCK_EVANSTON ]             = array();
		$locks[ StripeTaxPluginHelper::LOCK_SCHILLER_PARK ]        = array();
		$locks[ StripeTaxPluginHelper::LOCK_LOCAL_COMMUNICATIONS ] = array();
		$standard_countries                                        = StripeTaxPluginHelper::get_tax_registration_standard_tax_countries();
		$digital_countries = StripeTaxPluginHelper::get_tax_registration_digital_countries();
		$eu_countries      = StripeTaxPluginHelper::get_tax_registration_eu_countries();

		foreach ( $tax_registrations->data as $registration ) {
			$country               = $registration->country;
			$country_in_lower_case = strtolower( $country );
			$country_options       = $registration->country_options->$country_in_lower_case;
			if ( in_array( $country, $standard_countries, true ) ) {
				if ( 'standard' === $country_options->type ) {
					$locks[ StripeTaxPluginHelper::LOCK_COUNTRIES ][] = $country;
				}
			} elseif ( in_array( $country, $eu_countries, true ) ) {
				if ( 'standard' === $country_options->type ) {
					$locks[ StripeTaxPluginHelper::LOCK_COUNTRIES ][] = $country;
				}
				if ( 'oss_union' === $country_options->type ) {
					$locks[ StripeTaxPluginHelper::LOCK_OSS_UNION ] = true;
				}
				if ( 'oss_non_union' === $country_options->type ) {
					$locks[ StripeTaxPluginHelper::LOCK_OSS_NON_UNION ] = true;
				}
				if ( 'ioss' === $country_options->type ) {
					$locks[ StripeTaxPluginHelper::LOCK_IOSS ] = true;
				}
			} elseif ( in_array( $country, $digital_countries, true ) ) {
				if ( 'simplified' === $country_options->type ) {
					$locks[ StripeTaxPluginHelper::LOCK_COUNTRIES ][] = $country;
				}
			} elseif ( 'CA' === $country ) {
				if ( in_array( $country_options->type, array( 'standard', 'simplified' ), true ) ) {
					$locks[ StripeTaxPluginHelper::LOCK_COUNTRIES ][] = $country;
				}
				if ( 'province_standard' === $country_options->type ) {
					$locks[ StripeTaxPluginHelper::LOCK_CA_PROVINCES ][] = $country_options->province_standard->province;
				}
			} elseif ( 'US' === $country ) {
				$state = $country_options->state;
				if ( 'state_sales_tax' === $country_options->type ) {
					$locks[ StripeTaxPluginHelper::LOCK_US_STATES ][] = $state;
				}
				if ( 'local_lease_tax' === $country_options->type ) {
					$locks[ StripeTaxPluginHelper::LOCK_CHICAGO_LEASE ][] = $state;
				}
				if ( 'local_amusement_tax' === $country_options->type ) {
					switch ( $country_options->local_amusement_tax->jurisdiction ) {
						case StripeTaxPluginHelper::FIPS_CHICAGO:
							$locks[ StripeTaxPluginHelper::LOCK_CHICAGO_AMUSEMENT ][] = $state;
							break;
						case StripeTaxPluginHelper::FIPS_BLOOMINGTON:
							$locks[ StripeTaxPluginHelper::LOCK_BLOOMINGTON ][] = $state;
							break;
						case StripeTaxPluginHelper::FIPS_EAST_DUNDEE:
							$locks[ StripeTaxPluginHelper::LOCK_EAST_DUNDEE ][] = $state;
							break;
						case StripeTaxPluginHelper::FIPS_EVANSTON:
							$locks[ StripeTaxPluginHelper::LOCK_EVANSTON ][] = $state;
							break;
						case StripeTaxPluginHelper::FIPS_SCHILLER_PARK:
							$locks[ StripeTaxPluginHelper::LOCK_SCHILLER_PARK ][] = $state;
							break;
					}
				}

				if ( 'state_communications_tax' === $country_options->type ) {
					$locks[ StripeTaxPluginHelper::LOCK_LOCAL_COMMUNICATIONS ][] = $state;
				}
			}
		}

		return $locks;
	}
}
