<?php
/**
 * Tax Settings service
 *
 * @package Stripe\StripeTaxForWooCommerce\Stripe
 */

namespace Stripe\StripeTaxForWooCommerce\Stripe;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

use stdClass;
use Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException;
use Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Tax\SettingsService;
use Exception;
use Stripe\StripeTaxForWooCommerce\SDK\lib\Tax\Settings;
use Stripe\StripeTaxForWooCommerce\Stripe\Exception\CountryStateException;
use Stripe\StripeTaxForWooCommerce\Stripe\Exception\CountrySupportException;
use Stripe\StripeTaxForWooCommerce\Stripe\Exception\TaxBehaviorException;
use Stripe\StripeTaxForWooCommerce\WooCommerce\ErrorRenderer;

/**
 * Tax Setting service
 */
class TaxSettings {
	use StripeClientTrait;

	/**
	 * Stripe API key
	 *
	 * @var string
	 */
	protected $api_key = '';

	/**
	 * Settings array
	 *
	 * @var array
	 */
	protected static $settings = array();

	/**
	 * Countries mapping array.
	 * Maps country name, that may be received from Stripe to ISO 3166-1 alpha-2 country code.
	 *
	 * @var array
	 */
	protected static $countries_mapping = array();

	/**
	 * Creates TaxSettings service
	 *
	 * @param string $api_key API key.
	 */
	public function __construct( string $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Get country mapping, that maps country names, that may be received from Stripe into ISO 3166-1 alpha-2 country code.
	 *
	 * @return array
	 */
	public static function get_mappings() {
		if ( empty( static::$countries_mapping ) ) {
			static::$countries_mapping = wp_json_file_decode( STRIPE_TAX_FOR_WOOCOMMERCE_JSON_DIR . 'countries.json' );
		}

		return static::$countries_mapping;
	}

	/**
	 * Convert state name, that can be received from Stripe into state code
	 *
	 * @param string $country Country.
	 * @param string $state State.
	 *
	 * @return string
	 */
	protected function convert_state_name_into_state_code( $country, $state ) {
		$mappings = static::get_mappings();

		if ( empty( $mappings->$country ) ) {
			return $state;
		}

		foreach ( $mappings->$country as $state_data ) {
			if ( $state_data->key === $state ) {
				$state = $state_data->iso;
				break;
			}
		}

		return $state;
	}

	/**
	 * Get settings from API
	 *
	 * @return object|Settings
	 * @throws ApiErrorException In case of API error.
	 * @see https://stripe.com/docs/api/tax/settings/retrieve
	 */
	protected function get_from_api_call(): object {
		$stripe_client                         = $this->get_stripe_client( $this->api_key );
		$settings                              = StripeTaxPluginHelper::fill_stripe_tax_settings_object( $stripe_client->tax->settings->retrieve( array() ) );
		$settings->head_office->address->state = $this->convert_state_name_into_state_code( $settings->head_office->address->country, $settings->head_office->address->state );

		return $settings;
	}

	/**
	 * Get tax settings from cache or API call
	 *
	 * @param bool $force_api_call Force API call.
	 *
	 * @return object|Settings
	 * @throws ApiErrorException In case of API error.
	 * @see https://stripe.com/docs/api/tax/settings/retrieve
	 */
	public function get_settings( $force_api_call = false ) {
		if ( array_key_exists( $this->api_key, static::$settings ) && ( ! $force_api_call ) ) {
			return static::$settings[ $this->api_key ];
		}
		static::$settings[ $this->api_key ] = $this->get_from_api_call();

		return static::$settings[ $this->api_key ];
	}

	/**
	 * Updates Stripe Tax settings in cache and Stripe dashboard
	 *
	 * @param object $tax_settings Tax settings.
	 * @param bool   $no_api_call Set true, if no API call needed.
	 *
	 * @return void
	 * @throws ApiErrorException In case of API error.
	 * @see https://stripe.com/docs/api/tax/settings/update
	 */
	public function set_settings( object $tax_settings, bool $no_api_call = false ): void {
		static::$settings[ $this->api_key ] = $tax_settings;
		if ( $no_api_call ) {
			return;
		}
		$is_set_settings_success = false;
		try {
			Validate::validate_country_support( $this->get_country() );

			$state_handlers = array(
				'AE' => 'get_allowed_origin_address_ae_provinces',
				'AU' => 'get_allowed_origin_address_au_states',
				'CA' => 'get_allowed_origin_address_ca_provinces',
				'ES' => 'get_allowed_origin_address_es_provinces',
				'HK' => 'get_allowed_origin_address_hk_areas',
				'IE' => 'get_allowed_origin_address_ie_counties',
				'IT' => 'get_allowed_origin_address_it_provinces',
				'JP' => 'get_allowed_origin_address_jp_prefectures',
				'US' => 'get_allowed_origin_address_us_states',
			);

			if ( isset( $state_handlers[ $this->get_country() ] ) ) {
				$handler_method = $state_handlers[ $this->get_country() ];
				Validate::validate_country_state( $this->get_country(), $this->get_state(), StripeTaxPluginHelper::$handler_method() );
			}

			$client                                        = $this->get_stripe_client( $this->api_key );
			$setting_service                               = new SettingsService( $client );
			$save_setting                                  = array();
			$save_setting['head_office[address][country]'] = $this->get_country();
			if ( $this->get_state() ) {
				$save_setting['head_office[address][state]'] = $this->get_state();
			}
			$save_setting['head_office[address][city]']        = $this->get_city();
			$save_setting['head_office[address][line1]']       = $this->get_line1();
			$save_setting['head_office[address][line2]']       = $this->get_line2();
			$save_setting['head_office[address][postal_code]'] = $this->get_postal_code();
			$save_setting['defaults[tax_code]']                = $this->get_tax_code();
			$setting_service->update( $save_setting );
			$is_set_settings_success = true;
		} catch ( CountrySupportException $exception ) {
			ErrorRenderer::set_error_object( 'setting_country_error', $exception->getMessage(), 'error' );
		} catch ( CountryStateException $exception ) {
			ErrorRenderer::set_error_object( 'setting_state_error', $exception->getMessage(), 'error' );
		} catch ( TaxBehaviorException $exception ) {
			ErrorRenderer::set_error_object( 'setting_tax_behavior_error', $exception->getMessage(), 'error' );
		} catch ( Exception $exception ) {
			\WC_Admin_Settings::add_error( $exception->getMessage() );
		} finally {
			if ( ! $is_set_settings_success ) {
				StripeTaxPluginHelper::set_stripe_settings_update_error_flag();
			}
			$this->get_settings( true );
		}
	}

	/**
	 * Get Tax Settings country
	 *
	 * @return string
	 * @throws ApiErrorException In case of API error.
	 */
	public function get_country(): string {
		return $this->get_settings()->head_office->address->country ?? '';
	}

	/**
	 * Get Tax Settings address line1
	 *
	 * @return string
	 * @throws ApiErrorException In case of API error.
	 */
	public function get_line1(): string {
		return $this->get_settings()->head_office->address->line1 ?? '';
	}

	/**
	 * Get Tax Settings address line2
	 *
	 * @return string
	 * @throws ApiErrorException In case of API error.
	 */
	public function get_line2(): string {
		return $this->get_settings()->head_office->address->line2 ?? '';
	}

	/**
	 * Get Tax Settings city
	 *
	 * @return string
	 * @throws ApiErrorException In case of API error.
	 */
	public function get_city(): string {
		return $this->get_settings()->head_office->address->city ?? '';
	}

	/**
	 * Get Tax Settings state
	 *
	 * @return string
	 * @throws ApiErrorException In case of API error.
	 */
	public function get_state(): string {
		return $this->get_settings()->head_office->address->state ?? '';
	}

	/**
	 * Get Tax Settings postal code
	 *
	 * @return string
	 * @throws ApiErrorException In case of API error.
	 */
	public function get_postal_code(): string {
		return $this->get_settings()->head_office->address->postal_code ?? '';
	}

	/**
	 * Get Tax Settings tax code
	 *
	 * @return string
	 * @throws ApiErrorException In case of API error.
	 */
	public function get_tax_code(): string {
		return $this->get_settings()->defaults->tax_code ?? '';
	}

	/**
	 * Get Tax Settings tax behavior
	 *
	 * @return string
	 */
	public function get_tax_behavior(): string {
		return wc_prices_include_tax() ? 'inclusive' : 'exclusive';
	}

	/**
	 * Get Tax Settings from POST request
	 *
	 * @param bool $live Live.
	 *
	 * @return stdClass
	 */
	public static function get_from_post_request( bool $live ): object {
		check_admin_referer( 'woocommerce-settings' );
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'woocommerce-settings' ) ) {
			exit;
		}

		$prefix      = 'stripe_tax_for_woocommerce_' . ( $live ? 'live' : 'test' ) . '_mode_';
		$tax_code    = $prefix . 'tax_code';
		$city        = $prefix . 'city';
		$country     = $prefix . 'country';
		$line1       = $prefix . 'line1';
		$line2       = $prefix . 'line2';
		$postal_code = $prefix . 'postal_code';
		$state       = $prefix . 'state';

		$tax_settings                     = new stdClass();
		$tax_settings->object             = 'tax.settings';
		$tax_settings->defaults           = new stdClass();
		$tax_settings->defaults->tax_code = sanitize_text_field( wp_unslash( $_POST[ $tax_code ] ?? null ) );

		$tax_settings->head_office                       = new stdClass();
		$tax_settings->head_office->address              = new stdClass();
		$tax_settings->head_office->address->city        = sanitize_text_field( wp_unslash( $_POST[ $city ] ?? null ) );
		$tax_settings->head_office->address->country     = sanitize_text_field( wp_unslash( $_POST[ $country ] ?? null ) );
		$tax_settings->head_office->address->line1       = sanitize_text_field( wp_unslash( $_POST[ $line1 ] ?? null ) );
		$tax_settings->head_office->address->line2       = sanitize_text_field( wp_unslash( $_POST[ $line2 ] ?? null ) );
		$tax_settings->head_office->address->postal_code = sanitize_text_field( wp_unslash( $_POST[ $postal_code ] ?? null ) );
		$tax_settings->head_office->address->state       = sanitize_text_field( wp_unslash( $_POST[ $state ][ $_POST[ $country ] ?? null ] ?? null ) );

		return $tax_settings;
	}
}
