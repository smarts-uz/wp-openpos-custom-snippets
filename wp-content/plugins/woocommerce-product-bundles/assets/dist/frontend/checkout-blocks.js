/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
var __webpack_exports__ = {};

;// CONCATENATED MODULE: external ["wp","data"]
const external_wp_data_namespaceObject = window["wp"]["data"];
;// CONCATENATED MODULE: external ["wp","hooks"]
const external_wp_hooks_namespaceObject = window["wp"]["hooks"];
;// CONCATENATED MODULE: external ["wc","blocksCheckout"]
const external_wc_blocksCheckout_namespaceObject = window["wc"]["blocksCheckout"];
;// CONCATENATED MODULE: ./resources/js/frontend/blocks/checkout/index.js
/**
 * External dependencies
 */



(0,external_wc_blocksCheckout_namespaceObject.registerCheckoutFilters)('product-bundles', {
  cartItemClass: (classlist, {
    bundles
  }, {
    context,
    cartItem
  }) => {
    if (bundles) {
      const classes = [];
      if (bundles.bundled_by) {
        classes.push('is-bundled');
        classes.push('is-bundled__cid_' + bundles.bundled_item_data.bundle_id);
        classes.push('is-bundled__iid_' + bundles.bundled_item_data.bundled_item_id);
        if (bundles.bundled_item_data.is_indented) {
          classes.push('is-bundled__indented');
        }
        if (bundles.bundled_item_data.is_last) {
          classes.push('is-bundled__last');
        }
        if (bundles.bundled_item_data.is_removable) {
          classes.push('is-bundled__removable');
        }
        if (bundles.bundled_item_data.is_subtotal_aggregated) {
          classes.push('is-bundled__subtotal_aggregated');
        }
        if (bundles.bundled_item_data.is_price_hidden) {
          classes.push('is-bundled__price_hidden');
        }
        if (bundles.bundled_item_data.is_subtotal_hidden) {
          classes.push('is-bundled__subtotal_hidden');
        }
        if (bundles.bundled_item_data.is_hidden_in_cart && context === 'cart') {
          classes.push('is-bundled__hidden');
        }
        if (bundles.bundled_item_data.is_hidden_in_summary && context === 'summary') {
          classes.push('is-bundled__hidden');
        }
        if (bundles.bundled_item_data.is_thumbnail_hidden) {
          classes.push('is-bundled__thumbnail_hidden');
        }
        if (bundles.bundled_item_data.is_parent_visible) {
          classes.push('is-bundled__description_hidden');
        }
        if (bundles.bundled_item_data.is_composited) {
          classes.push('is-bundled__composited');
        }
        if (bundles.bundled_item_data.is_ungrouped) {
          classes.push('is-bundled__ungrouped');
        }
      } else if (bundles.bundled_items) {
        classes.push('is-bundle');
        classes.push('is-bundle__cid_' + cartItem.id);
        if (bundles.bundle_data.is_editable) {
          classes.push('is-bundle__editable');
        }
        if (bundles.bundle_data.is_hidden) {
          classes.push('is-bundle__hidden');
        }
        if (bundles.bundle_data.is_title_hidden) {
          classes.push('is-bundle__title_hidden');
        }
        if (bundles.bundle_data.is_price_hidden) {
          classes.push('is-bundle__price_hidden');
        }
        if (bundles.bundle_data.is_subtotal_hidden) {
          classes.push('is-bundle__subtotal_hidden');
        }
        if (bundles.bundle_data.is_meta_hidden_in_cart && context === 'cart') {
          classes.push('is-bundle__meta_hidden');
        }
        if (bundles.bundle_data.is_meta_hidden_in_summary && context === 'summary') {
          classes.push('is-bundle__meta_hidden');
        }
      }
      if (classes.length) {
        classlist += ' ' + classes.join(' ');
      }
    }
    return classlist;
  }
});

//
// Functions to mark bundled items as pending delete or quantity
// when the user removes or changes the quantity of a bundle in the cart.
//
// This suppresses the quantity change notifications for bundled items.
//

function markBundledItems(product, actionCreator) {
  const bundledItems = product?.extensions?.bundles?.bundled_items;

  // bundled_items can be either a sequential/regular array
  // or a non-sequential array, which is an object.

  if (!Array.isArray(bundledItems) && typeof bundledItems !== 'object') {
    return;
  }
  const bundledItemsCartItemKeys = Array.isArray(bundledItems) ? bundledItems : Object.values(bundledItems);
  bundledItemsCartItemKeys.forEach(cartItemKey => actionCreator(cartItemKey, true));
}
function markBundledItemsAsPendingDelete({
  product
}) {
  const {
    itemIsPendingDelete
  } = (0,external_wp_data_namespaceObject.dispatch)('wc/store/cart');
  markBundledItems(product, itemIsPendingDelete);
}

//
// Hook up the action callbacks to mark bundled items as pending delete or quantity.
//

(0,external_wp_hooks_namespaceObject.addAction)('experimental__woocommerce_blocks-cart-remove-item', 'product-bundles', markBundledItemsAsPendingDelete);
function maybeHideProductBundlesNotice(shouldShow, cartItem) {
  if (!shouldShow) {
    return false;
  }
  if (cartItem?.extensions?.bundles?.bundled_by) {
    return false;
  }
  return shouldShow;
}
(0,external_wp_hooks_namespaceObject.addFilter)('woocommerce_show_cart_item_removed_notice', 'woocommerce-blocks/checkout', maybeHideProductBundlesNotice);
(0,external_wp_hooks_namespaceObject.addFilter)('woocommerce_show_cart_item_quantity_changed_notice', 'woocommerce-blocks/checkout', maybeHideProductBundlesNotice);
/******/ })()
;