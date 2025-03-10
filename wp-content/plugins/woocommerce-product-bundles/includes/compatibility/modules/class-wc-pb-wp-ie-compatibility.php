<?php
/**
 * WC_PB_WP_IE_Compatibility class
 *
 * @package  WooCommerce Product Bundles
 * @since    5.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP Import/Export Compatibility.
 * Uses a dedicated '_bundled_items_db_data' meta field to export bundle data using the 'get_data()' method of the WC_Bundled_Item_Data CRUD class.
 * Data is imported again using the WC_Bundled_Item_Data class.
 * Supports import of existing v4 data from post meta.
 *
 * @version  7.1.2
 */
class WC_PB_WP_IE_Compatibility {

	/**
	 *  Init compatibility.
	 *
	 * @return void
	 */
	public static function init() {

		// Export bundle data.
		add_filter( 'wxr_export_skip_postmeta', array( __CLASS__, 'wp_export_data' ), 10, 3 );

		if ( class_exists( 'WP_Importer' ) ) {

			// Import bundle data exported using PB v5.
			add_filter( 'wp_import_post_meta', array( __CLASS__, 'wp_import_data' ), 10, 3 );

			// Reassociate bundled items with products on import end.
			add_action( 'import_end', array( __CLASS__, 'wp_import_end' ) );
		}
	}

	/**
	 * Export bundle data using the 'get_data()' method of the WC_Bundled_Item_Data CRUD class.
	 * Data is exported with a hack, when the '_wc_pb_layout_style' meta is exported.
	 *
	 * @param  object $skip_export   Whether to skip the export of the meta key.
	 * @param  array  $meta_key     Meta key.
	 * @param  array  $meta         Meta data.
	 * @return object
	 */
	public static function wp_export_data( $skip_export, $meta_key, $meta ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		global $post;

		// Export serialized data before the '_wc_pb_layout_style' meta.
		if ( '_wc_pb_layout_style' === $meta_key ) {

			$bundled_items = WC_PB_DB::query_bundled_items(
				array(
					'return'    => 'objects',
					'bundle_id' => $post->ID,
				)
			);

			if ( ! empty( $bundled_items ) ) {
				$data = array();
				foreach ( $bundled_items as $bundled_item ) {
					$data[ $bundled_item->get_id() ] = $bundled_item->get_data();
				}
				$item_data = wp_json_encode( $data );

				?>
				<wp:postmeta>
					<wp:meta_key><?php echo wxr_cdata( '_bundled_items_db_data' ); ?></wp:meta_key><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<wp:meta_value><?php echo wxr_cdata( $item_data ); ?></wp:meta_value><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</wp:postmeta>
				<?php
			}
		} elseif ( '_wc_pb_v4_bundle_data' === $meta_key ) {
			$skip_export = true;
		}

		return $skip_export;
	}

	/**
	 * Import json-encoded bundle data using the WC_Bundled_Item_Data CRUD class.
	 *
	 * @param  array $post_meta     Post meta.
	 * @param  int   $imported_post_id  Imported post ID.
	 * @param  array $post      Post data.
	 * @return array
	 */
	public static function wp_import_data( $post_meta, $imported_post_id, $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$bundle_data = false;
		foreach ( $post_meta as $meta_key => $meta_data ) {
			if ( '_bundled_items_db_data' === $meta_data['key'] ) {
				$bundle_data = json_decode( $meta_data['value'], true );
				unset( $post_meta[ $meta_key ] );
			}
		}

		if ( ! empty( $bundle_data ) ) {
			foreach ( $bundle_data as $bundled_item_id => $bundled_item_data ) {

				// Create bundled item.
				WC_PB_DB::add_bundled_item(
					array(
						'bundle_id'  => $imported_post_id,                  // Use the new bundle id.
						'product_id' => $bundled_item_data['product_id'], // May get modified during import - @see 'wp_import_end().
						'menu_order' => $bundled_item_data['menu_order'],
						'meta_data'  => $bundled_item_data['meta_data'],
						'force_add'  => true,                                // Bundled product may not exist in the DB yet, but get created later during import.
					)
				);

			}

			// Flush bundle transients.
			wc_delete_product_transients( $imported_post_id );
		}

		return $post_meta;
	}

	/**
	 * Reassociate bundled item ids with modified bundled product ids on import end.
	 * Also delete the bundled items stock cache.
	 */
	public static function wp_import_end() {
		global $wpdb, $wp_import;

		if ( ! empty( $wp_import ) && ! empty( $wp_import->processed_posts ) ) {

			$processed_products                = (array) $wp_import->processed_posts;
			$case_arguments                    = array();
			$prepare_arguments                 = array();
			$placeholders_update_products_keys = array();
			$i                                 = 1;

			if ( ! empty( $processed_products ) ) {
				foreach ( $processed_products as $old_id => $new_id ) {
					if ( absint( $old_id ) !== absint( $new_id ) ) {
						$prepare_arguments[]                 = $old_id;
						$prepare_arguments[]                 = $new_id;
						$placeholders_update_products_keys[] = '%' . $i . '$d';
						$case_arguments[]                    = 'WHEN %' . $i . '$d THEN %' . ( $i + 1 ) . '$d';
						$i                                  += 2;
					}
				}
			}

			// Reassociate ids.
			if ( ! empty( $case_arguments ) ) {

				// Placeholders for WHEN...THEN clauses. This variable adds as many %s placeholders to the query clauses. Therefore, we are skipping PHPCS checks for this query.
				$case_arguments = implode( ' ', $case_arguments );

				// Placeholders for product IDs. This variable adds as many %d placeholders to the query as the IDs. Therefore, we are skipping PHPCS checks for this query.
				$placeholders_update_products_keys = implode( ',', $placeholders_update_products_keys );

				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				$wpdb->query(
					$wpdb->prepare(
						"
					UPDATE `{$wpdb->prefix}woocommerce_bundled_items`
					SET `product_id` = CASE `product_id` " . $case_arguments . ' ELSE `product_id` END
					WHERE `product_id` IN (' . $placeholders_update_products_keys . ')
					AND `bundle_id` IN (' . $placeholders_update_products_keys . ')
				',
						...$prepare_arguments,
						...$prepare_arguments
					)
				); // Need to do this twice because WP counts each individual placeholder,
				// rather than taking into account the position argument.
				// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			}

			WC_PB_DB::bulk_delete_bundled_item_stock_meta();

			$data_store = WC_Data_Store::load( 'product-bundle' );
			$data_store->reset_bundled_items_stock_status();
		}
	}
}

WC_PB_WP_IE_Compatibility::init();
