<?php
/**
 * Class provides functionality to manage plugin settings.
 *
 * @package  Stripe\StripeTaxForWooCommerce\WooCommerce
 */

namespace Stripe\StripeTaxForWooCommerce\WooCommerce;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

use Exception;
use Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException;
use Stripe\StripeTaxForWooCommerce\Stripe\StripeTaxPluginHelper;
use Stripe\StripeTaxForWooCommerce\Stripe\TaxRegistrations;
use Stripe\StripeTaxForWooCommerce\Stripe\TaxSettings;
use Stripe\StripeTaxForWooCommerce\WordPress\Options;
use WC_Settings_Page;

/**
 * Allows to configure Tax Settings in WooCommerce.
 */
class StripeTax extends WC_Settings_Page {

	/**
	 * Tax Registrations service.
	 *
	 * @var TaxRegistrations
	 */
	protected $tax_registration;

	/**
	 * Tax Settings service.
	 *
	 * @var TaxSettings
	 */
	protected $tax_settings;

	/**
	 * Factory method for TaxRegistrations service.
	 *
	 * @param string $api_key Stripe API key.
	 */
	protected function get_tax_registration( string $api_key ): TaxRegistrations {
		if ( is_null( $this->tax_registration ) ) {
			$this->tax_registration = new TaxRegistrations( $api_key );
		}
		return $this->tax_registration;
	}


	/**
	 * Factory method for TaxSettings service.
	 *
	 * @param string $api_key Stripe API key.
	 *
	 * @return TaxSettings
	 */
	protected function get_tax_settings( string $api_key ): TaxSettings {
		if ( is_null( $this->tax_settings ) ) {
			$this->tax_settings = new TaxSettings( $api_key );
		}
		return $this->tax_settings;
	}

	/**
	 * Add this page to settings right after WooCommerce "Tax" tab (or to the end if "Tax" tab not exists).
	 *
	 * @param array $pages The settings array where we'll add ourselves.
	 *
	 * @return mixed
	 */
	public function add_settings_page( $pages ) {
		if ( ! array_key_exists( 'tax', $pages ) || array_key_exists( $this->id, $pages ) ) {
			return parent::add_settings_page( $pages );
		}
		$new_pages = array();
		foreach ( $pages as $page_id => $page_label ) {
			$new_pages[ $page_id ] = $page_label;
			if ( 'tax' === $page_id ) {
				$new_pages[ $this->id ] = $this->label;
			}
		}

		return $new_pages;
	}

	/**
	 * Constructor for StripeTax.
	 */
	public function __construct() {
		$this->id    = 'stripe_tax_for_woocommerce';
		$this->label = __( 'Stripe Tax', 'stripe-tax-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * Ends immediately single or bulk Stripe Tax Registrations.
	 *
	 * @return void
	 */
	protected function stripe_tax_for_woocommerce_end_registration_immediately(): void {
		global $current_section;

		check_admin_referer( 'woocommerce-settings' );
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'woocommerce-settings' ) ) {
			$this->do_exit();
		}

		$api_key = Options::get_live_mode_key();

		$tax_registrations = $this->get_tax_registration( $api_key );

		if ( isset( $_GET['tax_registration_id'] ) ) {
			$tax_registrations->end_immediately_registration( sanitize_text_field( wp_unslash( $_GET['tax_registration_id'] ) ) );
		}

		if ( isset( $_POST['tax_registration_id'] ) ) {
			$tax_registration_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['tax_registration_id'] ) );

			foreach ( $tax_registration_id as $reg ) {
				$tax_registrations->end_immediately_registration( $reg );
			}
		}
	}

	/**
	 * Adds Stripe Tax Registrations if it not exists.
	 *
	 * @return void
	 * @throws Exception In case of API error.
	 */
	protected function stripe_tax_for_woocommerce_add_tax_registrations(): void {
		check_admin_referer( 'woocommerce-settings' );
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'woocommerce-settings' ) ) {
			$this->do_exit();
		}

		$api_key = Options::get_live_mode_key();

		if ( ! isset( $_POST['stripe_tax_for_woocommerce_tax_registration_country'] ) ) {
			return;
		}
		$country = sanitize_text_field( wp_unslash( $_POST['stripe_tax_for_woocommerce_tax_registration_country'] ) );

		$tax_registrations = $this->get_tax_registration( $api_key );

		// $locks array contains prepared for easy check list of already added tax registrations.
		// In this case it used to lock user from adding already added tax registrations.
		$locks = $tax_registrations->get_locks();

		if ( in_array( $country, StripeTaxPluginHelper::get_tax_registration_eu_countries(), true ) ) {
			$has_registration_type = false;

			if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_type_standard'] ) && '1' === $_POST['stripe_tax_for_woocommerce_tax_registration_type_standard'] ) {
				$has_registration_type = true;

				if ( ! in_array( $country, $locks[ StripeTaxPluginHelper::LOCK_COUNTRIES ], true ) ) {
					$tax_registrations->create_registration(
						$country,
						array(
							'type'     => 'standard',
							'standard' => array(
								'place_of_supply_scheme' => 'standard',
							),
						)
					);
				} else {
					$this->throw_duplicate_country_exception( $country );
				}
			}
			if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_type_oss_union'] ) && '1' === $_POST['stripe_tax_for_woocommerce_tax_registration_type_oss_union'] ) {
				$has_registration_type = true;

				if ( ! $locks[ StripeTaxPluginHelper::LOCK_OSS_UNION ] ) {
					$tax_registrations->create_registration(
						$country,
						array(
							'type' => 'oss_union',
						)
					);
				} else {
					$this->throw_duplicate_country_exception( $country );
				}
			}
			if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_type_oss_non_union'] ) && '1' === $_POST['stripe_tax_for_woocommerce_tax_registration_type_oss_non_union'] ) {
				$has_registration_type = true;

				if ( ! $locks[ StripeTaxPluginHelper::LOCK_OSS_NON_UNION ] ) {
					$tax_registrations->create_registration(
						$country,
						array(
							'type' => 'oss_non_union',
						)
					);
				} else {
					$this->throw_duplicate_country_exception( $country );
				}
			}
			if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_type_ioss'] ) && '1' === $_POST['stripe_tax_for_woocommerce_tax_registration_type_ioss'] ) {
				$has_registration_type = true;

				if ( ! $locks[ StripeTaxPluginHelper::LOCK_IOSS ] ) {
					$tax_registrations->create_registration(
						$country,
						array(
							'type' => 'ioss',
						)
					);
				} else {
					$this->throw_duplicate_country_exception( $country );
				}
			}
			if ( ! $has_registration_type ) {
				$this->throw_duplicate_country_exception( $country );
			}
		} elseif ( in_array( $country, StripeTaxPluginHelper::get_tax_registration_digital_countries(), true ) ) {
			if ( ! in_array( $country, $locks[ StripeTaxPluginHelper::LOCK_COUNTRIES ], true ) ) {
				$tax_registrations->create_registration(
					$country,
					array(
						'type' => 'simplified',
					)
				);
			} else {
				$this->throw_duplicate_country_exception( $country );
			}
		} elseif ( in_array( $country, StripeTaxPluginHelper::get_tax_registration_standard_tax_countries(), true ) ) {
			if ( ! in_array( $country, $locks[ StripeTaxPluginHelper::LOCK_COUNTRIES ], true ) ) {
				$tax_registrations->create_registration(
					$country,
					array(
						'type' => 'standard',
					)
				);
			} else {
				$this->throw_duplicate_country_exception( $country );
			}
		} elseif ( 'CA' === $country ) {
			$has_province = false;

			if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_type_bc'] ) && '1' === $_POST['stripe_tax_for_woocommerce_tax_registration_type_bc'] ) {
				$has_province = true;

				if ( ! in_array( 'BC', $locks[ StripeTaxPluginHelper::LOCK_CA_PROVINCES ], true ) ) {
					$tax_registrations->create_registration(
						$country,
						array(
							'type'              => 'province_standard',
							'province_standard' => array(
								'province' => 'BC',
							),
						)
					);
				} else {
					$this->throw_duplicate_country_exception( $country, 'BC' );
				}
			}
			if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_type_mb'] ) && '1' === $_POST['stripe_tax_for_woocommerce_tax_registration_type_mb'] ) {
				$has_province = true;

				if ( ! in_array( 'MB', $locks[ StripeTaxPluginHelper::LOCK_CA_PROVINCES ], true ) ) {
					$tax_registrations->create_registration(
						$country,
						array(
							'type'              => 'province_standard',
							'province_standard' => array(
								'province' => 'MB',
							),
						)
					);
				} else {
					$this->throw_duplicate_country_exception( $country, 'MB' );
				}
			}
			if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_type_sk'] ) && '1' === $_POST['stripe_tax_for_woocommerce_tax_registration_type_sk'] ) {
				$has_province = true;

				if ( ! in_array( 'SK', $locks[ StripeTaxPluginHelper::LOCK_CA_PROVINCES ], true ) ) {
					$tax_registrations->create_registration(
						$country,
						array(
							'type'              => 'province_standard',
							'province_standard' => array(
								'province' => 'SK',
							),
						)
					);
				} else {
					$this->throw_duplicate_country_exception( $country, 'SK' );
				}
			}
			if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_type_qc'] ) && '1' === $_POST['stripe_tax_for_woocommerce_tax_registration_type_qc'] ) {
				$has_province = true;

				if ( ! in_array( 'QC', $locks[ StripeTaxPluginHelper::LOCK_CA_PROVINCES ], true ) ) {
					$tax_registrations->create_registration(
						$country,
						array(
							'type'              => 'province_standard',
							'province_standard' => array(
								'province' => 'QC',
							),
						)
					);
				} else {
					$this->throw_duplicate_country_exception( $country, 'QC' );
				}
			}
			if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_type_ca'] ) ) {
				$canada_registration_type = sanitize_text_field( wp_unslash( $_POST['stripe_tax_for_woocommerce_tax_registration_type_ca'] ) );
				if ( in_array( $canada_registration_type, array( 'standard', 'simplified' ), true ) ) {
					if ( ! in_array( $country, $locks[ StripeTaxPluginHelper::LOCK_COUNTRIES ], true ) ) {
						$tax_registrations->create_registration(
							$country,
							array(
								'type' => $canada_registration_type,
							)
						);
					} elseif ( ! $has_province ) {
						$this->throw_duplicate_country_exception( $country );
					}
				}
			}
		} elseif ( 'US' === $country ) {
			if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_us_state'] ) ) {
				$us_state = sanitize_text_field( wp_unslash( $_POST['stripe_tax_for_woocommerce_tax_registration_us_state'] ) );
				if ( in_array( $us_state, StripeTaxPluginHelper::get_tax_registration_us_states(), true ) ) {
					if ( ! in_array( $us_state, StripeTaxPluginHelper::get_tax_registration_no_sales_tax_us_states(), true ) ) {
						if ( in_array( $us_state, StripeTaxPluginHelper::get_tax_registration_local_communications_tax_us_states(), true ) ) {
							if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_type_us_state_communications'] ) && '1' === $_POST['stripe_tax_for_woocommerce_tax_registration_type_us_state_communications'] ) {
								if ( ! in_array( $us_state, $locks[ StripeTaxPluginHelper::LOCK_LOCAL_COMMUNICATIONS ], true ) ) {
									$tax_registrations->create_registration(
										$country,
										array(
											'state' => $us_state,
											'type'  => 'state_communications_tax',
										)
									);
								} else {
									$this->throw_duplicate_country_exception( $country, $us_state );
								}
							}
						}
						if ( in_array( $us_state, StripeTaxPluginHelper::get_tax_registration_lease_and_amusement_tax_us_states(), true ) ) {
							if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_type_us_state_lease_tax'] ) && '1' === $_POST['stripe_tax_for_woocommerce_tax_registration_type_us_state_lease_tax'] ) {
								if ( ! in_array( $us_state, $locks[ StripeTaxPluginHelper::LOCK_CHICAGO_LEASE ], true ) ) {
									$tax_registrations->create_registration(
										$country,
										array(
											'state' => $us_state,
											'type'  => 'local_lease_tax',
											'local_lease_tax' => array(
												'jurisdiction' => StripeTaxPluginHelper::FIPS_CHICAGO,
											),
										)
									);
								} else {
									$this->throw_duplicate_country_exception( $country, $us_state );
								}
							}
							if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_type_us_state_chicago'] ) && '1' === $_POST['stripe_tax_for_woocommerce_tax_registration_type_us_state_chicago'] ) {
								if ( ! in_array( $us_state, $locks[ StripeTaxPluginHelper::LOCK_CHICAGO_AMUSEMENT ], true ) ) {
									$tax_registrations->create_registration(
										$country,
										array(
											'state' => $us_state,
											'type'  => 'local_amusement_tax',
											'local_amusement_tax' => array(
												'jurisdiction' => StripeTaxPluginHelper::FIPS_CHICAGO,
											),
										)
									);
								} else {
									$this->throw_duplicate_country_exception( $country, $us_state );
								}
							}
							if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_type_us_state_bloomington'] ) && '1' === $_POST['stripe_tax_for_woocommerce_tax_registration_type_us_state_bloomington'] ) {
								if ( ! in_array( $us_state, $locks[ StripeTaxPluginHelper::LOCK_BLOOMINGTON ], true ) ) {
									$tax_registrations->create_registration(
										$country,
										array(
											'state' => $us_state,
											'type'  => 'local_amusement_tax',
											'local_amusement_tax' => array(
												'jurisdiction' => StripeTaxPluginHelper::FIPS_BLOOMINGTON,
											),
										)
									);
								} else {
									$this->throw_duplicate_country_exception( $country, $us_state );
								}
							}
							if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_type_us_state_east_dundee'] ) && '1' === $_POST['stripe_tax_for_woocommerce_tax_registration_type_us_state_east_dundee'] ) {
								if ( ! in_array( $us_state, $locks[ StripeTaxPluginHelper::LOCK_EAST_DUNDEE ], true ) ) {
									$tax_registrations->create_registration(
										$country,
										array(
											'state' => $us_state,
											'type'  => 'local_amusement_tax',
											'local_amusement_tax' => array(
												'jurisdiction' => StripeTaxPluginHelper::FIPS_EAST_DUNDEE,
											),
										)
									);
								} else {
									$this->throw_duplicate_country_exception( $country, $us_state );
								}
							}
							if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_type_us_state_evanston'] ) && '1' === $_POST['stripe_tax_for_woocommerce_tax_registration_type_us_state_evanston'] ) {
								if ( ! in_array( $us_state, $locks[ StripeTaxPluginHelper::LOCK_EVANSTON ], true ) ) {
									$tax_registrations->create_registration(
										$country,
										array(
											'state' => $us_state,
											'type'  => 'local_amusement_tax',
											'local_amusement_tax' => array(
												'jurisdiction' => StripeTaxPluginHelper::FIPS_EVANSTON,
											),
										)
									);
								} else {
									$this->throw_duplicate_country_exception( $country, $us_state );
								}
							}
							if ( isset( $_POST['stripe_tax_for_woocommerce_tax_registration_type_us_state_schiller_park'] ) && '1' === $_POST['stripe_tax_for_woocommerce_tax_registration_type_us_state_schiller_park'] ) {
								if ( ! in_array( $us_state, $locks[ StripeTaxPluginHelper::LOCK_SCHILLER_PARK ], true ) ) {
									$tax_registrations->create_registration(
										$country,
										array(
											'state' => $us_state,
											'type'  => 'local_amusement_tax',
											'local_amusement_tax' => array(
												'jurisdiction' => StripeTaxPluginHelper::FIPS_SCHILLER_PARK,
											),
										)
									);
								} else {
									$this->throw_duplicate_country_exception( $country, $us_state );
								}
							}
						}
						if ( ! in_array( $us_state, $locks[ StripeTaxPluginHelper::LOCK_US_STATES ], true ) ) {
							$tax_registrations->create_registration(
								$country,
								array(
									'state' => $us_state,
									'type'  => 'state_sales_tax',
								)
							);
						} else {
							$this->throw_duplicate_country_exception( $country, $us_state );
						}
					}
				}
			}
		}
	}

	/**
	 * Throw duplication exception for registrations.
	 *
	 * @param string      $country Specific country.
	 * @param string|null $region Specific region.
	 * @return void
	 * @throws Exception For duplicates.
	 */
	private function throw_duplicate_country_exception( string $country, string $region = null ): void {
		if ( ! is_null( $region ) && ( 'CA' === $country || 'US' === $country ) ) {
			throw new Exception(
				sprintf(
					/* translators: %s: Region full name */
					esc_html__( 'You already have a registration for %s', 'stripe-tax-for-woocommerce' ),
					esc_html( WC()->countries->get_states()[ $country ][ $region ] )
				)
			);
		}

		throw new Exception(
			sprintf(
				/* translators: %s: Country full name */
				esc_html__( 'You already have a registration for %s', 'stripe-tax-for-woocommerce' ),
				esc_html( WC()->countries->get_countries()[ $country ] )
			)
		);
	}

	/**
	 * Saves live mode Stripe Tax plugin settings from $_POST data
	 *
	 * @return void
	 * @throws ApiErrorException In case of API error.
	 */
	protected function stripe_tax_for_woocommerce_save_live_mode_options(): void {
		$live_mode_key = Options::get_live_mode_key();

		if ( ! $live_mode_key ) {
			return;
		}

		if ( isset( $_GET['add_tax_registration'] ) ) {
			try {
				static::stripe_tax_for_woocommerce_add_tax_registrations();
			} catch ( Exception $e ) {
				ErrorRenderer::set_error_object( 'add_tax_registration', $e->getMessage(), 'error' );
			}

			return;
		}

		if ( array_key_exists( 'stripe_tax_for_woocommerce_enable_live_mode', $_POST ) && is_string( $_POST['stripe_tax_for_woocommerce_enable_live_mode'] ) ) {
			check_admin_referer( 'woocommerce-settings' );
			if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'woocommerce-settings' ) ) {
				StripeTaxPluginHelper::do_exit();
			}
			$enable_live_mode = sanitize_text_field( wp_unslash( $_POST['stripe_tax_for_woocommerce_enable_live_mode'] ) );
			if ( '1' === $enable_live_mode ) {
				Options::update_option( Options::OPTION_LIVE_MODE_ENABLED, '1' );
			} else {
				Options::update_option( Options::OPTION_LIVE_MODE_ENABLED, '0' );
			}
		}

		try {
			$tax_settings        = $this->get_tax_settings( $live_mode_key );
			$posted_tax_settings = $tax_settings::get_from_post_request( true );

			$tax_settings->set_settings( $posted_tax_settings );
		} catch ( Exception $e ) {
			\WC_Admin_Settings::add_error( $e->getMessage() );
		}
	}

	/**
	 * Callback, triggered by "Save changes" button
	 *
	 * @return void
	 * @throws ApiErrorException In case of API error.
	 */
	public function save(): void {
		global $current_section;
		switch ( $current_section ) {
			case '':
				$this->stripe_tax_for_woocommerce_save_live_mode_options();
				break;
		}
	}

	/**
	 * Callback returns list of sections
	 *
	 * @return array<string, string> List of sections
	 */
	protected function get_own_sections(): array {
		return array(
			'' => __( 'Live mode', 'stripe-tax-for-woocommerce' ),
		);
	}

	/**
	 * Callback triggered, when section output needed
	 *
	 * @return void
	 */
	public function output(): void {
		global $current_section;
		switch ( $current_section ) {
			case '':
				if ( isset( $_REQUEST['action'] ) && 'trash' === $_REQUEST['action'] ) {
					check_admin_referer( 'woocommerce-settings' );
					if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'woocommerce-settings' ) ) {
						$this->do_exit();
					}
					$this->stripe_tax_for_woocommerce_end_registration_immediately();
					wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=stripe_tax_for_woocommerce&section=' . $current_section ) . ( isset( $_GET['add_tax_registration'] ) ? '&add_tax_registration' : '' ) );
					$this->do_exit();
				}
				if ( isset( $_GET['add_tax_registration'] ) ) {
					include STRIPE_TAX_FOR_WOOCOMMERCE_TEMPLATES_DIR . 'add-tax-registration.php';
					break;
				}
				include STRIPE_TAX_FOR_WOOCOMMERCE_TEMPLATES_DIR . 'live-mode.php';
				break;
		}
	}

	// phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn

	/**
	 * This function is used as a wrapper for the PHP's built-in exit function.
	 *
	 * @return never-return
	 */
	public function do_exit() {
		exit;
	}
	// phpcs:enable Squiz.Commenting.FunctionComment.InvalidNoReturn
}
