<?php
/**
 * WC_PB_Admin class
 *
 * @package  WooCommerce Product Bundles
 * @since    5.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Report_Stock' ) ) {
	require_once WC_ABSPATH . 'includes/admin/reports/class-wc-report-stock.php';
}

/**
 * WC_PB_Report_Insufficient_Stock class.
 *
 * Handles reporting of bundles with an "Insufficient stock" status.
 *
 * @version  6.21.0
 */
class WC_PB_Report_Insufficient_Stock extends WC_Report_Stock {

	/**
	 * Bundle IDs sorted by title.
	 *
	 * @var array
	 */
	private $ordered_bundle_ids = array();

	/**
	 * No items found text.
	 */
	public function no_items() {
		esc_html_e( 'No products found with insufficient stock.', 'woocommerce-product-bundles' );
	}

	/**
	 * Get bundles matching "Insufficient stock" stock status criteria.
	 *
	 * @param  int $current_page Current page.
	 * @param  int $per_page    Items per page.
	 */
	public function get_items( $current_page, $per_page ) {

		global $wpdb;

		$this->max_items = 0;
		$this->items     = array();

		/*
		 * First, sync any bundled items without stock meta.
		 */
		if ( ! defined( 'WC_PB_DEBUG_STOCK_PARENT_SYNC' ) && ! defined( 'WC_PB_DEBUG_STOCK_SYNC' ) ) {

			$data_store = WC_Data_Store::load( 'product-bundle' );
			$sync_ids   = $data_store->get_bundled_items_stock_sync_status_ids( 'unsynced' );

		} elseif ( ! defined( 'WC_PB_DEBUG_STOCK_SYNC' ) ) {

			$sync_ids = WC_PB_DB::query_bundled_items(
				array(
					'return'     => 'id=>bundle_id',
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => 'stock_status',
							'compare' => 'NOT EXISTS',
						),
					),
				)
			);

		} else {

			$sync_ids = WC_PB_DB::query_bundled_items(
				array(
					'return' => 'id=>bundle_id',
				)
			);
		}

		if ( ! empty( $sync_ids ) ) {
			foreach ( $sync_ids as $id ) {
				$product = wc_get_product( $id );
				if ( $product && $product->is_type( 'bundle' ) ) {
					$product->sync_stock();
				}
			}
		}

		/*
		 * Then, get all bundled items with insufficient stock.
		 */
		$insufficient_stock_results = WC_PB_DB::query_bundled_items(
			array(
				'return'     => 'all',
				'bundle_id'  => ! empty( $_GET['bundle_id'] ) ? absint( $_GET['bundle_id'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'order_by'   => array(
					'bundle_id'  => 'ASC',
					'menu_order' => 'ASC',
				),
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'stock_status',
						'value'   => 'out_of_stock',
						'compare' => '=',
					),
				),
			)
		);

		if ( ! empty( $insufficient_stock_results ) ) {

			// Order results by bundle title.

			$insufficient_stock_bundle_ids = array_unique( wp_list_pluck( $insufficient_stock_results, 'bundle_id' ) );

			$this->ordered_bundle_ids = get_posts(
				array(
					'post_type'   => 'product',
					'post_status' => 'any',
					'orderby'     => 'title',
					'order'       => 'ASC',
					'post__in'    => $insufficient_stock_bundle_ids,
					'fields'      => 'ids',
					'numberposts' => -1,
				)
			);

			$insufficient_stock_results = array_filter( $insufficient_stock_results, array( $this, 'clean_missing_bundles' ) );

			uasort( $insufficient_stock_results, array( $this, 'order_by_bundle_title' ) );

			$insufficient_stock_results_in_page = array_slice( $insufficient_stock_results, ( $current_page - 1 ) * $per_page, $per_page );

			// Generate results data.

			foreach ( $insufficient_stock_results_in_page as $insufficient_stock_result_in_page ) {

				$bundled_item = wc_pb_get_bundled_item( $insufficient_stock_result_in_page['bundled_item_id'] );

				if ( ! $bundled_item ) {
					continue;
				}

				$item = new stdClass();

				$item->id           = $insufficient_stock_result_in_page['product_id'];
				$item->parent       = $insufficient_stock_result_in_page['bundle_id'];
				$item->bundled_item = $bundled_item;
				$this->items[]      = $item;
			}

			$this->max_items = count( $insufficient_stock_results );
		}
	}

	/**
	 * Clean up missing bundles.
	 *
	 * @since  5.10.0
	 *
	 * @param  array $result Bundled item data.
	 * @return boolean
	 */
	private function clean_missing_bundles( $result ) {
		return in_array( $result['bundle_id'], $this->ordered_bundle_ids, true );
	}

	/**
	 * Sorting callback - see 'get_items'.
	 *
	 * @param  array $a First bundle.
	 * @param  array $b Second bundle.
	 * @return integer
	 */
	private function order_by_bundle_title( $a, $b ) {

		$bundle_id_a = $a['bundle_id'];
		$bundle_id_b = $b['bundle_id'];

		$bundle_id_a_index = array_search( $bundle_id_a, $this->ordered_bundle_ids, true );
		$bundle_id_b_index = array_search( $bundle_id_b, $this->ordered_bundle_ids, true );

		if ( $bundle_id_a_index === $bundle_id_b_index ) {
			return 0;
		}

		return ( $bundle_id_a_index < $bundle_id_b_index ) ? -1 : 1;
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {

		$columns = array(
			'title'                => __( 'Bundled product', 'woocommerce-product-bundles' ),
			'bundle_title'         => __( 'Bundle', 'woocommerce-product-bundles' ),
			'required_stock_level' => __( 'Units required', 'woocommerce-product-bundles' ),
			'stock_status'         => __( 'Stock status', 'woocommerce' ),
			'wc_actions'           => __( 'Actions', 'woocommerce' ),
		);

		return $columns;
	}

	/**
	 * Renders column values.
	 *
	 * @param  object $item       The current item.
	 * @param  string $column_name The column name.
	 * @return void
	 */
	public function column_default( $item, $column_name ) {

		if ( 'title' === $column_name ) {

			$bundled_item = $item->bundled_item;
			$title        = $bundled_item->product->get_title();

			if ( $bundled_item->has_title_override() ) {
				$bundled_item_title = $bundled_item->get_title();
				if ( '' !== $bundled_item_title ) {
					$title = $title . ' (' . $bundled_item_title . ')';
				}
			}

			echo esc_html( $title );

		} elseif ( 'bundle_title' === $column_name ) {

			$bundled_item = $item->bundled_item;
			$edit_link    = get_edit_post_link( $bundled_item->get_bundle_id() );
			$title        = $bundled_item->get_bundle()->get_title();

			echo '<a class="item" href="' . esc_url( $edit_link ) . '">' . esc_html( $title ) . '</a>';

		} elseif ( 'required_stock_level' === $column_name ) {

			echo esc_html( $item->bundled_item->get_quantity() );

		} else {
			parent::column_default( $item, $column_name );
		}
	}
}
