<?php
/**
 * WC_PB_Tracker class
 *
 * @package  WooCommerce Product Bundles
 * @since    6.16.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Bundles Tracker.
 *
 * @class    WC_PB_Tracker
 * @version  7.2.0
 */
class WC_PB_Tracker {

	/**
	 * Property to store reusable query data.
	 *
	 * @var array
	 */
	private static $reusable_data = array();

	/**
	 * Property to store and share tracking data in the class.
	 *
	 * @var array
	 */
	private static $data = array();

	/**
	 * Property to store the starting time of the process.
	 *
	 * @var int
	 */
	private static $start_time = 0;

	/**
	 * Property to store the tracking events.
	 *
	 * @var array
	 */
	private static $tracking_events = array();

	/**
	 * Property to store the HPOS table name.
	 *
	 * @var string
	 */
	private static $hpos_orders_table = '';

	/**
	 * Property to store how often the data will be invalidated.
	 *
	 * @var string
	 */
	private static $invalidation_interval = '-1 week';

	/**
	 * Initialize the tracker.
	 */
	public static function init() {
		if ( 'yes' === get_option( 'woocommerce_allow_tracking', 'no' ) ) {
			add_filter( 'woocommerce_tracker_data', array( __CLASS__, 'add_tracking_data' ), 10 );

			// Async tasks.
			if ( defined( 'WC_CALYPSO_BRIDGE_TRACKER_FREQUENCY' ) ) {
				add_action( 'wc_pb_hourly', array( __CLASS__, 'maybe_calculate_tracking_data' ) );
			} else {
				add_action( 'wc_pb_daily', array( __CLASS__, 'maybe_calculate_tracking_data' ) );
			}
		}

		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'track_bundle_sell_ids' ), 100 );
	}

	/**
	 * Add PB data to the tracked data.
	 *
	 * @param  array $data The tracking data.
	 * @return array  all the tracking data.
	 */
	public static function add_tracking_data( $data ) {
		$data['extensions']['wc_pb'] = self::get_tracking_data();
		return $data;
	}

	/**
	 * Get all tracking data from options.
	 *
	 * @return array PB's tracking data.
	 */
	private static function get_tracking_data() {
		self::read_data();
		self::maybe_initialize_data();

		// if there are no data calculated, it will calculate them and then send the data.

		if ( self::has_pending_calculations() ) {
			return array();
		}

		if ( isset( self::$data['info']['started_time'] ) ) {
			unset( self::$data['info']['started_time'] );
		}

		return self::$data;
	}

	/**
	 * Calculates all tracking-related data for the previous month and year.
	 * Runs independently in a background task.
	 *
	 * @see ::maybe_calculate_tracking_data().
	 */
	private static function calculate_tracking_data() {
		self::set_start_time();
		self::calculate_product_data();
		self::calculate_bundled_item_data();
		self::calculate_order_data();
		self::calculate_integration_data();
	}

	/**
	 * Maybe calculate orders data. Also, handles the caching strategy.
	 *
	 * @return bool Returns true if the data are re-calculated, false otherwise.
	 */
	public static function maybe_calculate_tracking_data() {

		self::read_data();
		self::maybe_initialize_data();

		// Let's check if the array has pending data to calculate.
		if ( self::has_pending_calculations() ) {

			self::calculate_tracking_data();
			self::increase_iterations();
			self::set_option_data();

			return true;
		}

		return false;
	}

	/**
	 * Track bundle sells first date when saving a product (wp-admin / rest api).
	 *
	 * @param  WC_Product $product The product object.
	 */
	public static function track_bundle_sell_ids( $product ) {

		$bundle_sell_ids = WC_PB_BS_Product::get_bundle_sell_ids( $product, 'edit' );

		if ( ! empty( $bundle_sell_ids ) ) {
			$events = get_option( 'woocommerce_pb_tracking_events', array() );

			if ( is_array( $events ) && ! isset( $events['products_bundle_sells_first_create_date'] ) ) {
				$events['products_bundle_sells_first_create_date'] = gmdate( 'Y-m-d H:i:s' );
				update_option( 'woocommerce_pb_tracking_events', $events );
			}
		}
	}

	/**
	 * Calculate product aggregation data.
	 *
	 * @return void
	 */
	private static function calculate_product_data() {

		global $wpdb;

		$data = &self::$data['products'];

		if ( ! isset( $data['products_count'] ) ) {
			// Number of products in catalog.
			$data['products_count'] = (int) $wpdb->get_var(
				"
				SELECT COUNT(*)
				FROM `{$wpdb->posts}`
				WHERE `post_type` = 'product'
					AND `post_status` = 'publish'
		"
			);

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		if ( ! isset( $data['product_bundles_count'] ) ) {
			// Number of bundles in use.
			$data['product_bundles_count'] = self::get_reusable_data( 'product_bundles_count' );

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Creation date of first bundle.
		if ( ! isset( $data['product_bundles_first_create_date'] ) ) {

			// @see maybe_initialize_data() for tracking events default values.
			if ( self::get_reusable_data( 'product_bundles_count' )
				&& null === self::$tracking_events['product_bundles_first_create_date'] ) {

				$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

				// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
				$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				self::$tracking_events['product_bundles_first_create_date'] = $wpdb->get_var(
					$wpdb->prepare(
						"
					SELECT `post_date_gmt`
					FROM `{$wpdb->posts}` AS `posts`
					WHERE `posts`.`ID` IN ( " . $placeholders . ' )
					ORDER BY `post_date_gmt` ASC
					LIMIT 1
				',
						...$product_bundles_ids
					)
				);
				// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

				update_option( 'woocommerce_pb_tracking_events', self::$tracking_events );
			}

			$data['product_bundles_first_create_date'] = self::$tracking_events['product_bundles_first_create_date'];

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundled items in all bundles.
		if ( ! isset( $data['bundled_items_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['bundled_items_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
				WHERE `bundled_items`.`bundle_id` IN ( " . $placeholders . ' )
			',
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			// Average number of bundled items per bundle in bundles.
			$data['bundled_items_per_bundle_average'] = ! empty( $data['product_bundles_count'] )
				? round( $data['bundled_items_count'] / $data['product_bundles_count'], 2 )
				: 0;

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundled items in the largest bundle.
		if ( ! isset( $data['bundled_items_in_largest_bundle_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['bundled_items_in_largest_bundle_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT MAX(`bundled_items_count`)
				FROM (
					SELECT COUNT(*) AS `bundled_items_count`,
						`bundled_items`.`bundle_id`
					FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
					WHERE `bundled_items`.`bundle_id` IN ( " . $placeholders . ' )
					GROUP BY `bundled_items`.`bundle_id`
					) AS `bundled_items_group`;
			',
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of products with bundle-sells.
		if ( ! isset( $data['products_bundle_sells_count'] ) ) {

			$data['products_bundle_sells_count'] = self::get_reusable_data( 'products_bundle_sells_count' );

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Creation date of first bundle-sell.
		if ( ! isset( $data['products_bundle_sells_first_create_date'] ) ) {

			// @see maybe_initialize_data() for tracking events default values.
			if ( self::get_reusable_data( 'products_bundle_sells_count' )
				&& null === self::$tracking_events['products_bundle_sells_first_create_date'] ) {

				self::$tracking_events['products_bundle_sells_first_create_date'] = gmdate( 'Y-m-d H:i:s' );

				update_option( 'woocommerce_pb_tracking_events', self::$tracking_events );
			}

			$data['products_bundle_sells_first_create_date'] = self::$tracking_events['products_bundle_sells_first_create_date'];

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of products with non empty/zero bundle-sell discounts.
		if ( ! isset( $data['products_non_zero_bundle_sell_discounts_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['products_non_zero_bundle_sell_discounts_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` NOT IN ( " . $placeholders . " )
					AND `postmeta`.`meta_key` = '_wc_pb_bundle_sells_discount'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of virtual bundles.
		if ( ! isset( $data['product_bundles_virtual_count'] ) ) {
			$data['product_bundles_virtual_count'] = self::get_reusable_data( 'product_bundles_virtual_count' );

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of assembled bundles.
		if ( ! isset( $data['product_bundles_assembled_count'] ) ) {
			$data['product_bundles_assembled_count'] = self::get_reusable_data( 'product_bundles_assembled_count' );

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of unassembled bundles.
		if ( ! isset( $data['product_bundles_unassembled_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders                = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );
			$product_bundles_virtual_ids = self::get_reusable_data( 'product_bundles_virtual_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders_virtual = implode( ', ', array_fill( 0, count( $product_bundles_virtual_ids ), '%d' ) );

			if ( ! empty( $product_bundles_ids ) && ! empty( $product_bundles_virtual_ids ) ) {
				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$data['product_bundles_unassembled_count'] = (int) $wpdb->get_var(
					$wpdb->prepare(
						"
				SELECT COUNT(*)
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` IN ( " . $placeholders . ' )
					AND `postmeta`.`post_id` NOT IN ( ' . $placeholders_virtual . " )
					AND `postmeta`.`meta_key` = '_virtual'
					AND `postmeta`.`meta_value` = 'yes'
			",
						...$product_bundles_ids,
						...$product_bundles_virtual_ids
					)
				);
				// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			} else {
				$data['product_bundles_unassembled_count'] = 0;
			}

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of assembled bundles with preserved assembled weight.
		if ( ! isset( $data['product_bundles_assembled_preserved_weight_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_assembled_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['product_bundles_assembled_preserved_weight_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` IN ( " . $placeholders . " )
					AND `postmeta`.`meta_key` = '_wc_pb_aggregate_weight'
					AND `postmeta`.`meta_value` = 'yes'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of assembled bundles with ignored assembled weight.
		if ( ! isset( $data['product_bundles_assembled_ignored_weight_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_assembled_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['product_bundles_assembled_ignored_weight_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` IN ( " . $placeholders . " )
					AND `postmeta`.`meta_key` = '_wc_pb_aggregate_weight'
					AND `postmeta`.`meta_value` = 'no'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundles with the Standard layout.
		if ( ! isset( $data['product_bundles_layout_standard_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['product_bundles_layout_standard_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` IN ( " . $placeholders . " )
					AND `postmeta`.`meta_key` = '_wc_pb_layout_style'
					AND `postmeta`.`meta_value` = 'default'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundles with the Tabular layout.
		if ( ! isset( $data['product_bundles_layout_tabular_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['product_bundles_layout_tabular_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` IN ( " . $placeholders . " )
				AND `postmeta`.`meta_key` = '_wc_pb_layout_style'
				AND `postmeta`.`meta_value` = 'tabular'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundles with the Grid layout.
		if ( ! isset( $data['product_bundles_layout_grid_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['product_bundles_layout_grid_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` IN ( " . $placeholders . " )
					AND `postmeta`.`meta_key` = '_wc_pb_layout_style'
					AND `postmeta`.`meta_value` = 'grid'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundles having a Default form location.
		if ( ! isset( $data['product_bundles_form_location_default_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['product_bundles_form_location_default_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` IN ( " . $placeholders . " )
					AND `postmeta`.`meta_key` = '_wc_pb_add_to_cart_form_location'
					AND `postmeta`.`meta_value` = 'default'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundles having a Before Tabs form location.
		if ( ! isset( $data['product_bundles_form_location_before_tabs_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['product_bundles_form_location_before_tabs_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` IN ( " . $placeholders . " )
					AND `postmeta`.`meta_key` = '_wc_pb_add_to_cart_form_location'
					AND `postmeta`.`meta_value` = 'after_summary'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundles with a Grouped item grouping.
		if ( ! isset( $data['product_bundles_item_grouping_grouped_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['product_bundles_item_grouping_grouped_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` IN ( " . $placeholders . " )
					AND `postmeta`.`meta_key` = '_wc_pb_group_mode'
					AND `postmeta`.`meta_value` = 'parent'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundles with a Flat item grouping.
		if ( ! isset( $data['product_bundles_item_grouping_flat_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['product_bundles_item_grouping_flat_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` IN ( " . $placeholders . " )
					AND `postmeta`.`meta_key` = '_wc_pb_group_mode'
					AND `postmeta`.`meta_value` = 'noindent'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundles with a None item grouping.
		if ( ! isset( $data['product_bundles_item_grouping_none_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['product_bundles_item_grouping_none_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` IN ( " . $placeholders . " )
					AND `postmeta`.`meta_key` = '_wc_pb_group_mode'
					AND `postmeta`.`meta_value` = 'none'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundles with a positive Min or Max Bundle Size.
		if ( ! isset( $data['product_bundles_positive_min_max_bundle_size_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['product_bundles_positive_min_max_bundle_size_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(DISTINCT `postmeta`.`post_id`)
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` IN ( " . $placeholders . " )
					AND(
					    (`postmeta`.`meta_key` = '_wcpb_min_qty_limit'
						AND `postmeta`.`meta_value` <> '')
						OR
					    (`postmeta`.`meta_key` = '_wcpb_max_qty_limit'
						AND `postmeta`.`meta_value` <> '')
					);
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundles that are Editable in Cart.
		if ( ! isset( $data['product_bundles_editable_in_cart_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['product_bundles_editable_in_cart_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` IN ( " . $placeholders . " )
					AND `postmeta`.`meta_key` = '_wc_pb_edit_in_cart'
					AND `postmeta`.`meta_value` = 'yes'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				// If we don't unset now, it would exit and would need
				// an additional run just to remove the pending flag.
				unset( $data['pending'] );
				return;
			}
		}

		unset( $data['pending'] );
	}

	/**
	 * Calculate bundled item aggregation data.
	 *
	 * @return void
	 */
	private static function calculate_bundled_item_data() {

		global $wpdb;

		$data = &self::$data['bundled_items'];

		// Number of bundled items that are optional.
		if ( ! isset( $data['bundled_items_optional_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['bundled_items_optional_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
					INNER JOIN `{$wpdb->prefix}woocommerce_bundled_itemmeta` AS `bundled_itemmeta` ON `bundled_items`.`bundled_item_id` = `bundled_itemmeta`.`bundled_item_id`
				WHERE `bundled_items`.`bundle_id` IN ( " . $placeholders . " )
					AND `bundled_itemmeta`.`meta_key` = 'optional'
					AND `bundled_itemmeta`.`meta_value` = 'yes'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundled items that have a Max Qty !== Min Qty.
		if ( ! isset( $data['bundled_items_quantity_max_neq_min_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['bundled_items_quantity_max_neq_min_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
					INNER JOIN `{$wpdb->prefix}woocommerce_bundled_itemmeta` AS `bundled_itemmeta` ON `bundled_items`.`bundled_item_id` = `bundled_itemmeta`.`bundled_item_id`
					INNER JOIN `{$wpdb->prefix}woocommerce_bundled_itemmeta` AS `bundled_itemmeta_2` ON `bundled_items`.`bundled_item_id` = `bundled_itemmeta_2`.`bundled_item_id`
				WHERE `bundled_items`.`bundle_id` IN ( " . $placeholders . " )
					AND `bundled_itemmeta`.`meta_key` = 'quantity_max'
					AND `bundled_itemmeta_2`.`meta_key` = 'quantity_min'
					AND `bundled_itemmeta`.`meta_value` <> `bundled_itemmeta_2`.`meta_value`;
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundled items that have a Default Qty !== Min Qty.
		if ( ! isset( $data['bundled_items_quantity_default_neq_min_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['bundled_items_quantity_default_neq_min_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
					INNER JOIN `{$wpdb->prefix}woocommerce_bundled_itemmeta` AS `bundled_itemmeta` ON `bundled_items`.`bundled_item_id` = `bundled_itemmeta`.`bundled_item_id`
					INNER JOIN `{$wpdb->prefix}woocommerce_bundled_itemmeta` AS `bundled_itemmeta_2` ON `bundled_items`.`bundled_item_id` = `bundled_itemmeta_2`.`bundled_item_id`
				WHERE `bundled_items`.`bundle_id` IN ( " . $placeholders . " )
					AND `bundled_itemmeta`.`meta_key` = 'quantity_default'
					AND `bundled_itemmeta_2`.`meta_key` = 'quantity_min'
					AND `bundled_itemmeta`.`meta_value` <> `bundled_itemmeta_2`.`meta_value`;
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundled items that are Shipped Individually.
		if ( ! isset( $data['bundled_items_shipped_individually_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_assembled_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['bundled_items_shipped_individually_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
					INNER JOIN `{$wpdb->prefix}woocommerce_bundled_itemmeta` AS `bundled_itemmeta` ON `bundled_items`.`bundled_item_id` = `bundled_itemmeta`.`bundled_item_id`
				WHERE `bundled_items`.`bundle_id` IN ( " . $placeholders . " )
					AND `bundled_itemmeta`.`meta_key` = 'shipped_individually'
					AND `bundled_itemmeta`.`meta_value` = 'yes'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundled items that are Priced Individually.
		if ( ! isset( $data['bundled_items_priced_individually_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['bundled_items_priced_individually_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
					INNER JOIN `{$wpdb->prefix}woocommerce_bundled_itemmeta` AS `bundled_itemmeta` ON `bundled_items`.`bundled_item_id` = `bundled_itemmeta`.`bundled_item_id`
				WHERE `bundled_items`.`bundle_id` IN ( " . $placeholders . " )
					AND `bundled_itemmeta`.`meta_key` = 'priced_individually'
					AND `bundled_itemmeta`.`meta_value` = 'yes'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundled items that are hidden in product templates.
		if ( ! isset( $data['bundled_items_visibility_single_product_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['bundled_items_visibility_single_product_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
					INNER JOIN `{$wpdb->prefix}woocommerce_bundled_itemmeta` AS `bundled_itemmeta` ON `bundled_items`.`bundled_item_id` = `bundled_itemmeta`.`bundled_item_id`
				WHERE `bundled_items`.`bundle_id` IN ( " . $placeholders . " )
					AND `bundled_itemmeta`.`meta_key` = 'single_product_visibility'
					AND `bundled_itemmeta`.`meta_value` = 'hidden'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundled items that are hidden in cart templates.
		if ( ! isset( $data['bundled_items_visibility_cart_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['bundled_items_visibility_cart_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
					INNER JOIN `{$wpdb->prefix}woocommerce_bundled_itemmeta` AS `bundled_itemmeta` ON `bundled_items`.`bundled_item_id` = `bundled_itemmeta`.`bundled_item_id`
				WHERE `bundled_items`.`bundle_id` IN ( " . $placeholders . " )
					AND `bundled_itemmeta`.`meta_key` = 'cart_visibility'
					AND `bundled_itemmeta`.`meta_value` = 'hidden'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundled items that are hidden in order templates.
		if ( ! isset( $data['bundled_items_visibility_order_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['bundled_items_visibility_order_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
					INNER JOIN `{$wpdb->prefix}woocommerce_bundled_itemmeta` AS `bundled_itemmeta` ON `bundled_items`.`bundled_item_id` = `bundled_itemmeta`.`bundled_item_id`
				WHERE `bundled_items`.`bundle_id` IN ( " . $placeholders . " )
					AND `bundled_itemmeta`.`meta_key` = 'order_visibility'
					AND `bundled_itemmeta`.`meta_value` = 'hidden'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of priced individually bundled items whose prices are hidden in product templates.
		if ( ! isset( $data['bundled_items_price_visibility_single_product_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['bundled_items_price_visibility_single_product_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
					INNER JOIN `{$wpdb->prefix}woocommerce_bundled_itemmeta` AS `bundled_itemmeta` ON `bundled_items`.`bundled_item_id` = `bundled_itemmeta`.`bundled_item_id`
				WHERE `bundled_items`.`bundle_id` IN ( " . $placeholders . " )
					AND `bundled_itemmeta`.`meta_key` = 'single_product_price_visibility'
					AND `bundled_itemmeta`.`meta_value` = 'hidden'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of priced individually bundled items whose prices are hidden in cart templates.
		if ( ! isset( $data['bundled_items_price_visibility_cart_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['bundled_items_price_visibility_cart_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
					INNER JOIN `{$wpdb->prefix}woocommerce_bundled_itemmeta` AS `bundled_itemmeta` ON `bundled_items`.`bundled_item_id` = `bundled_itemmeta`.`bundled_item_id`
				WHERE `bundled_items`.`bundle_id` IN ( " . $placeholders . " )
					AND `bundled_itemmeta`.`meta_key` = 'cart_price_visibility'
					AND `bundled_itemmeta`.`meta_value` = 'hidden'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of priced individually bundled items whose prices are hidden in order templates.
		if ( ! isset( $data['bundled_items_price_visibility_order_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['bundled_items_price_visibility_order_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
					INNER JOIN `{$wpdb->prefix}woocommerce_bundled_itemmeta` AS `bundled_itemmeta` ON `bundled_items`.`bundled_item_id` = `bundled_itemmeta`.`bundled_item_id`
				WHERE `bundled_items`.`bundle_id` IN ( " . $placeholders . " )
					AND `bundled_itemmeta`.`meta_key` = 'order_price_visibility'
					AND `bundled_itemmeta`.`meta_value` = 'hidden'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundled items whose title is overridden.
		if ( ! isset( $data['bundled_items_override_title_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['bundled_items_override_title_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
					INNER JOIN `{$wpdb->prefix}woocommerce_bundled_itemmeta` AS `bundled_itemmeta` ON `bundled_items`.`bundled_item_id` = `bundled_itemmeta`.`bundled_item_id`
				WHERE `bundled_items`.`bundle_id` IN ( " . $placeholders . " )
					AND `bundled_itemmeta`.`meta_key` = 'override_title'
					AND `bundled_itemmeta`.`meta_value` = 'yes'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundled items whose short description is overridden.
		if ( ! isset( $data['bundled_items_override_description_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['bundled_items_override_description_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
					INNER JOIN `{$wpdb->prefix}woocommerce_bundled_itemmeta` AS `bundled_itemmeta` ON `bundled_items`.`bundled_item_id` = `bundled_itemmeta`.`bundled_item_id`
				WHERE `bundled_items`.`bundle_id` IN ( " . $placeholders . " )
					AND `bundled_itemmeta`.`meta_key` = 'override_description'
					AND `bundled_itemmeta`.`meta_value` = 'yes'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of bundled items whose product thumbnail is hidden.
		if ( ! isset( $data['bundled_items_hide_thumbnail_count'] ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$data['bundled_items_hide_thumbnail_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
					INNER JOIN `{$wpdb->prefix}woocommerce_bundled_itemmeta` AS `bundled_itemmeta` ON `bundled_items`.`bundled_item_id` = `bundled_itemmeta`.`bundled_item_id`
				WHERE `bundled_items`.`bundle_id` IN ( " . $placeholders . " )
					AND `bundled_itemmeta`.`meta_key` = 'hide_thumbnail'
					AND `bundled_itemmeta`.`meta_value` = 'yes'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			if ( self::time_or_memory_exceeded() ) {
				// If we don't unset now, it would exit and would need
				// an additional run just to remove the pending flag.
				unset( $data['pending'] );
				return;
			}
		}

		unset( $data['pending'] );
	}

	/**
	 * Calculate order data.
	 *
	 * @since 6.18.3
	 * @return void
	 */
	private static function calculate_order_data() {
		global $wpdb;

		$hpos_orders_table = self::$hpos_orders_table;

		$data = &self::$data['orders'];

		// Number of orders containing bundles.
		if ( ! isset( $data['product_bundles_count'] ) ) {
			$data['product_bundles_count'] = (int) $wpdb->get_var(
				"
				SELECT COUNT(DISTINCT `pb_lookup`.`order_id`)
				FROM `{$wpdb->prefix}wc_order_bundle_lookup` AS `pb_lookup`
			"
			);

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Number of orders containing bundle-sells.
		if ( ! isset( $data['bundle_sells_count'] ) ) {

			$data['bundle_sells_count'] = (int) $wpdb->get_var(
				"
				SELECT
					COUNT(DISTINCT orders.order_id)
				FROM
					`{$wpdb->prefix}wc_order_product_lookup` AS `orders`
					INNER JOIN `{$wpdb->prefix}woocommerce_order_items` AS `order_items` ON `orders`.`order_item_id` = `order_items`.`order_item_id`
						AND `orders`.`order_id` = `order_items`.`order_id`
					INNER JOIN `{$wpdb->prefix}woocommerce_order_itemmeta` AS `order_itemmeta` ON `order_itemmeta`.`order_item_id` = `order_items`.`order_item_id`
				WHERE
					`order_itemmeta`.`meta_key` = '_bundle_sell_of'
			"
			);

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Revenue from bundles over time.
		if ( ! isset( $data['product_bundles_revenue'] ) ) {
			$data['product_bundles_revenue'] = (float) $wpdb->get_var(
				"
				SELECT SUM(`pb_lookup`.`product_gross_revenue`)
				FROM `{$wpdb->prefix}wc_order_bundle_lookup` AS `pb_lookup`
			"
			);

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Revenue from bundle-sells over time.
		if ( ! isset( $data['bundle_sells_revenue'] ) ) {
			$data['bundle_sells_revenue'] = (float) $wpdb->get_var(
				"
				SELECT
					SUM(`orders`.`product_gross_revenue`)
				FROM
					`{$wpdb->prefix}wc_order_product_lookup` AS `orders`
					INNER JOIN `{$wpdb->prefix}woocommerce_order_items` AS `order_items` ON `orders`.`order_item_id` = `order_items`.`order_item_id`
						AND `orders`.`order_id` = `order_items`.`order_id`
					INNER JOIN `{$wpdb->prefix}woocommerce_order_itemmeta` AS `order_itemmeta` ON `order_itemmeta`.`order_item_id` = `order_items`.`order_item_id`
				WHERE
					`order_itemmeta`.`meta_key` = '_bundle_sell_of'
			"
			);

			if ( self::time_or_memory_exceeded() ) {
				return;
			}
		}

		// Multi-currency data.
		if ( ! isset( $data['in_multiple_currencies'] ) ) {

			if ( WC_PB_Core_Compatibility::is_hpos_enabled() ) {
				$orders_currencies_count = (int) $wpdb->get_var(
					$wpdb->prepare(
						'
					SELECT COUNT( DISTINCT( `currency` ) )
					FROM %i AS `orders`
				',
						$hpos_orders_table
					)
				);
			} else {
				$orders_currencies_count = (int) $wpdb->get_var(
					"
					SELECT COUNT( DISTINCT( `meta_value` ) )
					FROM `{$wpdb->postmeta}` AS `orders_meta`
					WHERE `orders_meta`.`meta_key` = '_order_currency'
				"
				);
			}

			$data['in_multiple_currencies'] = ( $orders_currencies_count > 1 ) ? true : false;

			if ( self::time_or_memory_exceeded() ) {
				// If we don't unset now, it would exit and would need
				// an additional run just to remove the pending flag.
				unset( $data['pending'] );
				return;
			}
		}

		unset( $data['pending'] );
	}

	/**
	 * Calculates integration data.
	 *
	 * @return void
	 */
	private static function calculate_integration_data() {

		global $wpdb;

		$data = &self::$data['integrations'];

		if ( isset( $data['pending'] ) && 1 === count( $data ) ) {
			$integrations = array(
				'all_products_for_subscriptions' => array( 'enabled' => 'no' ),
				'product_addons'                 => array( 'enabled' => 'no' ),
				'blocks'                         => array( 'enabled' => 'no' ),
				'bulk_discounts'                 => array( 'enabled' => 'no' ),
				'cost_of_goods'                  => array( 'enabled' => 'no' ),
				'composite_products'             => array( 'enabled' => 'no' ),
				'elementor'                      => array( 'enabled' => 'no' ),
				'divi'                           => array( 'enabled' => 'no' ),
				'flatsome'                       => array( 'enabled' => 'no' ),
				'give_products'                  => array( 'enabled' => 'no' ),
				'memberships'                    => array( 'enabled' => 'no' ),
				'min_max_quantities'             => array( 'enabled' => 'no' ),
				'name_your_price'                => array( 'enabled' => 'no' ),
				'one_page_checkout'              => array( 'enabled' => 'no' ),
				'pip'                            => array( 'enabled' => 'no' ),
				'points_rewards_products'        => array( 'enabled' => 'no' ),
				'pre_orders'                     => array( 'enabled' => 'no' ),
				'ppec'                           => array( 'enabled' => 'no' ),
				'quickview'                      => array( 'enabled' => 'no' ),
				'storefront'                     => array( 'enabled' => 'no' ),
				'shipstation'                    => array( 'enabled' => 'no' ),
				'shipwire'                       => array( 'enabled' => 'no' ),
				'stripe'                         => array( 'enabled' => 'no' ),
				'subscriptions'                  => array( 'enabled' => 'no' ),
				'wcpay'                          => array( 'enabled' => 'no' ),
				'wc_services'                    => array( 'enabled' => 'no' ),
				'wishlists'                      => array( 'enabled' => 'no' ),
			);

			foreach ( $integrations as $integration_key => $is_integration_enabled ) {
				$integrations[ $integration_key ]['enabled'] = WC_PB()->compatibility->is_module_loaded( $integration_key ) ? 'yes' : 'no';
			}

			if ( class_exists( 'WCS_ATT' ) ) {
				$integrations['all_products_for_subscriptions']['enabled'] = 'yes';
			}

			if ( class_exists( 'WC_PB_Bulk_Discounts' ) ) {
				$integrations['bulk_discounts']['enabled'] = 'yes';
			}

			if ( class_exists( 'WC_Composite_Products' ) ) {
				$integrations['composite_products']['enabled'] = 'yes';
			}

			$data = array_merge( $data, $integrations );
		}

		// Number of bundles with subscription plans.
		if ( ! isset( $data['all_products_for_subscriptions']['product_bundles_with_subscription_plans_count'] ) ) {

			if ( 'yes' === $data['all_products_for_subscriptions']['enabled'] ) {

				$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

				// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
				$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$data['all_products_for_subscriptions']['product_bundles_with_subscription_plans_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
					$wpdb->prepare(
						"
					SELECT COUNT(*)
					FROM `{$wpdb->postmeta}` AS `postmeta`
					WHERE `postmeta`.`post_id` IN ( " . $placeholders . " )
						AND `postmeta`.`meta_key` = '_wcsatt_schemes'
				",
						...$product_bundles_ids
					)
				);
				// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

				if ( self::time_or_memory_exceeded() ) {
					return;
				}
			} else {
				$data['all_products_for_subscriptions']['product_bundles_with_subscription_plans_count'] = 0;
			}
		}

		// Number of bundled items that are subscription-type products.
		if ( ! isset( $data['subscriptions']['bundled_items_subscription_type_count'] ) ) {
			if ( 'yes' === $data['subscriptions']['enabled'] ) {

				$args = array(
					'status' => 'publish',
					'type'   => array( 'subscription', 'variable-subscription' ),
					'limit'  => -1,
					'return' => 'ids',
				);

				$subscription_products_query = new WC_Product_Query( $args );
				$subscription_products       = $subscription_products_query->get_products();

				$subscription_products_array = is_array( $subscription_products ) ? $subscription_products : array();
				$subscription_products_count = count( $subscription_products_array );
				$subscription_products_ids   = array_map( 'absint', $subscription_products_array );

				// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
				$placeholders = implode( ', ', array_fill( 0, $subscription_products_count, '%d' ) );

				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$data['subscriptions']['bundled_items_subscription_type_count'] = empty( $subscription_products_ids ) ? 0 : (int) $wpdb->get_var(
					$wpdb->prepare(
						"
					SELECT COUNT(*)
					FROM `{$wpdb->prefix}woocommerce_bundled_items` AS `bundled_items`
					WHERE `bundled_items`.`product_id` IN ( " . $placeholders . ' )
				',
						...$subscription_products_ids
					)
				);
				// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

				if ( self::time_or_memory_exceeded() ) {
					return;
				}
			} else {
				$data['subscriptions']['bundled_items_subscription_type_count'] = 0;
			}
		}

		// Number of bundles with bulk discounts.
		if ( ! isset( $data['bulk_discounts']['product_bundles_with_bulk_discounts_count'] ) ) {
			if ( 'yes' === $data['bulk_discounts']['enabled'] ) {

				$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

				// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
				$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$data['bulk_discounts']['product_bundles_with_bulk_discounts_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
					$wpdb->prepare(
						"
					SELECT COUNT(*)
					FROM `{$wpdb->postmeta}` AS `postmeta`
					WHERE `postmeta`.`post_id` IN ( " . $placeholders . " )
						AND `postmeta`.`meta_key` = '_wc_pb_quantity_discount_data'
				",
						...$product_bundles_ids
					)
				);
				// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

				if ( self::time_or_memory_exceeded() ) {
					// If we don't unset now, it would exit and would need
					// an additional run just to remove the pending flag.
					unset( $data['pending'] );
					return;
				}
			} else {
				$data['bulk_discounts']['product_bundles_with_bulk_discounts_count'] = 0;
			}
		}

		unset( $data['pending'] );
	}

	/**
	 * Get any reusable data, without re-querying the DB.
	 *
	 * @throws Exception If an invalid key is passed.
	 * @param  string $key  Reusable data key.
	 * @return mixed
	 */
	private static function get_reusable_data( $key = '' ) {

		global $wpdb;

		$valid_keys = array(
			'product_bundles_array',
			'product_bundles_count',
			'product_bundles_ids',
			'product_bundles_assembled_array',
			'product_bundles_assembled_count',
			'product_bundles_assembled_ids',
			'product_bundles_virtual_array',
			'product_bundles_virtual_count',
			'product_bundles_virtual_ids',
			'products_bundle_sells_count',
		);

		if ( ! in_array( $key, $valid_keys, true ) ) {
			// translators: %s: key.
			$notice = sprintf( __( 'Invalid key &quot;%1$s&quot; passed to get_reusable_data.', 'woocommerce-product-bundles' ), $key );
			throw new Exception( esc_html( $notice ) );
		}

		// Check if the specific data key is already calculated and bail out early.
		if ( isset( self::$reusable_data[ $key ] ) ) {
			return self::$reusable_data[ $key ];
		}

		if ( in_array( $key, array( 'product_bundles_array', 'product_bundles_count', 'product_bundles_ids' ), true ) ) {
			$bundled_args = array(
				'status' => 'publish',
				'type'   => 'bundle',
				'limit'  => -1,
				'return' => 'ids',
			);

			$bundled_products_query = new WC_Product_Query( $bundled_args );
			$bundled_products       = $bundled_products_query->get_products();

			self::$reusable_data['product_bundles_array'] = is_array( $bundled_products ) ? $bundled_products : array();
			self::$reusable_data['product_bundles_count'] = count( self::$reusable_data['product_bundles_array'] );
			self::$reusable_data['product_bundles_ids']   = self::$reusable_data['product_bundles_count']
				? array_map( 'absint', self::$reusable_data['product_bundles_array'] )
				: array();

		} elseif ( in_array( $key, array( 'product_bundles_assembled_array', 'product_bundles_assembled_count', 'product_bundles_assembled_ids' ), true ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			self::$reusable_data['product_bundles_assembled_array'] = empty( $product_bundles_ids ) ? array() : $wpdb->get_col(
				$wpdb->prepare(
					"
				SELECT `post_id`
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` IN ( " . $placeholders . " )
					AND `postmeta`.`meta_key` = '_virtual'
					AND `postmeta`.`meta_value` = 'no'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			self::$reusable_data['product_bundles_assembled_count'] = count( self::$reusable_data['product_bundles_assembled_array'] );
			self::$reusable_data['product_bundles_assembled_ids']   = self::$reusable_data['product_bundles_assembled_count']
				? array_map( 'absint', self::$reusable_data['product_bundles_assembled_array'] )
				: array();

		} elseif ( in_array( $key, array( 'product_bundles_virtual_array', 'product_bundles_virtual_count', 'product_bundles_virtual_ids' ), true ) ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			self::$reusable_data['product_bundles_virtual_array'] = empty( $product_bundles_ids ) ? array() : $wpdb->get_col(
				$wpdb->prepare(
					"
				SELECT `post_id`
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` IN ( " . $placeholders . " )
					AND `postmeta`.`meta_key` = '_wc_pb_virtual_bundle'
					AND `postmeta`.`meta_value` = 'yes'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			self::$reusable_data['product_bundles_virtual_count'] = count( self::$reusable_data['product_bundles_virtual_array'] );
			self::$reusable_data['product_bundles_virtual_ids']   = self::$reusable_data['product_bundles_virtual_count']
				? array_map( 'absint', self::$reusable_data['product_bundles_virtual_array'] )
				: array();

		} elseif ( 'products_bundle_sells_count' === $key ) {

			$product_bundles_ids = self::get_reusable_data( 'product_bundles_ids' );

			// This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
			$placeholders = implode( ', ', array_fill( 0, count( $product_bundles_ids ), '%d' ) );

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			self::$reusable_data['products_bundle_sells_count'] = empty( $product_bundles_ids ) ? 0 : (int) $wpdb->get_var(
				$wpdb->prepare(
					"
				SELECT COUNT(*)
				FROM `{$wpdb->postmeta}` AS `postmeta`
				WHERE `postmeta`.`post_id` NOT IN ( " . $placeholders . " )
					AND `postmeta`.`meta_key` = '_wc_pb_bundle_sell_ids'
			",
					...$product_bundles_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		}

		return self::$reusable_data[ $key ];
	}

	/**
	 * Check if all the main aggregations have pending data.
	 *
	 * @return bool Pending status.
	 */
	private static function has_pending_calculations() {

		if (
			! isset( self::$data['products']['pending'] )
			&& ! isset( self::$data['bundled_items']['pending'] )
			&& ! isset( self::$data['orders']['pending'] )
			&& ! isset( self::$data['integrations']['pending'] )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Check if execution time is high or if available memory is almost consumed.
	 *
	 * @return bool Returns true if we're about to consume our available resources.
	 */
	private static function time_or_memory_exceeded() {
		return self::time_exceeded() || self::memory_exceeded();
	}

	/**
	 * Initialize data if they are empty month/year has changed.
	 *
	 * @return void
	 */
	private static function maybe_initialize_data() {

		// Default interval is -1 week.
		if ( defined( 'WC_CALYPSO_BRIDGE_TRACKER_FREQUENCY' ) ) {
			self::$invalidation_interval = '-1 day';
		}

		if (
			empty( self::$data )
			|| ! isset( self::$data['info']['started_time'] )
			|| self::$data['info']['started_time'] <= strtotime( self::$invalidation_interval )
		) {
			self::$data = array(
				'products'      => array( 'pending' => true ),
				'bundled_items' => array( 'pending' => true ),
				'orders'        => array( 'pending' => true ),
				'integrations'  => array( 'pending' => true ),
				'info'          => array(
					'iterations'   => 0,
					'started_time' => time(),
				),
			);
		}

		self::$tracking_events = get_option( 'woocommerce_pb_tracking_events', array() );
		$defaults              = array(
			'product_bundles_first_create_date'       => null,
			'products_bundle_sells_first_create_date' => null,
		);
		self::$tracking_events = wp_parse_args( self::$tracking_events, $defaults );

		// Convert timestamps to dates. Needed to keep previous data.
		foreach ( self::$tracking_events as $key => $value ) {
			if ( is_int( $value ) ) {
				self::$tracking_events[ $key ] = gmdate( 'Y-m-d H:i:s', $value );
			}
		}

		if ( WC_PB_Core_Compatibility::is_hpos_enabled() ) {
			self::$hpos_orders_table = Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore::get_orders_table_name();
		}
	}

	/**
	 * Time exceeded.
	 *
	 * Ensures the batch never exceeds a sensible time limit.
	 * A timeout limit of 30s is common on shared hosting.
	 *
	 * @return bool
	 */
	private static function time_exceeded() {
		$finish = self::$start_time + 20; // 20 seconds
		return time() >= $finish;
	}

	/**
	 * Memory exceeded
	 *
	 * Ensures the batch process never exceeds 90%
	 * of the maximum WordPress memory.
	 *
	 * @return bool
	 */
	private static function memory_exceeded() {
		$memory_limit   = self::get_memory_limit() * 0.8; // 80% of max memory
		$current_memory = memory_get_usage( true );
		return $current_memory >= $memory_limit;
	}

	/**
	 * Get memory limit.
	 *
	 * @return int
	 */
	private static function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Sensible default.
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || -1 === intval( $memory_limit ) ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32000M';
		}

		return wp_convert_hr_to_bytes( $memory_limit );
	}

	/**
	 * Increase iterations.
	 *
	 * @return void
	 */
	private static function increase_iterations() {
		if ( isset( self::$data['info'] ) && isset( self::$data['info']['iterations'] ) ) {
			self::$data['info']['iterations'] += 1;
		}
	}

	/**
	 * Set starting time.
	 *
	 * @return void
	 */
	private static function set_start_time() {
		self::$start_time = time();
	}

	/**
	 * Set data from option.
	 *
	 * @return void
	 */
	private static function read_data() {
		self::$data = get_option( 'woocommerce_pb_tracking_data' );
	}

	/**
	 * Set option with data.
	 *
	 * @return void
	 */
	private static function set_option_data() {
		update_option( 'woocommerce_pb_tracking_data', self::$data );
	}
}

WC_PB_Tracker::init();
