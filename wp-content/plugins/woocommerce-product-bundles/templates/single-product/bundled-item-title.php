<?php
/**
 * Bundled Item Title template
 *
 * Override this template by copying it to 'yourtheme/woocommerce/single-product/bundled-item-title.php'.
 *
 * On occasion, this template file may need to be updated and you (the theme developer) will need to copy the new files to your theme to maintain compatibility.
 * We try to do this as little as possible, but it does happen.
 * When this occurs the version of the template file will be bumped and the readme will list any important changes.
 *
 * Note: Bundled product properties are accessible via '$bundled_item->product'.
 *
 * @version 6.21.0
 * @package WooCommerce Product Bundles
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( '' === $title ) {
	return;
}

?><h4 class="bundled_product_title product_title">
	<?php
	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$title = '<span class="bundled_product_title_inner">' . WC_PB_Helpers::format_product_shop_title( $title, $quantity, '', $title_suffix ) . '</span>';
	/**
	 * Filters the bundled item title HTML.
	 *
	 * @since 6.0.0
	 *
	 * @param string                 $title        The bundled item title HTML.
	 * @param WC_Product_Bundle_Item $bundled_item The bundled item.
	 * @param WC_Product_Bundle      $bundle       The bundle product.
	 */
	$link = $permalink ? apply_filters( 'woocommerce_bundled_item_link_html', ' <span class="bundled_product_title_link"><a class="bundled_product_permalink" href="' . esc_url( $permalink ) . '" target="_blank" aria-label="' . esc_attr__( 'View product', 'woocommerce-product-bundles' ) . '"></a></span>', $bundled_item, $bundle ) : ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	echo wp_kses_post( $title . $link );
	?>
</h4>
