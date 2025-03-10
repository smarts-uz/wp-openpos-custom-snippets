/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
var __webpack_exports__ = {};

;// CONCATENATED MODULE: external ["wp","hooks"]
const external_wp_hooks_namespaceObject = window["wp"]["hooks"];
;// CONCATENATED MODULE: external ["wp","i18n"]
const external_wp_i18n_namespaceObject = window["wp"]["i18n"];
;// CONCATENATED MODULE: external ["wp","element"]
const external_wp_element_namespaceObject = window["wp"]["element"];
;// CONCATENATED MODULE: ./resources/js/admin/product-tutorial/index.js
/**
 * External dependencies
 */



(0,external_wp_hooks_namespaceObject.addFilter)('experimental_woocommerce_admin_product_tour_steps', 'woocommerce-product-bundles', (tourSteps, tourType) => {
  if ('product-bundles' !== tourType) {
    return tourSteps;
  }
  const steps = [{
    referenceElements: {
      desktop: '._regular_price_field'
    },
    focusElement: {
      desktop: '#_regular_price'
    },
    meta: {
      name: 'product-bundle-price',
      heading: (0,external_wp_i18n_namespaceObject.__)('Assign a base price to your Bundle', 'woocommerce-product-bundles'),
      descriptions: {
        desktop: (0,external_wp_element_namespaceObject.createInterpolateElement)((0,external_wp_i18n_namespaceObject.__)('Use these fields to define a base price for your Bundle. This can be handy if your Bundle does not include any options that affect its price. If you prefer to <link>preserve the prices of individual bundled items</link>, you may omit this step.', 'woocommerce-product-bundles'), {
          link: (0,external_wp_element_namespaceObject.createElement)('a', {
            href: 'https://woocommerce.com/document/bundles/bundles-configuration/#pricing',
            'aria-label': (0,external_wp_i18n_namespaceObject.__)('Product Bundles configuration documentation.', 'woocommerce-product-bundles'),
            target: '_blank'
          })
        })
      }
    }
  }, {
    referenceElements: {
      desktop: '.product_data .bundled_products_tab'
    },
    meta: {
      name: 'add-bundled-products',
      heading: (0,external_wp_i18n_namespaceObject.__)('Add bundled items', 'woocommerce-product-bundles'),
      descriptions: {
        desktop: (0,external_wp_element_namespaceObject.createInterpolateElement)((0,external_wp_i18n_namespaceObject.__)('You can add both Simple and Variable products to this Bundle. Once added, every bundled item reveals <link>additional pricing, shipping and display options</link> to configure.', 'woocommerce-product-bundles'), {
          link: (0,external_wp_element_namespaceObject.createElement)('a', {
            href: 'https://woocommerce.com/document/bundles/bundles-configuration/#bundled-product-options',
            'aria-label': (0,external_wp_i18n_namespaceObject.__)('Product Bundles configuration documentation.', 'woocommerce-product-bundles'),
            target: '_blank'
          })
        })
      }
    }
  }, {
    referenceElements: {
      desktop: '.product_data .shipping_tab'
    },
    meta: {
      name: 'shipping-options',
      heading: (0,external_wp_i18n_namespaceObject.__)('Configure shipping options', 'woocommerce-product-bundles'),
      descriptions: {
        desktop: (0,external_wp_i18n_namespaceObject.__)('Assembled Bundles have their own dimensions and weight: Choose the Assembled option if the items contained in your Bundle are physically assembled in a common container. Unassembled Bundles do not have any shipping options to configure: Choose the Unassembled option if the items of your Bundle are shipped in their existing packaging.', 'woocommerce-product-bundles')
      }
    }
  }];
  return steps;
});
/******/ })()
;