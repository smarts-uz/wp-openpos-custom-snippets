<?php
/**
 * Provides tax registrations list table functionality.
 *
 * @package Stripe\StripeTaxForWooCommerce\WordPress
 */

namespace Stripe\StripeTaxForWooCommerce\WordPress;

use Stripe\StripeTaxForWooCommerce\Stripe\StripeTaxPluginHelper;
use Stripe\StripeTaxForWooCommerce\Stripe\TaxRegistrations;
use WP_List_Table;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

/**
 * TaxRegistrationsListTable Class.
 */
class TaxRegistrationsListTable extends WP_List_Table {

	/**
	 * Stripe API key.
	 *
	 * @var string
	 */
	protected $stripe_tax_for_woocommerce_api_key;

	/**
	 * Keep TaxRegistrations to prevent duplicates.
	 *
	 * @var TaxRegistrations
	 */
	protected $tax_registration;

	/**
	 * Sets Stripe API key.
	 *
	 * @param string $api_key API key.
	 */
	public function set_api_key( $api_key ) {
		$this->stripe_tax_for_woocommerce_api_key = $api_key;
	}

	/**
	 * Create TaxRegistrations object once and always return it.
	 *
	 * @return TaxRegistrations
	 */
	public function getTaxRegistrations() {
		if ( null === $this->tax_registration ) {
			$this->tax_registration = new TaxRegistrations( $this->stripe_tax_for_woocommerce_api_key );
		}
		return $this->tax_registration;
	}


	/**
	 * Initialize WordPress list table names.
	 *
	 * @param array $args Construct arguments.
	 */
	public function __construct( $args = array() ) {
		parent::__construct(
			array(
				'singular' => 'Tax Registration',
				'plural'   => 'Tax Registrations',
				'ajax'     => false,
			)
		);
	}

	/**
	 * No items found text.
	 */
	public function no_items() {
		esc_html_e( 'No Tax Registrations found.', 'stripe-tax-for-woocommerce' );
	}

	/**
	 * Get list of columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'                   => '<input type="checkbox">',
			'country'              => __( 'Country', 'stripe-tax-for-woocommerce' ),
			'state'                => __( 'State', 'stripe-tax-for-woocommerce' ) . '/' . __( 'Province', 'stripe-tax-for-woocommerce' ),
			'status'               => __( 'Status', 'stripe-tax-for-woocommerce' ),
			'start_date'           => __( 'Registration start date', 'stripe-tax-for-woocommerce' ),
			'tax_type'             => __( 'Tax type', 'stripe-tax-for-woocommerce' ),
			'type_of_registration' => __( 'Type of registration', 'stripe-tax-for-woocommerce' ),
		);
	}

	/**
	 * Checkbox column.
	 *
	 * @param array $item Key data.
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return '<input type="checkbox" name="tax_registration_id[]" value="' . esc_attr( $item['tax_registration_id'] ) . '">';
	}

	/**
	 * Handles row actions.
	 *
	 * @param array  $item The item.
	 * @param string $column_name The name of column.
	 * @param string $primary The primary column name.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		global $current_section;

		if ( $primary !== $column_name ) {
			return '';
		}

		$actions = array(
			'trash' => sprintf(
				'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
				// There are no nonce exists or needed, because it is just a regular page view without any changes made by user input.
				// phpcs:disable WordPress.Security.NonceVerification.Recommended
				admin_url( 'admin.php?page=wc-settings&tab=stripe_tax_for_woocommerce&section=' . $current_section . ( isset( $_GET['add_tax_registration'] ) ? '&add_tax_registration' : '' ) . '&action=trash&tax_registration_id=' . rawurlencode( $item['tax_registration_id'] ) . '&_wpnonce=' . rawurlencode( wp_create_nonce( 'woocommerce-settings' ) ) ),
				// phpcs:enable WordPress.Security.NonceVerification.Recommended
				esc_attr( __( 'End immediately', 'stripe-tax-for-woocommerce' ) ),
				__( 'End immediately', 'stripe-tax-for-woocommerce' )
			),
		);

		if ( strtolower( $item['status'] ) === 'scheduled' ) {
			$actions['trash'] = sprintf(
				'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
				// There are no nonce exists or needed, because it is just a regular page view without any changes made by user input.
				// phpcs:disable WordPress.Security.NonceVerification.Recommended
				'https://dashboard.stripe.com/tax/registrations/' . rawurlencode( $item['tax_registration_id'] ),
				// phpcs:enable WordPress.Security.NonceVerification.Recommended
				esc_attr( __( 'Edit registration', 'stripe-tax-for-woocommerce' ) ),
				__( 'Edit registration', 'stripe-tax-for-woocommerce' )
			);
		}

		return $this->row_actions( $actions );
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'trash' => __( 'End immediately', 'stripe-tax-for-woocommerce' ),
		);
	}

	/**
	 * Prepares the items to be rendered by WordPress List Table class.
	 */
	public function prepare_items() {
		$tax_registrations_instance                = $this->getTaxRegistrations();
		$tax_registrations                         = $tax_registrations_instance->get_registrations();
		$tax_data                                  = $tax_registrations->data;
		$items                                     = array();
		$tax_registration_countries                = StripeTaxPluginHelper::get_allowed_tax_registration_countries();
		$tax_registration_us_states                = StripeTaxPluginHelper::get_allowed_tax_registration_us_states();
		$tax_registration_us_tax_names             = StripeTaxPluginHelper::get_tax_registration_us_tax_names();
		$tax_registration_us_tax_types             = StripeTaxPluginHelper::get_tax_registration_us_tax_types();
		$tax_registration_us_type_of_registration  = StripeTaxPluginHelper::get_tax_registration_us_type_of_registration();
		$tax_registration_ca_tax_names             = StripeTaxPluginHelper::get_tax_registration_ca_tax_names();
		$tax_registration_ca_provinces             = StripeTaxPluginHelper::get_allowed_tax_registration_ca_provinces();
		$tax_registration_ca_tax_types             = StripeTaxPluginHelper::get_tax_registration_ca_tax_types();
		$tax_registration_ca_types_of_registration = StripeTaxPluginHelper::get_tax_registration_ca_type_of_registration();
		$tax_registration_eu_countries             = StripeTaxPluginHelper::get_tax_registration_eu_countries();

		foreach ( $tax_data as $item ) {
			$country = strtolower( $item->country );
			if ( in_array(
				$item->country_options->$country->type,
				array(
					'oss_union',
					'oss_non_union',
					'ioss',
				),
				true
			) ) {
				$flag = StripeTaxPluginHelper::get_tax_registration_flag_eu_image_file_contents();
			} else {
				$flag = StripeTaxPluginHelper::get_tax_registration_flag_image_file_contents( $country );
			}
			$state                = '—';
			$country_name         = $tax_registration_countries[ $item->country ];
			$start_date           = wp_date( 'M j, Y', $item->active_from );
			$tax_type             = '';
			$type_of_registration = '';
			if ( 'us' === $country ) {
				$state = $tax_registration_us_states[ $item->country_options->$country->state ] ?? '—';
				if ( 'state_communications_tax' === $item->country_options->$country->type ) {
					$tax_registration_us_tax = $tax_registration_us_tax_names[ StripeTaxPluginHelper::DISPLAY_STATE_COMMUNICATIONS_TAX ];
					$state                  .= ' - ' . $tax_registration_us_tax;
				} elseif ( 'local_amusement_tax' === $item->country_options->$country->type ) {
					$jurisdiction            = $item->country_options->$country->local_amusement_tax->jurisdiction;
					$tax_registration_us_tax = $tax_registration_us_tax_names[ StripeTaxPluginHelper::DISPLAY_LOCAL_AMUSEMENT_TAX_PREFIX . $jurisdiction ] ?? '';
					$state                  .= ' - ' . $tax_registration_us_tax;
				} elseif ( 'local_lease_tax' === $item->country_options->$country->type ) {
					$jurisdiction            = $item->country_options->$country->local_lease_tax->jurisdiction;
					$tax_registration_us_tax = $tax_registration_us_tax_names[ StripeTaxPluginHelper::DISPLAY_LOCAL_LEASE_TAX_PREFIX . $jurisdiction ] ?? '';
					$state                  .= ' - ' . $tax_registration_us_tax;
				}
				if ( array_key_exists( $item->country_options->$country->type, $tax_registration_us_tax_types ) ) {
					$tax_type = $tax_registration_us_tax_types[ $item->country_options->$country->type ];
				}
				if ( array_key_exists( $item->country_options->$country->type, $tax_registration_us_type_of_registration ) ) {
					$type_of_registration = $tax_registration_us_type_of_registration[ $item->country_options->$country->type ];
				}
			} elseif ( 'ca' === $country ) {
				$state = $tax_registration_ca_provinces[ $item->country_options->$country->province_standard->province ?? '' ] ?? '—';
				if ( 'standard' === $item->country_options->$country->type || 'simplified' === $item->country_options->$country->type ) {
					$tax_registration_ca_tax = $tax_registration_ca_tax_names[ $item->country_options->$country->type ];
					$country_name           .= '(' . $tax_registration_ca_tax . ')';
				} elseif ( 'province_standard' === $item->country_options->$country->type ) {
					$province                = $item->country_options->$country->province_standard->province;
					$tax_registration_ca_tax = $tax_registration_ca_tax_names[ $item->country_options->$country->type . '_' . $province ];
					$country_name           .= '(' . $tax_registration_ca_tax . ')';
				}
				if ( array_key_exists( $item->country_options->$country->type, $tax_registration_ca_tax_types ) ) {
					$tax_type = $tax_registration_ca_tax_types[ $item->country_options->$country->type ];
				}
				if ( array_key_exists( $item->country_options->$country->type, $tax_registration_ca_types_of_registration ) ) {
					$type_of_registration = $tax_registration_ca_types_of_registration[ $item->country_options->$country->type ];
				}
			} elseif ( in_array( $item->country, $tax_registration_eu_countries, true ) ) {
				if ( array_key_exists( $item->country_options->$country->type, StripeTaxPluginHelper::TAX_REGISTRATION_EU_TAX_NAMES ) ) {
					if ( StripeTaxPluginHelper::TAX_REGISTRATION_EU_TAX_NAMES[ $item->country_options->$country->type ] ) {
						$tax_registration_eu_tax = StripeTaxPluginHelper::TAX_REGISTRATION_EU_TAX_NAMES[ $item->country_options->$country->type ];
						$country_name           .= ' - ' . $tax_registration_eu_tax;
					}
				}
			}
			if ( array_key_exists( $item->country, StripeTaxPluginHelper::TAX_REGISTRATION_VAT_STANDARD_COUNTRIES ) ) {
				$tax_type             = __( 'tax: VAT', 'stripe-tax-for-woocommerce' );
				$type_of_registration = __( 'type: Standard', 'stripe-tax-for-woocommerce' );
				if ( array_key_exists( $item->country_options->$country->type, StripeTaxPluginHelper::TAX_REGISTRATION_EU_TAX_NAMES ) ) {
					if ( StripeTaxPluginHelper::TAX_REGISTRATION_EU_TAX_NAMES[ $item->country_options->$country->type ] ) {
						$type_of_registration = StripeTaxPluginHelper::TAX_REGISTRATION_EU_TAX_NAMES[ $item->country_options->$country->type ];
					}
				}
			} elseif ( array_key_exists( $item->country, StripeTaxPluginHelper::TAX_REGISTRATION_VAT_SIMPLIFIED_COUNTRIES ) ) {
				$tax_type             = __( 'tax: VAT', 'stripe-tax-for-woocommerce' );
				$type_of_registration = __( 'type: Simplified', 'stripe-tax-for-woocommerce' );
			} elseif ( array_key_exists( $item->country, StripeTaxPluginHelper::TAX_REGISTRATION_GST_STANDARD_COUNTRIES ) ) {
				$tax_type             = __( 'tax: GST', 'stripe-tax-for-woocommerce' );
				$type_of_registration = __( 'type: Standard', 'stripe-tax-for-woocommerce' );
			} elseif ( array_key_exists( $item->country, StripeTaxPluginHelper::TAX_REGISTRATION_JCT_STANDARD_COUNTRIES ) ) {
				$tax_type             = __( 'tax: JCT', 'stripe-tax-for-woocommerce' );
				$type_of_registration = __( 'type: Standard', 'stripe-tax-for-woocommerce' );
			} elseif ( array_key_exists( $item->country, StripeTaxPluginHelper::TAX_REGISTRATION_SERVICE_TAX_SIMPLIFIED_COUNTRIES ) ) {
				$tax_type             = __( 'tax: Service tax', 'stripe-tax-for-woocommerce' );
				$type_of_registration = __( 'type: Simplified', 'stripe-tax-for-woocommerce' );
			}

			$items[] = array(
				'tax_registration_id'  => $item->id,
				'country'              => '<div class="stripe_tax_for_woocommerce_list_registration_country">' . $flag . ' ' . $country_name . '</div>',
				'state'                => $state,
				'status'               => ucfirst( $item->status ),
				'start_date'           => esc_html( $start_date ),
				'tax_type'             => $tax_type,
				'type_of_registration' => $type_of_registration,
			);

		}
		$this->items = $items;

		$this->_column_headers = array( $this->get_columns(), array(), array() );
	}

	/**
	 * Get the content for the tax_registration_id column.
	 *
	 * @param array $key The key.
	 *
	 * @return array Column data.
	 */
	public function column_tax_registration_id( $key ) {
		return $key['tax_registration_id'];
	}

	/**
	 * Get the content for the Country column.
	 *
	 * @param array $key The key.
	 *
	 * @return array Column data.
	 */
	public function column_country( $key ) {
		return $key['country'];
	}

	/**
	 * Get the content for the State column.
	 *
	 * @param array $key The key.
	 *
	 * @return array Column data.
	 */
	public function column_state( $key ) {
		return $key['state'];
	}

	/**
	 * Get the content for the Status column.
	 *
	 * @param array $key The key.
	 *
	 * @return array Column data.
	 */
	public function column_status( $key ) {
		return $key['status'];
	}

	/**
	 * Get the content for the start_date column.
	 *
	 * @param array $key The key.
	 *
	 * @return array Column data.
	 */
	public function column_start_date( $key ) {
		return $key['start_date'];
	}

	/**
	 * Get the content for the tax_type column.
	 *
	 * @param array $key The key.
	 *
	 * @return array Column data.
	 */
	public function tax_type( $key ) {
		return $key['tax_type'];
	}

	/**
	 * Type of registration.
	 *
	 * @param array $key The key.
	 *
	 * @return array Column data.
	 */
	public function type_of_registration( $key ) {
		return $key['type_of_registration'];
	}

	/**
	 * Default column.
	 *
	 * @param array  $item Item.
	 * @param string $column_name Column name.
	 *
	 * @return array Column data.
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}
}
