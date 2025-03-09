<?php
/**
 * Install WordPress Hooks.
 *
 * @package Stripe\StripeTaxForWooCommerce\WordPress
 */

namespace Stripe\StripeTaxForWooCommerce\WordPress;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Utilities\NumberUtil;
use Exception;
use Stripe\StripeTaxForWooCommerce\SDK\lib\Exception\ApiErrorException;
use Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AccountService;
use Stripe\StripeTaxForWooCommerce\SDK\lib\Stripe;
use Stripe\StripeTaxForWooCommerce\SDK\lib\StripeClient;
use Stripe\StripeTaxForWooCommerce\Stripe\CalculateTax;
use Stripe\StripeTaxForWooCommerce\Stripe\StripeTaxPluginHelper;
use Stripe\StripeTaxForWooCommerce\Stripe\TaxCodeList;
use Stripe\StripeTaxForWooCommerce\Stripe\TaxExemptions;
use Stripe\StripeTaxForWooCommerce\Stripe\TaxRegistrations;
use Stripe\StripeTaxForWooCommerce\Stripe\TaxSettings;
use Stripe\StripeTaxForWooCommerce\Stripe\TaxTransaction;
use Stripe\StripeTaxForWooCommerce\WooCommerce\Connect;
use Stripe\StripeTaxForWooCommerce\WooCommerce\ErrorRenderer;
use Stripe\StripeTaxForWooCommerce\WooCommerce\ExtendedProduct;
use Stripe\StripeTaxForWooCommerce\WooCommerce\StripeOrderItemTax;
use Stripe\StripeTaxForWooCommerce\WooCommerce\StripeTax;
use WC_Data;

/**
 * Class for adding WordPress actions, filter, registering styles and scripts
 */
class Hooks {

	/**
	 * Contains WP Hooks initialization state.
	 *
	 * @var bool $hooks_initialized
	 */
	protected static $hooks_initialized;

	/**
	 * Tax exemptions object.
	 *
	 * @var TaxExemptions|null $tax_exemptions
	 */
	protected static $tax_exemptions;

	/**
	 * Map line item type name to line item group name.
	 *
	 * @var array $type_to_group
	 */
	protected static $type_to_group = array(
		'line_item' => 'line_items',
		'tax'       => 'tax_lines',
		'shipping'  => 'shipping_lines',
		'fee'       => 'fee_lines',
		'coupon'    => 'coupon_lines',
	);

	/**
	 * Tax transaction object.
	 *
	 * @var TaxTransaction|null
	 */
	protected static $tax_transaction;

	/**
	 * If true then the errors are collected and added to the REST response if $error_reporting_collect_enabled is true.
	 *
	 * @var bool $error_reporting_collect
	 */
	protected static $error_reporting_collect = false;

	/**
	 * If true then the collected errors are added to the REST response.
	 *
	 * @var bool $error_reporting_collect
	 */
	protected static $error_reporting_collect_enabled = false;

	/**
	 * Create and return tax transaction object.
	 *
	 * @return TaxTransaction Returns the tax transaction object.
	 */
	protected static function get_tax_transaction() {
		if ( is_null( static::$tax_transaction ) ) {
			static::$tax_transaction = new TaxTransaction( Options::get_live_mode_key() );
		}
		return static::$tax_transaction;
	}

	/**
	 * Register plugin's CSS styles.
	 */
	public static function action_admin_enqueue_styles() {
		wp_enqueue_style(
			'stripe_tax_for_woocommerce_admin',
			STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_CSS_URL . 'stripe_tax_for_woocommerce_admin.css',
			array(),
			filemtime( STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_CSS_DIR . 'stripe_tax_for_woocommerce_admin.css' )
		);
	}

	/**
	 * Register JS and CSS files required for the admin panel.
	 */
	protected static function admin_enqueue_styles() {
		add_action( 'admin_enqueue_scripts', array( static::class, 'action_admin_enqueue_styles' ), 10, 0 );
	}

	/**
	 * Register JS files required for WP Admin(backend page) and add settings names localizations.
	 */
	public static function action_admin_enqueue_scripts() {
		wp_enqueue_script(
			'stripe_tax_for_woocommerce_admin',
			STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_JS_URL . 'stripe_tax_for_woocommerce_admin.js',
			array( 'jquery' ),
			filemtime( STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_JS_DIR . 'stripe_tax_for_woocommerce_admin.js' ),
			true
		);

		global $current_section;

		$localize_script = array(
			'ajax_url'                                    => admin_url( 'admin-ajax.php' ),
			'current_section_url'                         => admin_url( 'admin.php?page=wc-settings&tab=stripe_tax_for_woocommerce&section=' . $current_section ),
			'city_label'                                  => __( 'City', 'stripe-tax-for-woocommerce' ),
			'city_is_district_label'                      => __( 'District', 'stripe-tax-for-woocommerce' ),
			'city_is_district_countries'                  => array_keys( StripeTaxPluginHelper::get_city_is_district_countries() ),
			'city_is_town_or_city_label'                  => __( 'Town or City', 'stripe-tax-for-woocommerce' ),
			'city_is_town_or_city_countries'              => array_keys( StripeTaxPluginHelper::get_city_is_town_or_city_countries() ),
			'postal_code_label'                           => __( 'Postal code', 'stripe-tax-for-woocommerce' ),
			'postal_code_is_eircode_label'                => __( 'Eircode', 'stripe-tax-for-woocommerce' ),
			'postal_code_is_eircode_countries'            => array_keys( StripeTaxPluginHelper::get_postal_code_is_eircode_countries() ),
			'postal_code_is_zip_label'                    => __( 'ZIP', 'stripe-tax-for-woocommerce' ) . StripeTaxPluginHelper::get_required_field_mark_html(),
			'postal_code_is_zip_countries'                => array_keys( StripeTaxPluginHelper::get_postal_code_is_zip_countries() ),
			'postal_code_no_city_countries'               => array_keys( StripeTaxPluginHelper::get_no_city_countries() ),
			'postal_code_no_postal_code_countries'        => array_keys( StripeTaxPluginHelper::get_no_postal_code_countries() ),
			'tax_registrations_lease_and_amusement_tax_use_states' => StripeTaxPluginHelper::get_tax_registration_lease_and_amusement_tax_us_states(),
			'tax_registrations_local_communications_tax_us_states' => StripeTaxPluginHelper::get_tax_registration_local_communications_tax_us_states(),
			'tax_registrations_no_sales_tax_us_states'    => StripeTaxPluginHelper::get_tax_registration_no_sales_tax_us_states(),
			'tax_registrations_eu_countries'              => StripeTaxPluginHelper::get_tax_registration_eu_countries(),
			/* translators: %s: country code, eg. 'US' */
			'tax_registrations_localize_domestic'         => __( 'Domestic (registered in %s)', 'stripe-tax-for-woocommerce' ),
			/* translators: %s: country code, eg. 'US' */
			'tax_registrations_localize_domestic_description' => __( 'Common for businesses selling goods and services to customers in %s.', 'stripe-tax-for-woocommerce' ),
			/* translators: %s: country code, eg. 'US' */
			'tax_registrations_localize_local_communications' => __( '%s State and Local Communications Tax', 'stripe-tax-for-woocommerce' ),
			/* translators: %s: country code, eg. 'US' */
			'tax_registrations_localize_local_communications_description' => __( 'Common for businesses selling video or audio streaming to customers in %s. This includes the Communications Services Tax, Communications Services Gross Receipts Tax and Local Communications Services Tax.', 'stripe-tax-for-woocommerce' ),
			/* translators: %s: country code, eg. 'US' */
			'tax_registrations_localize_no_sales_description' => __( 'You don’t need to add a registration in %s because there’s no sales tax in this state.', 'stripe-tax-for-woocommerce' ),
			'tax_ids_html'                                => CalculateTax::get_tax_ids_admin_html(),
			'disconnect_from_stripe_message_confirmation' => __( 'Are you sure you want to disconnect Stripe Tax plugin from Stripe Account?', 'stripe-tax-for-woocommerce' ),
		);

		$api_key = Options::get_live_mode_key();

		try {
			$tax_registrations = new TaxRegistrations( $api_key );
			// $locks array contains prepared for easy check list of already added tax registrations.
			// In this case it used to lock "on-the-fly" checkboxes as checked and disabled by JavaScript.
			$locks                                      = $tax_registrations->get_locks();
			$localize_script['tax_registrations_locks'] = $locks;
			// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		} catch ( Exception $e ) {
			$stripe_tax_error_message = 'Error fetching tax registrations: ' . $e->getMessage();
		}
		wp_localize_script( 'stripe_tax_for_woocommerce_admin', 'stripe_tax_for_woocommerce', $localize_script );
	}

	/**
	 * Register WP Admin JS.
	 */
	protected static function admin_enqueue_scripts() {
		add_action( 'admin_enqueue_scripts', array( static::class, 'action_admin_enqueue_scripts' ), 10, 0 );
	}

	/**
	 * Register AJAX requests handlers for connection testing and disconnection functionality.
	 */
	protected static function admin_ajax() {
		add_action(
			'wp_ajax_stripe_tax_for_woocommerce_test_connection',
			array(
				AdminAjax::class,
				'test_connection',
			),
			10,
			0
		);
		add_action(
			'wp_ajax_stripe_tax_for_woocommerce_disconnect_from_stripe',
			array(
				AdminAjax::class,
				'disconnect_from_stripe',
			),
			10,
			0
		);
	}

	/**
	 * Filter adds Stripe Tax tab into WooCommerce Settings.
	 *
	 * @param array $settings Settings.
	 */
	public static function filter_add_stripe_tax_settings( $settings ) {
		$settings[] = new StripeTax();

		return $settings;
	}

	/**
	 * Saves additional fields for WooCommerce Product on save action.
	 *
	 * @param WC_Data $wc_data WooCommerce data.
	 *
	 * @throws Exception If something goes wrong.
	 */
	public static function action_woocommerce_after_product_object_save( WC_Data $wc_data ) {
		$stripe_wc_product = new ExtendedProduct( $wc_data->get_id() );

		$posted_tax_code = ExtendedProduct::get_on_save_post_parameter_tax_code( $wc_data );

		$stripe_wc_product->save_extended_product(
			array(
				'tax_code' => $posted_tax_code,
			)
		);
	}

	/**
	 *  Admin init action.
	 */
	public static function action_admin_init() {
		// Because there is no "classic" WordPress "nonce" - we disable CodeSniffer "NonceVerification" check.
		// Actual CSRF checks here made by using "wcs_stripe_state" GET parameter and validated in method get_stripe_oauth_keys().
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$current_tab      = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		$wcs_stripe_code  = isset( $_GET['wcs_stripe_code'] ) ? sanitize_text_field( wp_unslash( $_GET['wcs_stripe_code'] ) ) : '';
		$wcs_stripe_state = isset( $_GET['wcs_stripe_state'] ) ? sanitize_text_field( wp_unslash( $_GET['wcs_stripe_state'] ) ) : '';
		// phpcs:enable
		if ( 'stripe_tax_for_woocommerce' === $current_tab ) {
			if ( ! empty( $wcs_stripe_code ) ) {
				try {
					$secret_key = Connect::get_stripe_oauth_keys( $wcs_stripe_code, $wcs_stripe_state );
					Options::update_option( Options::OPTION_LIVE_MODE_SECRET_KEY, $secret_key );
					Connect::set_woocommerce_connect_last_error( __( 'Connect with Stripe successful', 'stripe-tax-for-woocommerce' ) );
				} catch ( Exception $e ) {
					Connect::set_woocommerce_connect_last_error( $e->getMessage() );
				}
				wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=stripe_tax_for_woocommerce' ) );
				static::do_exit();
			}
		}
	}

	// phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn

	/**
	 * Wrapper around the PHP's built-in die function.
	 *
	 * @return never-return
	 */
	protected static function do_exit() {
		exit;
	}
	// phpcs:enable Squiz.Commenting.FunctionComment.InvalidNoReturn

	/**
	 * Adds additional fields on WooCommerce Product page.
	 */
	public static function action_woocommerce_product_options_tax() {
		global $product_object;
		try {
			$tax_settings      = new TaxSettings( Options::get_live_mode_key() );
			$stripe_wc_product = new ExtendedProduct( $product_object->get_id() );
			$default_tax_code  = $tax_settings->get_tax_code();
			$tax_codes         = ( new TaxCodeList( Options::get_live_mode_key() ) )->get_as_key_value_formatted();
			woocommerce_wp_select(
				array(
					'id'          => '_stripe_tax_for_woocommerce_tax_code',
					'value'       => isset( $stripe_wc_product->get_extended_product()['tax_code'] ) ? $stripe_wc_product->get_extended_product()['tax_code'] : 'stfwc_inherit',
					'label'       => __( 'Stripe Tax - Product tax code', 'stripe-tax-for-woocommerce' ),
					'options'     => array_merge( array( 'stfwc_inherit' => __( 'Default' ) . ' (' . $default_tax_code . ' - ' . TaxCodeList::format_single( $default_tax_code, Options::get_live_mode_key() ) . ')' ), $tax_codes ),
					'desc_tip'    => 'true',
					'description' => __( 'Choose a stripe tax code for this product.', 'stripe-tax-for-woocommerce' ),
				)
			);
		} catch ( Exception $e ) {
			ErrorRenderer::set_error_object( 'product_tax_code_retrieve_error', esc_html( 'Stripe Tax: ' . $e->getMessage() ), 'error' );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo esc_html( ErrorRenderer::get_rendered_error( 'product_tax_code_retrieve_error' ) );
		}
	}

	/**
	 * Anything needed to be done on WooCommerce meta box render event.
	 */
	public static function action_add_meta_boxes() {
		add_action(
			'woocommerce_product_options_tax',
			array(
				static::class,
				'action_woocommerce_product_options_tax',
			),
			10,
			0
		);
	}

	/**
	 * Anything needed to be done, when we are on admin page.
	 */
	public static function action_init() {
		add_action( 'add_meta_boxes', array( static::class, 'action_add_meta_boxes' ), 30, 0 );
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param mixed $links Plugin Action links.
	 *
	 * @return array
	 */
	public static function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=stripe_tax_for_woocommerce' ) . '" aria-label="' . esc_attr__( 'View Stripe Tax settings', 'stripe_tax_for_woocommerce' ) . '">' . esc_html__( 'Settings', 'stripe-tax-for-woocommerce' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	/**
	 * Add plugin actions.
	 *
	 * @return void
	 */
	protected static function add_actions(): void {
		static::admin_enqueue_scripts();
		static::admin_ajax();
		static::add_action_tax_exemptions( static::$tax_exemptions );
		add_action(
			'woocommerce_after_product_object_save',
			array(
				static::class,
				'action_woocommerce_after_product_object_save',
			),
			10,
			1
		);
		add_action( 'init', array( static::class, 'action_init' ), 10, 0 );
		add_action(
			'rest_dispatch_request',
			array(
				static::class,
				'detect_error_reporting_collect',
			),
			10,
			3
		);
		add_action(
			'woocommerce_hydration_dispatch_request',
			array(
				static::class,
				'detect_error_reporting_collect',
			),
			10,
			3
		);
		add_action(
			'woocommerce_hydration_request_after_callbacks',
			array(
				static::class,
				'add_collected_errors_to_response',
			),
			10,
			3
		);
		add_action(
			'rest_request_after_callbacks',
			array(
				static::class,
				'add_collected_errors_to_response',
			),
			10,
			3
		);
		add_action(
			'woocommerce_order_partially_refunded',
			array(
				static::class,
				'action_woocommerce_order_partially_refunded',
			),
			20,
			2
		);
		add_action(
			'woocommerce_order_fully_refunded',
			array(
				static::class,
				'action_woocommerce_order_fully_refunded',
			),
			20,
			2
		);
		add_action(
			'woocommerce_order_status_changed',
			array(
				static::class,
				'action_woocommerce_order_status_changed',
			),
			20,
			4
		);
		add_action(
			'woocommerce_checkout_create_order_tax_item',
			array(
				static::class,
				'action_woocommerce_checkout_create_order_tax_item',
			),
			5,
			3
		);
		add_action(
			'woocommerce_order_before_calculate_totals',
			array(
				static::class,
				'action_calculate_totals',
			),
			100,
			2
		);

		add_action(
			'woocommerce_order_after_calculate_totals',
			array(
				static::class,
				'action_calculate_totals',
			),
			100,
			2
		);

		add_action( 'admin_notices', array( static::class, 'render_admin_notices' ) );
	}

	/**
	 * Add plugin filters.
	 *
	 * @return void
	 */
	protected static function add_filters(): void {
		add_filter(
			'woocommerce_order_type_to_group',
			array(
				static::class,
				'filter_woocommerce_order_type_to_group',
			),
			100,
			1
		);
		add_filter( 'woocommerce_rate_code', array( static::class, 'filter_woocommerce_rate_code' ), 5, 2 );
		add_filter( 'woocommerce_rate_label', array( static::class, 'filter_woocommerce_rate_label' ), 5, 2 );
		add_filter( 'woocommerce_rate_compound', array( static::class, 'filter_woocommerce_rate_compound' ), 5, 2 );
		add_filter(
			'woocommerce_get_order_item_classname',
			array(
				static::class,
				'filter_woocommerce_get_order_item_classname',
			),
			5,
			3
		);
		add_filter(
			'woocommerce_cart_hide_zero_taxes',
			'__return_false',
			20,
			0
		);
		add_filter(
			'woocommerce_after_calculate_totals',
			array(
				static::class,
				'action_woocommerce_after_calculate_totals',
			),
			20,
			2
		);

		// Using this to ignore Woocommerce rates to be displayed in shop.
		add_filter(
			'woocommerce_find_rates',
			array(
				static::class,
				'filter_woocommerce_find_rates',
			),
			10,
			0
		);
		add_filter( 'pre_option_wc_connect_taxes_enabled', fn () => Options::is_live_mode_enabled() );
		add_filter(
			'woocommerce_rest_prepare_shop_order_object',
			array(
				static::class,
				'filter_rest_prepare_shop_order_object',
			),
			10,
			1
		);
	}

	/**
	 * Using this to ignore Woocommerce rates to be displayed in shop.
	 */
	public static function filter_woocommerce_find_rates() {
		return array();
	}

	/**
	 * Action to be called when Stripe Tax calculation needed for WooCommerce Order.
	 *
	 * @param bool      $and_taxes And taxes.
	 * @param \WC_Order $wc_order WooCommerce order.
	 *
	 * @throws Exception If something goes wrong.
	 */
	public static function action_calculate_totals( $and_taxes, $wc_order ) {
		if ( ! Options::is_live_mode_enabled() || ! wc_tax_enabled() ) {
			return $and_taxes;
		}

		if ( ! ( $wc_order instanceof \WC_Order ) ) {
			return $and_taxes;
		}

		$currency         = strtolower( get_woocommerce_currency() );
		$customer_details = CalculateTax::get_customer_details_by_post();
		if ( empty( $customer_details ) ) {
			$customer_details = CalculateTax::get_customer_details_by_order( $wc_order );
		}
		if ( empty( $customer_details ) || ( ! is_admin() && ! CalculateTax::can_calculate_tax( $customer_details ) ) ) {
			return $and_taxes;
		}

		$line_items = CalculateTax::get_line_items_by_order( $wc_order );
		if ( ! $line_items ) {
			return $and_taxes;
		}

		$customer_details['taxability_override'] = CalculateTax::get_order_tax_exempt( $wc_order );
		try {

			if ( ! CalculateTax::can_calculate_tax( $customer_details ) ) {
				return $and_taxes;
			}

			$shipping_cost = CalculateTax::get_taxable_shipping_cost_from_cart_or_order_for_api( $wc_order, $currency );

			$calculate_tax = new CalculateTax(
				Options::get_live_mode_key(),
				$currency,
				$line_items,
				$customer_details,
				$shipping_cost
			);

			$response = $calculate_tax->get_response();

			$calculated_tax_amount   = $response->tax_amount_exclusive + $response->tax_amount_inclusive;
			$denormalized_tax_amount = CalculateTax::get_denormalized_amount( $calculated_tax_amount, $currency );

			$denormalized_amount_total = CalculateTax::get_denormalized_amount( $response->amount_total, $currency );

			$calculated_shipping_amount   = $response->shipping_cost->amount;
			$denormalized_shipping_amount = CalculateTax::get_denormalized_amount( $calculated_shipping_amount, $currency );

			$calculated_shipping_tax_amount   = $response->shipping_cost->amount_tax;
			$denormalized_shipping_tax_amount = CalculateTax::get_denormalized_amount( $calculated_shipping_tax_amount, $currency );

			$wc_order->remove_order_items( 'tax' );

			$line_items = $wc_order->get_items();

			$order_item_taxes      = array();
			$order_item_tax_totals = array();

			$counter = 0;

			foreach ( $line_items as $line_item ) {
				/**
				 * Line item.
				 *
				 * @var \WC_Order_Item_Product $line_item
				 */
				$line_item_tax_rates = CalculateTax::get_wc_rates_array_from_response_for_item( $response, $line_item );

				foreach ( $line_item_tax_rates as $line_item_tax_rate_id => $line_item_tax_rate ) {
					if ( ! array_key_exists( $line_item_tax_rate_id, $order_item_taxes ) ) {
						$order_item_taxes[ $line_item_tax_rate_id ] = new StripeOrderItemTax();
						$order_item_taxes[ $line_item_tax_rate_id ]->set_rate( $line_item_tax_rate_id );
					}

					$current_order_item_tax = CalculateTax::get_denormalized_amount( $line_item_tax_rate['amount'], $currency );

					$order_item_taxes[ $line_item_tax_rate_id ]->set_tax_total( ( (float) ( $order_item_taxes[ $line_item_tax_rate_id ]->get_tax_total( 'edit' ) ) + $current_order_item_tax ) );
					if ( ! array_key_exists( $counter, $order_item_tax_totals ) ) {
						$order_item_tax_totals[ $counter ]             = array();
						$order_item_tax_totals[ $counter ]['total']    = array();
						$order_item_tax_totals[ $counter ]['subtotal'] = array();
					}
					if ( ! array_key_exists( $line_item_tax_rate_id, $order_item_tax_totals[ $counter ]['total'] ) ) {
						$order_item_tax_totals[ $counter ]['total'][ $line_item_tax_rate_id ]    = 0.0;
						$order_item_tax_totals[ $counter ]['subtotal'][ $line_item_tax_rate_id ] = 0.0;
					}

					$order_item_tax_totals[ $counter ]['total'][ $line_item_tax_rate_id ]    += (float) ( $current_order_item_tax );
					$order_item_tax_totals[ $counter ]['subtotal'][ $line_item_tax_rate_id ] += (float) ( $current_order_item_tax );
					$order_item_tax_totals[ $counter ]['inclusive']                           = $line_item_tax_rate['inclusive'];
				}

				++$counter;

			}

			foreach ( $order_item_taxes as $order_item_tax ) {
				$wc_order->add_item( $order_item_tax );

				$order_item_tax->save();
				$wc_order->save();
			}

			$counter = 0;

			foreach ( $line_items as $line_item ) {
				if ( array_key_exists( $counter, $order_item_tax_totals ) ) {
					unset( $order_item_tax_totals[ $counter ]['inclusive'] );
					unset( $order_item_tax_totals[ $counter ]['amount'] );
					$line_item->set_taxes( $order_item_tax_totals[ $counter ] );
					$item_tax_total    = array_sum( array_values( $order_item_tax_totals[ $counter ]['total'] ) );
					$item_tax_subtotal = array_sum( array_values( $order_item_tax_totals[ $counter ]['subtotal'] ) );
					$line_item->set_subtotal_tax( $item_tax_subtotal );
					$line_item->set_total_tax( $item_tax_total );
					$line_item->save();
				}
				++$counter;
			}

			if ( $shipping_cost ) {

				$shipping_methods = $wc_order->get_shipping_methods();

				CalculateTax::apply_tax_to_order_shipping_methods( $shipping_methods, $calculate_tax, $currency );
				$wc_order->set_shipping_total( $denormalized_shipping_amount + CalculateTax::get_cart_or_order_not_taxable_shipping_total( $wc_order ) );
				if ( 'inclusive' === $response->shipping_cost->tax_behavior ) {
					$wc_order->set_shipping_total( CalculateTax::get_denormalized_amount( $calculated_shipping_amount - $calculated_shipping_tax_amount, $currency ) + CalculateTax::get_cart_or_order_not_taxable_shipping_total( $wc_order ) );
				}
				$wc_order->set_shipping_tax( $denormalized_shipping_tax_amount );

				if ( $calculated_shipping_amount > 0 ) {
					$wc_order->add_item( $calculate_tax->get_shipping_order_item_tax( $currency ) );
				}
			}

			$wc_order->set_cart_tax( $denormalized_tax_amount );
			$wc_order->set_total( NumberUtil::round( $denormalized_amount_total + $wc_order->get_total_fees() + CalculateTax::get_cart_or_order_not_taxable_shipping_total( $wc_order ), wc_get_price_decimals() ) );
			$wc_order->save();
		} catch ( Exception $e ) {
			static::handle_calculate_tax_error( $e );
		}

		return $and_taxes;
	}


	/**
	 * Handles exceptions.
	 * If the request is REST then it adds the error message to ErrorRenderer,
	 * otherwise it formats and outputs the error message.
	 *
	 * @param Exception $e Handled exception.
	 */
	public static function handle_calculate_tax_error( $e ) {
		if ( ! ErrorRenderer::get_error_object( 'calculate_tax_error' )->message ) {
			ErrorRenderer::set_error_object( 'calculate_tax_error', 'Stripe Tax: ' . $e->getMessage(), 'error' );
		} else {
			// Error already reported.
			return;
		}

		if ( ! static::$error_reporting_collect ) {
			echo wp_kses( ErrorRenderer::get_rendered_error( 'calculate_tax_error' ), StripeTaxPluginHelper::get_admin_allowed_html() );
		} else {
			static::$error_reporting_collect_enabled = true;
		}
	}

	/**
	 * Preserve original WooCommerce order types to group plus any added by another plugin.
	 *
	 * @param array $type_to_group Type to group.
	 */
	public static function filter_woocommerce_order_type_to_group( $type_to_group ) {
		static::$type_to_group = $type_to_group;

		return $type_to_group;
	}

	/**
	 * Render admin notices.
	 *
	 * @return void
	 */
	public static function render_admin_notices() {
		if ( get_transient( 'stripe_tax_for_woocommerce_activate' ) ) {
			include STRIPE_TAX_FOR_WOOCOMMERCE_TEMPLATES_DIR . 'plugin-activate-notice.php';
			delete_transient( 'stripe_tax_for_woocommerce_activate' );
		}
	}

	/**
	 * WooCommerce order partially refunded action.
	 *
	 * @param int $order_id Order id.
	 * @param int $refund_id Refund id.
	 *
	 * @throws Exception If something goes wrong.
	 */
	public static function action_woocommerce_order_partially_refunded( $order_id, $refund_id ) {
		$refund               = new \WC_Order_Refund( $refund_id );
		$tax_transaction      = static::get_tax_transaction();
		$tax_transaction_data = $tax_transaction->get( $order_id )->tax_transaction;
		$line_items           = CalculateTax::get_line_items_by_order( $refund, true, $tax_transaction_data );

		$currency = strtolower( get_woocommerce_currency() );

		$shipping_total = (float) $refund->get_shipping_total( 'edit' );
		$shipping_tax   = (float) $refund->get_shipping_tax( 'edit' );

		$shipping_cost = array();

		if ( $shipping_total < 0.0 || $shipping_tax < 0.0 ) {
			$shipping_cost = array(
				'amount'     => CalculateTax::get_normalized_amount( $shipping_total, $currency ),
				'amount_tax' => CalculateTax::get_normalized_amount( $shipping_tax, $currency ),
			);
		}

		$tax_transaction->create_reversal( $order_id, $line_items, $shipping_cost );
	}

	/**
	 * WooCommerce order fully refunded action.
	 *
	 * @param int $order_id Order id.
	 *
	 * @throws ApiErrorException In case of API error.
	 */
	public static function action_woocommerce_order_fully_refunded( $order_id ) {
		$tax_transaction = static::get_tax_transaction();
		$tax_transaction->create_reversal( $order_id );
	}

	/**
	 * Creates Stripe Tax transaction when order payment made (status became "processing" or suddenly "completed").
	 *
	 * @param int       $order_id Order id.
	 * @param string    $status_from From status.
	 * @param string    $status_to To status.
	 * @param \WC_Order $wc_order The WooCommerce order.
	 */
	public static function action_woocommerce_order_status_changed( $order_id, $status_from, $status_to, $wc_order ) {
		/**
		 * WC Order.
		 *
		 * @var \WC_Order $wc_order
		 */

		if ( ! Options::is_live_mode_enabled() || ! wc_tax_enabled() ) {
			return;
		}

		try {
			if ( 'completed' === $status_to && 'processing' === $status_from ) {
				return;
			}

			if ( 'processing' !== $status_to && 'completed' !== $status_to ) {
				return;
			}

			$currency   = strtolower( get_woocommerce_currency() );
			$line_items = CalculateTax::get_line_items_by_order( $wc_order );

			$customer_details = CalculateTax::get_customer_details_by_post();
			if ( ! $customer_details ) {
				$customer_details = CalculateTax::get_customer_details_by_order( $wc_order );
			}

			$customer_details['taxability_override'] = CalculateTax::get_order_tax_exempt( $wc_order );

			$shipping_cost = CalculateTax::get_taxable_shipping_cost_from_cart_or_order_for_api( $wc_order, $currency );

			$calculate_tax = new CalculateTax(
				Options::get_live_mode_key(),
				$currency,
				$line_items,
				$customer_details,
				$shipping_cost
			);

			$response = $calculate_tax->get_response();

			$tax_transaction = static::get_tax_transaction();
			$tax_transaction->create( $response, $order_id );
			$calculate_tax->delete();
		} catch ( Exception $e ) {
			if ( ! ErrorRenderer::get_error_object( 'calculate_tax_error' )->message && is_admin() ) {
				ErrorRenderer::set_error_object( 'calculate_tax_error', 'Stripe Tax: ' . $e->getMessage(), 'error' );
				echo wp_kses( ErrorRenderer::get_rendered_error( 'calculate_tax_error' ), StripeTaxPluginHelper::get_admin_allowed_html() );
			}
		}
	}

	/**
	 * Fixes WooCommerce Tax rate ID for Stripe Tax (by making possible to use "string" keys, not only "integers").
	 *
	 * @param mixed $item The tax item.
	 * @param int   $tax_rate_id Tax rate id.
	 *
	 * @throws \ReflectionException If something goes wrong.
	 */
	public static function action_woocommerce_checkout_create_order_tax_item( $item, $tax_rate_id ) {
		if ( strpos( $tax_rate_id, 'stripe_tax_for_woocommerce_' ) !== 0 ) {
			return;
		}

		$ref_object = new \ReflectionObject( $item );

		$data_property = $ref_object->getProperty( 'data' );
		$data_property->setAccessible( true );
		$data = $data_property->getValue( $item );

		$object_read_property = $ref_object->getProperty( 'object_read' );
		$object_read_property->setAccessible( true );
		$object_read = $object_read_property->getValue( $item );

		$changes_property = $ref_object->getProperty( 'changes' );
		$changes_property->setAccessible( true );
		$changes = $changes_property->getValue( $item );

		if ( array_key_exists( 'rate_id', $data ) ) {
			if ( true === $object_read ) {
				if ( $tax_rate_id !== $data['rate_id'] || array_key_exists( 'rate_id', $changes ) ) {
					$changes['rate_id'] = $tax_rate_id;
				}
			} else {
				$data['rate_id'] = $tax_rate_id;
			}
		}

		$rate_percent = (float) ( explode( '__', $tax_rate_id )[2] );

		if ( array_key_exists( 'rate_percent', $data ) ) {
			if ( true === $object_read ) {
				if ( $rate_percent !== $data['rate_percent'] || array_key_exists( 'rate_percent', $changes ) ) {
					$changes['rate_percent'] = $rate_percent;
				}
			} else {
				$data['rate_percent'] = $rate_percent;
			}
		}

		$data_property->setValue( $item, $data );
		$changes_property->setValue( $item, $changes );
	}

	/**
	 * Filter the WooCommerce tax rate code.
	 *
	 * @param string $code_string Code.
	 * @param string $key The key.
	 */
	public static function filter_woocommerce_rate_code( $code_string, $key ) {
		if ( strpos( $key, 'stripe_tax_for_woocommerce' ) === 0 ) {
			return $key;
		}

		return $code_string;
	}

	/**
	 * Filter for the WooCommerce rate label.
	 *
	 * @param string $rate_name Rate name.
	 * @param string $key The key.
	 */
	public static function filter_woocommerce_rate_label( $rate_name, $key ) {
		if ( strpos( $key, 'stripe_tax_for_woocommerce' ) === 0 ) {
			return explode( '__', $key )[3];
		}

		return $rate_name;
	}

	/**
	 * Filter WooCommerce rate compound.
	 *
	 * @param bool   $compound The compound.
	 * @param string $key The key.
	 */
	public static function filter_woocommerce_rate_compound( $compound, $key ) {
		if ( strpos( $key, 'stripe_tax_for_woocommerce' ) === 0 ) {
			return false;
		}

		return $compound;
	}

	/**
	 * Replace WooCommerce Order Item Tax class to ours StripeOrderItemTax class
	 *
	 * @param class-string $classname The classname.
	 * @param string       $item_type The item type.
	 *
	 * @return class-string
	 */
	public static function filter_woocommerce_get_order_item_classname( $classname, $item_type ) {
		if ( 'tax' === $item_type ) {
			return StripeOrderItemTax::class;
		}

		return $classname;
	}

	/**
	 * Filter to alter cart totals.
	 * Used here to set shipping taxes.
	 *
	 * @param \WC_Cart $wc_cart WooCommerce Cart object.
	 */
	public static function action_woocommerce_after_calculate_totals( \WC_Cart $wc_cart ) {
		if ( ! Options::is_live_mode_enabled() || ! wc_tax_enabled() ) {
			return;
		}

		try {
			$currency = strtolower( get_woocommerce_currency() );

			$customer = $wc_cart->get_customer();

			$line_items = CalculateTax::get_line_items_by_cart( $wc_cart );

			$customer_details                        = CalculateTax::get_customer_details_by_order( $customer );
			$customer_details['taxability_override'] = static::$tax_exemptions->get_tax_exeption( get_current_user_id() );

			if ( ! CalculateTax::can_calculate_tax( $customer_details ) ) {
				return;
			}

			$shipping_cost = CalculateTax::get_taxable_shipping_cost_from_cart_or_order_for_api( $wc_cart, $currency );

			$calculate_tax = new CalculateTax(
				Options::get_live_mode_key(),
				$currency,
				$line_items,
				$customer_details,
				$shipping_cost
			);

			$response = $calculate_tax->get_response();

			$shipping_methods = StripeTaxPluginHelper::get_cart_shipping_methods( $wc_cart );
			$shipping_methods = CalculateTax::apply_tax_to_cart_shipping_methods( $shipping_methods, $calculate_tax, $currency );
			CalculateTax::calculate_cart_shipping( $shipping_methods, $wc_cart );
			CalculateTax::calculate_cart_totals( $response, $wc_cart, $currency );

			static::calculate_cart_line_item_taxes( $wc_cart );

		} catch ( ApiErrorException $e ) {
			if ( is_ajax() ) {
				ErrorRenderer::add_stripe_wc_notice( $e->getMessage(), 'error' );
			}

			return;
		} catch ( Exception $e ) {
			return;
		}
	}

	/**
	 * Checks whether WooCommerce is installed and active
	 *
	 * @return bool
	 */
	public static function is_woocommerce_activated() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Sets app info for Stripe Tax
	 *
	 * @return void
	 */
	public static function set_app_info() {
		if ( ! Stripe::getAppInfo() ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			if ( file_exists( __DIR__ . '/../stripe-tax-for-woocommerce.php' ) ) {
				$plugin_data = get_plugin_data( __DIR__ . '/../stripe-tax-for-woocommerce.php' );

				Stripe::setAppInfo( $plugin_data['Name'], $plugin_data['Version'] );
			}
		}
	}

	/**
	 * Entry point for adding WordPress actions, filter, registering styles and scripts
	 *
	 * @param bool $force Force flag.
	 *
	 * @return void
	 */
	public static function init( bool $force = false ): void {
		add_action( 'admin_init', array( static::class, 'action_admin_init' ), 5, 0 );
		add_action( 'woocommerce_system_status_report', array( static::class, 'system_status_report' ) );

		add_filter( 'woocommerce_get_settings_pages', array( static::class, 'filter_add_stripe_tax_settings' ), 10, 1 );
		add_filter(
			'plugin_action_links_' . STRIPE_TAX_FOR_WOOCOMMERCE_PLUGIN_BASENAME,
			array(
				static::class,
				'plugin_action_links',
			)
		);
		static::admin_enqueue_styles();

		if ( static::can_init( $force ) ) {
			static::$tax_exemptions = new TaxExemptions();

			static::$hooks_initialized = true;

			static::add_actions();
			static::add_filters();
		}
	}

	/**
	 * Check if the plugin can perform init action.
	 *
	 * @param bool $force Force flag.
	 *
	 * @return bool
	 */
	private static function can_init( bool $force = false ): bool {
		if ( ! empty( static::$hooks_initialized ) && ! $force ) {
			return false;
		}

		if ( ! static::is_woocommerce_activated() ) {
			add_action(
				'admin_notices',
				function () {
					/* translators: 1. URL link. */
					echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Stripe Tax requires WooCommerce to be installed and active. You can download %s here.', 'stripe-tax-for-woocommerce' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
				}
			);

			return false;
		}

		return ! empty( Options::get_live_mode_key() );
	}

	/**
	 * Adds tax exemptions action.
	 *
	 * @param object $instance The instance.
	 */
	protected static function add_action_tax_exemptions( $instance ) {
		add_action( 'show_user_profile', array( $instance, 'custom_user_profile_fields' ) );
		add_action( 'edit_user_profile', array( $instance, 'custom_user_profile_fields' ) );

		add_action( 'personal_options_update', array( $instance, 'save_custom_user_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $instance, 'save_custom_user_profile_fields' ) );
	}

	/**
	 * Filter to alter cart totals.
	 * Used here to set shipping taxes.
	 *
	 * @param \WC_Cart $wc_cart WooCommerce Cart object.
	 */
	public static function calculate_cart_line_item_taxes( $wc_cart ) {
		$currency = strtolower( get_woocommerce_currency() );

		$customer = $wc_cart->get_customer();

		$line_items = CalculateTax::get_line_items_by_cart( $wc_cart );

		$customer_details                        = CalculateTax::get_customer_details_by_order( $customer );
		$customer_details['taxability_override'] = static::$tax_exemptions->get_tax_exeption( get_current_user_id() );

		if ( ! CalculateTax::can_calculate_tax( $customer_details ) ) {
			return;
		}

		$shipping_cost = CalculateTax::get_taxable_shipping_cost_from_cart_or_order_for_api( $wc_cart, $currency );

		$calculate_tax = new CalculateTax(
			Options::get_live_mode_key(),
			$currency,
			$line_items,
			$customer_details,
			$shipping_cost
		);

		$response = $calculate_tax->get_response();

		$new_total_taxes = static::calculate_cart_contents_taxes( $wc_cart, $response );

		$wc_cart->set_cart_contents_taxes( $new_total_taxes );
	}

	/**
	 * Calculates cart item totals.
	 *
	 * @param \WC_Cart  $wc_cart WooCommerce Cart object.
	 * @param \stdClass $response Stripe Tax Api response.
	 */
	public static function calculate_cart_contents_taxes( $wc_cart, $response ) {
		$new_item_tax_rates = array();
		$new_total_taxes    = array();

		foreach ( $wc_cart->cart_contents as $item_key => $line_item ) {
			foreach ( $response->line_items->data as $datum ) {
				if ( $datum->reference === $line_item['data']->get_name() ) {
					foreach ( $datum->tax_breakdown as $tax_breakdown ) {
						if ( ! isset( $tax_breakdown->tax_rate_details ) && ! is_object( $tax_breakdown->tax_rate_details ) ) {
							continue;
						}

						$rate_name       = $tax_breakdown->jurisdiction->display_name . ' ' . $tax_breakdown->tax_rate_details->display_name;
						$rate_percentage = $tax_breakdown->tax_rate_details->percentage_decimal;
						$tax_type        = $tax_breakdown->tax_rate_details->tax_type;
						$rate_key        = 'stripe_tax_for_woocommerce__' . $tax_type . '__' . $rate_percentage . '__' . $rate_name;

						if ( ! array_key_exists( $rate_key, $new_item_tax_rates ) ) {
							$new_item_tax_rates[ $rate_key ] = array(
								'rate'     => (float) $rate_percentage,
								'label'    => $rate_name,
								'shipping' => 'no',
								'compound' => 'no',
							);
							$new_total_taxes[ $rate_key ]    = $tax_breakdown->amount / 100;
						} else {
							$new_item_tax_rates[ $rate_key ]['rate'] += (float) $rate_percentage;
							$new_total_taxes[ $rate_key ]            += $tax_breakdown->amount / 100;
						}
					}
				}

				$subtotal_taxes = $new_total_taxes;
				$subtotal_tax   = array_sum( $subtotal_taxes );

				$wc_cart->cart_contents[ $item_key ]['line_tax_data']     = array( 'subtotal' => wc_remove_number_precision_deep( $subtotal_taxes ) );
				$wc_cart->cart_contents[ $item_key ]['line_subtotal_tax'] = wc_remove_number_precision( $subtotal_tax );
			}
		}

		return $new_total_taxes;
	}

	/**
	 * On REST requests enables error collecting mechanism.
	 */
	public static function detect_error_reporting_collect() {
		static::$error_reporting_collect = true;
	}

	/**
	 * Adds collected errors to the REST response.
	 *
	 * @param WP_REST_Response $response REST response.
	 * @param array            $handler  REST handler.
	 * @param WP_REST_Request  $request  REST request.
	 */
	public static function add_collected_errors_to_response( $response, $handler, $request ) {
		$request_route = $request->get_route();

		if ( substr( $request_route, -6 ) === '/batch' ) {
			return $response;
		}
		if ( ! static::$error_reporting_collect_enabled ) {
			return $response;
		}
		$calculate_tax_error_message = ErrorRenderer::get_error_object( 'calculate_tax_error' )->message;

		if ( ! $calculate_tax_error_message ) {
			return $response;
		}

		$response->data['errors'][] = array(
			'code'    => 'calculate_tax_error',
			'message' => $calculate_tax_error_message,
		);

		return $response;
	}

	/**
	 * Hooks extra necessary sections into the system status report template
	 */
	public static function system_status_report(): void {
		?>
			<table class="wc_status_table widefat">
				<thead>
				<tr>
					<th colspan="5" data-export-label="Stripe">
						<h2>
							<?php esc_html_e( 'Stripe', 'stripe_tax_for_woocommerce' ); ?>
							<?php echo wp_kses_post( wc_help_tip( esc_html__( 'This section shows details of Stripe.', 'stripe_tax_for_woocommerce' ) ) ); ?>
						</h2>
					</th>
				</tr>
				</thead>
				<tbody>
					<tr>
						<td data-export-label="Account ID">
							<?php esc_html_e( 'Account ID', 'stripe_tax_for_woocommerce' ); ?>:
						</td>
						<td class="help">
							<?php echo wp_kses_post( wc_help_tip( esc_html__( 'Stripe Account ID.', 'stripe_tax_for_woocommerce' ) ) ); ?>
						</td>
						<td>
							<?php
							try {
								if ( ! empty( Options::get_live_mode_key() ) ) {
									if ( ! empty( Options::get_option( Options::OPTION_LIVE_MODE_ACCOUNT_ID ) ) ) {
										echo esc_html( Options::get_option( Options::OPTION_LIVE_MODE_ACCOUNT_ID ) );
									} else {
										$stripe_client   = new StripeClient( Options::get_live_mode_key() );
										$account_service = new AccountService( $stripe_client );
										$api_response    = $account_service->retrieve();
										if ( ! isset( $api_response->object ) || 'account' !== $api_response->object ) {
											echo esc_html__( 'Account ID could not be retrieved, please try to reconnect to Stripe.', 'stripe_tax_for_woocommerce' );
										} else {
											Options::update_option( Options::OPTION_LIVE_MODE_ACCOUNT_ID, $api_response->{ 'id' } );
											echo esc_html( Options::get_option( Options::OPTION_LIVE_MODE_ACCOUNT_ID ) );
										}
									}
								} else {
									echo esc_html__( 'Account ID could not be retrieved, please try to reconnect to Stripe.', 'stripe_tax_for_woocommerce' );
								}
							} catch ( \Throwable $e ) {
								echo esc_html__( 'Account ID could not be retrieved, please try to reconnect to Stripe.', 'stripe_tax_for_woocommerce' );
							}
							?>
						</td>
					</tr>
				</tbody>
			</table>
		<?php
	}

	/**
	 * Converts tax rate ids from string to integer.
	 *
	 * @param WP_REST_Response $response REST response.
	 */
	public static function filter_rest_prepare_shop_order_object( $response ) {
		// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
		$data = $response->get_data();

		$counter = 5000000000;

		if ( isset( $data['line_items'] ) ) {
			foreach ( $data['line_items'] as $idx_line => $line_item ) {
				if ( isset( $line_item['taxes'] ) ) {
					$order_id = $data['id'];

					foreach ( $line_item['taxes'] as $idx_tax => $line_item_tax ) {
						++$counter;
						$rate_id = $line_item_tax['id'];

						if ( is_numeric( $rate_id ) || strpos( $rate_id, 'stripe_tax_for_woocommerce__' ) === false ) {
							break 2;
						}

						$new_rate_id = $order_id * 1000 + $counter;

						foreach ( $data['tax_lines'] as $idx_tax_2 => $tax_line ) {
							if ( $tax_line['rate_id'] === $rate_id ) {
								break;
							}
						}

						$data['line_items'][ $idx_line ]['taxes'][ $idx_tax ]['id'] = $new_rate_id;
						$data['tax_lines'][ $idx_tax_2 ]['rate_id']                 = $new_rate_id;
					}
				}
			}
		}

		if ( isset( $data['shipping_lines'] ) ) {
			foreach ( $data['shipping_lines'] as $idx_line => $line_item ) {
				if ( isset( $line_item['taxes'] ) ) {
					$order_id = $data['id'];
					foreach ( $line_item['taxes'] as $idx_tax => $line_item_tax ) {
						++$counter;
						$rate_id = $line_item_tax['id'];
						if ( is_numeric( $rate_id ) || strpos( $rate_id, 'stripe_tax_for_woocommerce__' ) === false ) {
							break 2;
						}

						$new_rate_id = $order_id * 1000 + $counter;

						foreach ( $data['tax_lines'] as $idx_tax_2 => $tax_line ) {
							if ( $tax_line['rate_id'] === $rate_id ) {
								break;
							}
						}

						$data['shipping_lines'][ $idx_line ]['taxes'][ $idx_tax ]['id'] = $new_rate_id;
						$data['tax_lines'][ $idx_tax_2 ]['rate_id']                     = $new_rate_id;
					}
				}
			}
		}

		$response->set_data( $data );

		return $response;
	}
}
