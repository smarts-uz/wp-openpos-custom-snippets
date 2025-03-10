<?php
/**
 * WC_PB_CLI_Sync_Stock class
 *
 * @package  WooCommerce Product Bundles
 * @since    7.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allows Bundle stock updates via WP-CLI.
 *
 * @class    WC_PB_CLI_Sync_Stock
 * @version  7.1.0
 */
class WC_PB_CLI_Sync_Stock {

	/**
	 * Registers the update command.
	 */
	public static function register_command() {
		WP_CLI::add_command( 'wc pb sync-stock', array( 'WC_PB_CLI_Sync_Stock', 'sync' ) );
	}

	/**
	 * Sync stock for all product bundles.
	 */
	public static function sync() {
		WP_CLI::log( 'Starting Product Bundles stock sync...' );

		$data_store = WC_Data_Store::load( 'product-bundle' );
		$ids        = $data_store->get_bundled_items_stock_sync_status_ids( 'unsynced' );

		if ( empty( $ids ) ) {
			WP_CLI::success( 'No unsycned Bundles.' );
			return;
		}

		$processed_ids = array();
		$delete_ids    = array();
		foreach ( $ids as $id ) {

			$product = wc_get_product( $id );
			if ( is_a( $product, 'WC_Product' ) && $product->is_type( 'bundle' ) ) {
				$product->sync_stock();
				$processed_ids[] = $id;
			} else {
				$delete_ids[] = $id;
			}
		}

		if ( ! empty( $processed_ids ) ) {
			WP_CLI::log( sprintf( 'Synced IDs: [%s]', implode( ', ', $processed_ids ) ) );
		}

		if ( ! empty( $delete_ids ) ) {

			WP_CLI::log( sprintf( 'Discarding invalid IDs: [%s]', implode( ', ', $delete_ids ) ) );

			$data_store = WC_Data_Store::load( 'product-bundle' );
			$data_store->delete_bundled_items_stock_sync_status( $delete_ids );
		}

		WP_CLI::success( 'Task complete.' );
	}
}
