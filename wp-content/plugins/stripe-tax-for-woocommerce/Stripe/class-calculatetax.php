<?php
/**
 * Calculate Tax service
 * Makes Calculate Tax API calls, validations, database and WordPress object cache store
 *
 * @package Stripe\StripeTaxForWooCommerce\Stripe
 */

namespace Stripe\StripeTaxForWooCommerce\Stripe;

defined( 'ABSPATH' ) || exit;

use Exception;
use Stripe\StripeTaxForWooCommerce\SDK\lib\Tax\Calculation;
use Stripe\StripeTaxForWooCommerce\WooCommerce\ExtendedProduct;
use Stripe\StripeTaxForWooCommerce\WooCommerce\StripeOrderItemTax;
use Stripe\StripeTaxForWooCommerce\WordPress\Options;
use WC_Order_Item;

/**
 * Service for Stripe Calculate Tax API.
 *
 * @see https://stripe.com/docs/api/tax/calculations
 */
class CalculateTax {
	use StripeClientTrait;

	const CACHE_GROUP = 'stripe-tax-for-woocommerce';
	const CACHE_KEY   = 'calculate-tax';
	const TABLE_NAME  = STRIPE_TAX_FOR_WOOCOMMERCE_DB_PREFIX . 'calculate_tax';

	/**
	 * Stripe API key
	 *
	 * @var string
	 */
	protected $api_key;

	/**
	 * Prepared request to be sent on Stripe Calculate Tax API to make actual tax calculation.
	 *
	 * @see https://stripe.com/docs/api/tax/calculations/create
	 * @var array
	 */
	protected $request = array();

	/**
	 * MD5 of JSON string of Stripe Tax Calculation request array.
	 * Used to check if we are making the same request as already did (to not repeat it if calculation not expired).
	 *
	 * @var string
	 */
	protected $calculate_tax_md5 = '';

	/**
	 * MD5 of JSON string of Stripe Tax Registrations array.
	 * Used to check if Tax Registrations changed (to ensure already made tax calculation is valid if not expired)
	 *
	 * @var string
	 */
	protected $tax_registrations_md5 = '';

	/**
	 * MD5 of JSON string of Stripe Tax Settings array.
	 * Used to check if Tax Settings changed (to ensure already made tax calculation is valid if not expired)
	 *
	 * @var string
	 */
	protected $tax_settings_md5 = '';

	/**
	 * MD5 of Stripe API key string.
	 * Used to check if Stripe API key changed (to ensure already made tax calculation is valid if not expired)
	 *
	 * @var string
	 */
	protected $api_key_md5 = '';

	/**
	 * Concatenated $calculate_tax_md5 + $tax_registrations_md5 + $tax_settings_md5 + $api_key_md5.
	 * Used as key of associative array to cache Tax Calculations inside of WordPress object cache.
	 *
	 * @var string
	 */
	protected $md5_concat = '';

	/**
	 * Tax Registrations object
	 * Used to receive current Stripe Tax Registrations, to check if anything changed before creating Tax Calculation.
	 *
	 * @var TaxRegistrations
	 */
	protected $tax_registration;

	/**
	 * Tax Settings object
	 * Used to receive current Stripe Tax Settings, to check if anything changed before creating Tax Calculation.
	 *
	 * @var TaxSettings
	 */
	protected $tax_settings;

	/**
	 * Create CalculateTax service
	 *
	 * @param string $api_key API key.
	 * @param string $currency Currency.
	 * @param array  $line_items Line items.
	 * @param array  $customer_details Customer details.
	 * @param array  $shipping_cost Shipping cost.
	 *
	 * @throws Exception If data is not valid.
	 */
	public function __construct( string $api_key, string $currency, array $line_items, array $customer_details, array $shipping_cost = array() ) {
		$this->api_key = $api_key;
		Validate::validate_currency( $currency );
		Validate::validate_number_of_line_items( $line_items );
		Validate::validate_line_items( $line_items );
		Validate::check_customer_details_address_fields( $customer_details['address'] );

		$this->request = array(
			'currency'         => $currency,
			'customer_details' => $customer_details,
			'line_items'       => $line_items,
			'expand'           => array( 'line_items', 'line_items.data.tax_breakdown' ),
		);
		if ( $shipping_cost ) {
			$this->request['shipping_cost'] = $shipping_cost;
		}
		$this->calculate_tax_md5     = md5( wp_json_encode( $this->request ) );
		$this->tax_registrations_md5 = md5( wp_json_encode( $this->get_tax_registration( $this->api_key )->get_registrations() ) );
		$this->tax_settings_md5      = md5( wp_json_encode( $this->get_tax_settings( $this->api_key )->get_settings() ) );
		$this->api_key_md5           = md5( $this->api_key );
		$this->md5_concat            = $this->calculate_tax_md5 . $this->tax_registrations_md5 . $this->tax_settings_md5 . $this->api_key_md5;
	}

	/**
	 * Factory method to create TaxRegistrations service
	 *
	 * @param string $api_key API key.
	 *
	 * @return TaxRegistrations TaxRegistrations object
	 */
	protected function get_tax_registration( string $api_key ): TaxRegistrations {
		if ( is_null( $this->tax_registration ) ) {
			$this->tax_registration = new TaxRegistrations( $api_key );
		}
		return $this->tax_registration;
	}

	/**
	 * Factory method to create TaxSettings service
	 *
	 * @param string $api_key API key.
	 *
	 * @return TaxSettings TaxSettings object
	 */
	protected function get_tax_settings( string $api_key ): TaxSettings {
		if ( is_null( $this->tax_settings ) ) {
			$this->tax_settings = new TaxSettings( $api_key );
		}
		return $this->tax_settings;
	}

	/**
	 * Get customer details by WooCommerce order or WooCommerce customer objects
	 *
	 * @param \WC_Order|\WC_Customer $wc_order WooCommerce Order.
	 *
	 * @return array Prepared customer details for Calculate Tax API call
	 */
	public static function get_customer_details_by_order( $wc_order ): array {
		/**
		 * Order
		 *
		 * @var \WC_Order|\WC_Customer $wc_order
		 */
		$shipping         = false;
		$billing          = false;
		$customer_details = array();
		$country          = $wc_order->get_shipping_country( 'edit' );
		$state            = $wc_order->get_shipping_state( 'edit' );
		$postal_code      = $wc_order->get_shipping_postcode( 'edit' );

		if ( 'US' === $country ) {
			if ( $state && $postal_code ) {
				$shipping = true;
			}
		} elseif ( 'CA' === $country ) {
			if ( $postal_code ) {
				$shipping = true;
			}
		} elseif ( $country ) {
			$shipping = true;
		}

		if ( ! $shipping ) {
			$country     = $wc_order->get_billing_country( 'edit' );
			$state       = $wc_order->get_billing_state( 'edit' );
			$postal_code = $wc_order->get_billing_postcode( 'edit' );
			if ( 'US' === $country ) {
				if ( $state && $postal_code ) {
					$billing = true;
				}
			} elseif ( 'CA' === $country ) {
				if ( $postal_code ) {
					$billing = true;
				}
			} elseif ( $country ) {
				$billing = true;
			}
		}

		if ( $shipping ) {
			$customer_details['address'] = array();
			if ( $wc_order->get_shipping_country( 'edit' ) ) {
				$customer_details['address']['country'] = $wc_order->get_shipping_country( 'edit' );
			}
			if ( $wc_order->get_shipping_state( 'edit' ) ) {
				$customer_details['address']['state'] = $wc_order->get_shipping_state( 'edit' );
			}
			if ( $wc_order->get_shipping_city( 'edit' ) ) {
				$customer_details['address']['city'] = $wc_order->get_shipping_city( 'edit' );
			}
			if ( $wc_order->get_shipping_postcode( 'edit' ) ) {
				$customer_details['address']['postal_code'] = $wc_order->get_shipping_postcode( 'edit' );
			}
			if ( $wc_order->get_shipping_address_1( 'edit' ) ) {
				$customer_details['address']['line1'] = $wc_order->get_shipping_address_1( 'edit' );
			}
			if ( $wc_order->get_shipping_address_2( 'edit' ) ) {
				$customer_details['address']['line2'] = $wc_order->get_shipping_address_2( 'edit' );
			}
			if ( $customer_details['address'] ) {
				$customer_details['address_source'] = 'shipping';
			}
		} else {
			$customer_details['address'] = array();
			if ( $wc_order->get_billing_country( 'edit' ) ) {
				$customer_details['address']['country'] = $wc_order->get_billing_country( 'edit' );
			}
			if ( $wc_order->get_billing_state( 'edit' ) ) {
				$customer_details['address']['state'] = $wc_order->get_billing_state( 'edit' );
			}
			if ( $wc_order->get_billing_city( 'edit' ) ) {
				$customer_details['address']['city'] = $wc_order->get_billing_city( 'edit' );
			}
			if ( $wc_order->get_billing_postcode( 'edit' ) ) {
				$customer_details['address']['postal_code'] = $wc_order->get_billing_postcode( 'edit' );
			}
			if ( $wc_order->get_billing_address_1( 'edit' ) ) {
				$customer_details['address']['line1'] = $wc_order->get_billing_address_1( 'edit' );
			}
			if ( $wc_order->get_billing_address_2( 'edit' ) ) {
				$customer_details['address']['line2'] = $wc_order->get_billing_address_2( 'edit' );
			}
			if ( $customer_details['address'] ) {
				$customer_details['address_source'] = 'billing';
			}
		}

		if ( ! $customer_details['address'] ) {
			return array();
		}

		return $customer_details;
	}

	/**
	 * States whether the tax can be calculated for the provided customer details.
	 *
	 * @param array $customer_details The processed customer details.
	 * @return bool
	 */
	public static function can_calculate_tax( $customer_details ) {
		if ( empty( $customer_details ) || empty( $customer_details['address'] ) ) {
			return false;
		}

		if ( 'US' === $customer_details['address']['country'] && empty( $customer_details['address']['postal_code'] ) ) {
			return false;
		}

		if ( 'CA' === $customer_details['address']['country'] && ( empty( $customer_details['address']['state'] ) || empty( $customer_details['address']['postal_code'] ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get customer details by posted parameters.
	 * This part is triggered on order creation through WooCommerce admin.
	 *
	 * @return array Prepared customer details for Calculate Tax API call
	 */
	public static function get_customer_details_by_post(): array {
		try {
			return static::get_customer_details_address();
		} catch ( Exception $e ) {
			try {
				return static::get_customer_details_address( true );
			} catch ( Exception $e ) {
				return array();
			}
		}
	}

	/**
	 * Get customer address details from billing or shipping fields.
	 *
	 * @param bool $from_billing If we want to get details from billing fields.
	 * @return array Customer address details.
	 * @throws Exception If data is not correct.
	 */
	private static function get_customer_details_address( bool $from_billing = false ): array {
		if ( ! isset( $_POST['stripe_tax_for_woocommerce_customer_details_shipping_address'] ) && ! isset( $_POST['stripe_tax_for_woocommerce_customer_details_billing_address'] ) ) {
			return array();
		}

		check_admin_referer( 'calc-totals', 'security' );
		if ( ! isset( $_REQUEST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'calc-totals' ) ) {
			exit;
		}

		$customer_details = array();

		if ( $from_billing || 'disabled' === get_option( 'woocommerce_ship_to_countries' ) ) {
			$customer_details_address           = map_deep( wp_unslash( $_POST['stripe_tax_for_woocommerce_customer_details_billing_address'] ?? array() ), 'sanitize_text_field' );
			$customer_details['address_source'] = 'billing';
		} else {
			$customer_details_address           = map_deep( wp_unslash( $_POST['stripe_tax_for_woocommerce_customer_details_shipping_address'] ?? array() ), 'sanitize_text_field' );
			$customer_details['address_source'] = 'shipping';
		}

		Validate::validate_customer_details_address( $customer_details_address );
		$customer_details['address'] = $customer_details_address;
		return $customer_details;
	}

	/**
	 * Get customer id by posted parameters.
	 * This part is triggered on order creation through WooCommerce admin.
	 *
	 * @return string Customer ID or "guest"
	 */
	public static function get_customer_id_by_post(): string {
		if ( empty( $_POST['stripe_tax_for_woocommerce_customer_user'] ) || ( ! is_string( $_POST['stripe_tax_for_woocommerce_customer_user'] ) ) ) {
			return '';
		}

		check_admin_referer( 'calc-totals', 'security' );
		if ( ! isset( $_REQUEST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ), 'calc-totals' ) ) {
			StripeTaxPluginHelper::do_exit();
		}

		return sanitize_text_field( wp_unslash( $_POST['stripe_tax_for_woocommerce_customer_user'] ) );
	}

	/**
	 * Provides tax exemption information, depending on path, how it called.
	 *
	 * @param \WC_Order $wc_order WooCommerce Order object.
	 *
	 * @return string
	 */
	public static function get_order_tax_exempt( \WC_Order $wc_order ): string {
		$tax_exemptions = new TaxExemptions();

		$customer_id = self::get_customer_id_by_post();

		switch ( $customer_id ) {
			case StripeTaxPluginHelper::GUEST_CUSTOMER_ID: // In case of 'guest', assume "none" tax exemption.
				$taxability_override = 'none';
				break;
			case '': // In case of no information about customer id, assume WC_Order object contains it.
				$taxability_override = $tax_exemptions->get_tax_exeption( $wc_order->get_customer_id( 'edit' ) );
				break;
			default: // In other cases (Customer ID received) use it to receive tax exemption information.
				$taxability_override = $tax_exemptions->get_tax_exeption( $customer_id );
		}

		return $taxability_override;
	}

	/**
	 * Get original Stripe Line Item id by Stripe/WooCommerce Line Item reference.
	 * For partial refund Stripe Reversal Transaction API call also needed to send original line item id to be refunded.
	 *
	 * @param mixed $reference Reference.
	 * @param mixed $tax_transaction Tax transaction.
	 *
	 * @return string Line Item ID for partial refund Stripe Reversal Transaction API
	 */
	public static function get_original_line_item_id( $reference, $tax_transaction ) {
		$items = $tax_transaction->line_items->data;
		foreach ( $items as $item ) {
			if ( $item->reference !== $reference ) {
				continue;
			}

			return $item->id;
		}

		return '';
	}

	/**
	 * Get line items by WooCommerce order
	 * Used to prepare line items array to be sent to Stripe Calculate Tax API or to Stripe Reversal Transaction API.
	 *
	 * @param \WC_Order|\WC_Order_Refund $wc_order WooCommerce order object.
	 * @param bool                       $for_refund If line items are preparing for refund, not for Calculate Tax.
	 * @param \stdClass|null             $tax_transaction_data Tax transaction data. Needed only for refund.
	 *
	 * @return array Line items for Calculate Tax API or for Reversal Transaction API calls
	 * @throws Exception If data is not correct.
	 */
	public static function get_line_items_by_order( $wc_order, bool $for_refund = false, \stdClass $tax_transaction_data = null ): array {
		$items              = $wc_order->get_items();
		$currency           = strtolower( get_woocommerce_currency() );
		$line_items         = array();
		$line_items_counter = 0;
		// In WooCommerce same item (item with the same title/reference) can be added multiple times.
		// For Stripe API, item title/reference must be unique.
		// This array used to store item's title/reference and $line_items array index of where this item stored.
		// This is needed to not add new existing item to $line_items array, but to increase quantity and cost of items.
		$items_reference_already_added = array();

		foreach ( $items as $item ) {
			/**
			 * Order Item Product
			 *
			 * @var \WC_Order_Item_Product $product
			 * @var \WC_Product $product
			 */
			$product  = $item->get_product();
			$quantity = ( $for_refund ? - $item->get_quantity() : $item->get_quantity() );
			// "float" casting used, because the WooCommerce returns product properties as values of "string" type.
			$amount            = self::get_order_item_total_amount( $item, $quantity, $for_refund );
			$normalized_amount = self::get_normalized_amount( $amount, $currency );
			if ( $for_refund ) {
				$tax_amount            = (float) ( $item->get_total_tax( 'edit' ) );
				$normalized_tax_amount = self::get_normalized_amount( $tax_amount, $currency );
			}
			$item_id        = ( $for_refund ? $item->get_meta( '_refunded_item_id' ) : $item->get_id() );
			$reference      = $item->get_name( 'edit' ) . ( (int) $item_id ? ( '#' . $item_id ) : '' );
			$product_id     = $product->get_id();
			$stripe_product = ( new ExtendedProduct( $product_id ) )->get_extended_product();
			if ( ! array_key_exists( $reference, $items_reference_already_added ) ) {
				$line_items[ $line_items_counter ] = array(
					'amount'    => $normalized_amount,
					'reference' => $reference,
					'quantity'  => $quantity,
				);
				if ( ! $for_refund ) {
					if ( $stripe_product['tax_code'] ) {
						$line_items[ $line_items_counter ]['tax_code'] = $stripe_product['tax_code'];
					}

					$tax_settings                                      = new TaxSettings( Options::get_live_mode_key() );
					$line_items[ $line_items_counter ]['tax_behavior'] = $tax_settings->get_tax_behavior();
				} else {
					$line_items[ $line_items_counter ]['original_line_item'] = self::get_original_line_item_id( $reference, $tax_transaction_data );
					$line_items[ $line_items_counter ]['amount_tax']         = $normalized_tax_amount;

				}
				$items_reference_already_added[ $reference ]            = $line_items[ $line_items_counter ];
				$items_reference_already_added[ $reference ]['counter'] = $line_items_counter;
				++$line_items_counter;
			} else {
				$cached_item                            = $items_reference_already_added[ $reference ];
				$new_counter                            = $cached_item['counter'];
				$line_items[ $new_counter ]['amount']   = $line_items[ $new_counter ]['amount'] + $normalized_amount;
				$line_items[ $new_counter ]['quantity'] = $line_items[ $new_counter ]['quantity'] + $quantity;
				if ( $for_refund ) {
					$line_items[ $new_counter ]['amount_tax'] = $line_items[ $new_counter ]['amount_tax'] + $normalized_tax_amount;
				}
				$items_reference_already_added[ $reference ]            = $line_items[ $new_counter ];
				$items_reference_already_added[ $reference ]['counter'] = $new_counter;
			}
		}

		return $line_items;
	}

	/**
	 * Prepare line items for Calculate Tax API from WooCommerce cart
	 *
	 * @param \WC_Cart $wc_cart WooCommerce Cart object.
	 *
	 * @return array Prepared line items for Calculate Tax API call
	 * @throws Exception If data is not correct.
	 */
	public static function get_line_items_by_cart( $wc_cart ): array {
		$currency           = strtolower( get_woocommerce_currency() );
		$line_items         = array();
		$line_items_counter = 0;
		// In WooCommerce same item (item with the same title/reference) can be added multiple times.
		// For Stripe API, item title/reference must be unique.
		// This array used to store item's title/reference and $line_items array index of where this item stored.
		// This is needed to not add new existing item to $line_items array, but to increase quantity and cost of items.
		$items_reference_already_added = array();
		foreach ( $wc_cart->cart_contents as $cart_items ) {
			$quantity = (int) $cart_items['quantity'];

			/**
			 * Product
			 *
			 * @var \WC_Product $product
			 */
			$product = $cart_items['data'];
			$amount  = $cart_items['line_subtotal'];

			if ( array_key_exists( 'line_total', $cart_items ) ) {
				$amount = $cart_items['line_total'];
			}

			if ( $amount < 0 ) {
				continue;
			}

			$normalized_amount = self::get_normalized_amount( $amount, $currency );
			$reference         = $product->get_name();
			$id                = $product->get_id();
			$stripe_product    = ( new ExtendedProduct( $id ) )->get_extended_product();
			if ( ! array_key_exists( $reference, $items_reference_already_added ) ) {
				$line_items[ $line_items_counter ] = array(
					'amount'    => $normalized_amount,
					'reference' => $reference,
					'quantity'  => $quantity,
				);
				if ( $stripe_product['tax_code'] ) {
					$line_items[ $line_items_counter ]['tax_code'] = $stripe_product['tax_code'];
				}

				$tax_settings                                      = new TaxSettings( Options::get_live_mode_key() );
				$line_items[ $line_items_counter ]['tax_behavior'] = $tax_settings->get_tax_behavior();
				$items_reference_already_added[ $reference ]       = $line_items[ $line_items_counter ];
				$items_reference_already_added[ $reference ]['counter'] = $line_items_counter;
				++$line_items_counter;
			} else {
				$cached_item                                 = $items_reference_already_added[ $reference ];
				$new_counter                                 = $cached_item['counter'];
				$line_items[ $new_counter ]['amount']        = $line_items[ $new_counter ]['amount'] + $normalized_amount;
				$line_items[ $new_counter ]['quantity']      = $line_items[ $new_counter ]['quantity'] + $quantity;
				$items_reference_already_added[ $reference ] = $line_items[ $new_counter ];
				$items_reference_already_added[ $reference ]['counter'] = $new_counter;
			}
		}

		return $line_items;
	}

	/**
	 * Get the discount amount for a cart item.
	 *
	 * @param array $cart_item Single item from the cart.
	 * @return float
	 */
	public static function get_cart_item_discount_amount( $cart_item ) {
		if ( ! array_key_exists( 'line_subtotal', $cart_item ) || ! array_key_exists( 'line_total', $cart_item ) ) {
			return 0;
		}

		return (float) wc_format_decimal( $cart_item['line_subtotal'] - $cart_item['line_total'] );
	}

	/**
	 * Get the discount amount for an order item.
	 *
	 * @param WC_Order_Item $order_item Single item from an order.
	 * @return float
	 */
	public static function get_order_item_discount_amount( $order_item ) {
		return (float) wc_format_decimal( $order_item->get_subtotal() - $order_item->get_total() );
	}

	/**
	 * Get the total amount for an order item.
	 *
	 * @param WC_Order_Item $order_item Single item from an order.
	 * @return float
	 */
	public static function get_order_item_total_amount( $order_item ) {
		return (float) $order_item->get_total( 'edit' );
	}

	/**
	 * Gets cart or order taxable shipping cost.
	 *
	 * @param \WC_Cart|\WC_Order $wc_cart_or_order Cart or order.
	 *
	 * @return float
	 */
	public static function get_cart_or_order_taxable_shipping_total( $wc_cart_or_order ): float {
		$shipping_methods = StripeTaxPluginHelper::get_cart_or_order_taxable_shipping_methods( $wc_cart_or_order );
		if ( empty( $shipping_methods ) ) {
			return 0.0;
		}

		$taxable_shipping_total = 0.0;

		foreach ( $shipping_methods as $shipping_method ) {
			if ( $wc_cart_or_order instanceof \WC_Cart ) {
				/**
				 * Cart shipping method.
				 *
				 * @var \WC_Shipping_Rate $shipping_method
				 */
				$taxable_shipping_total += (float) $shipping_method->get_cost();
			} elseif ( $wc_cart_or_order instanceof \WC_Order ) {
				/**
				 * Order shipping method.
				 *
				 * @var \WC_Order_Item_Shipping $shipping_method
				 */
				$taxable_shipping_total += (float) $shipping_method->get_total( 'edit' );
			}
		}

		return $taxable_shipping_total;
	}

	/**
	 * Gets cart or order not taxable shipping cost.
	 *
	 * @param \WC_Cart|\WC_Order $wc_cart_or_order Cart or order.
	 *
	 * @return float
	 */
	public static function get_cart_or_order_not_taxable_shipping_total( $wc_cart_or_order ): float {
		$shipping_methods = StripeTaxPluginHelper::get_cart_not_taxable_shipping_methods( $wc_cart_or_order );
		if ( empty( $shipping_methods ) ) {
			return 0.0;
		}

		$not_taxable_shipping_total = 0.0;

		foreach ( $shipping_methods as $shipping_method ) {
			if ( $wc_cart_or_order instanceof \WC_Cart ) {
				/**
				 * Cart shipping method.
				 *
				 * @var \WC_Shipping_Rate $shipping_method
				 */
				$not_taxable_shipping_total += (float) $shipping_method->get_cost();
			} elseif ( $wc_cart_or_order instanceof \WC_Order ) {
				/**
				 * Order shipping method.
				 *
				 * @var \WC_Order_Item_Shipping $shipping_method
				 */
				$not_taxable_shipping_total += (float) $shipping_method->get_total( 'edit' );
			}
		}

		return $not_taxable_shipping_total;
	}

	/**
	 * Builds taxable shipping cost from WooCommerce Cart taxable Shipping Rates for Stripe API.
	 *
	 * @param \WC_Cart|\WC_Order $wc_cart_or_order WooCommerce Cart.
	 * @param string             $currency Currency.
	 *
	 * @return array
	 * @throws Exception In case of any Exception.
	 */
	public static function get_taxable_shipping_cost_from_cart_or_order_for_api( $wc_cart_or_order, string $currency ): array {
		$shipping_total = self::get_cart_or_order_taxable_shipping_total( $wc_cart_or_order );

		$normalized_shipping_total = self::get_normalized_amount( $shipping_total, $currency );

		$tax_settings = new TaxSettings( Options::get_live_mode_key() );

		$shipping_cost = array(
			'amount'       => $normalized_shipping_total,
			'tax_code'     => 'txcd_92010001',
			'tax_behavior' => $tax_settings->get_tax_behavior(),
		);

		return $shipping_cost;
	}

	/**
	 * Apply tax to WooCommerce Cart shipping methods.
	 *
	 * @param array        $shipping_methods Shipping methods.
	 * @param CalculateTax $calculate_tax CalculateTax object.
	 * @param string       $currency Currency.
	 *
	 * @return array
	 * @throws Exception In case of any exception.
	 */
	public static function apply_tax_to_cart_shipping_methods( array $shipping_methods, $calculate_tax, string $currency ): array {
		if ( empty( $shipping_methods ) ) {
			return $shipping_methods;
		}
		$max_cost = 0.0;
		$max_key  = null;
		foreach ( $shipping_methods as $shipping_method_key => $shipping_method ) {
			/**
			 * Shipping method object.
			 *
			 * @var \WC_Shipping_Rate $shipping_method
			 */

			$cost = (float) $shipping_method->get_cost();
			if ( $cost > $max_cost || is_null( $max_key ) ) {
				$max_cost = $cost;
				$max_key  = $shipping_method_key;
			}
		}

		if ( is_null( $max_key ) || $max_cost <= 0.0 ) {
			return $shipping_methods;
		}

		$response = $calculate_tax->get_response();

		if ( $response->shipping_cost->amount > 0.0 ) {
			$shipping_methods[ $max_key ]->set_taxes( $calculate_tax->get_shipping_cart_item_tax( $currency ) );
		}

		return $shipping_methods;
	}

	/**
	 * Apply tax to WooCommerce Order shipping methods.
	 *
	 * @param mixed  $shipping_methods WooCommerce Order shipping methods.
	 * @param mixed  $calculate_tax CalculateTax object.
	 * @param string $currency Currency.
	 *
	 * @return mixed
	 */
	public static function apply_tax_to_order_shipping_methods( $shipping_methods, $calculate_tax, string $currency ) {
		if ( empty( $shipping_methods ) ) {
			return $shipping_methods;
		}
		$max_cost = 0.0;
		$max_key  = null;
		foreach ( $shipping_methods as $shipping_method_key => $shipping_method ) {
			/**
			 * Shipping method object.
			 *
			 * @var \WC_Order_Item_Shipping $shipping_method
			 */

			$cost = (float) $shipping_method->get_total( 'edit' );
			if ( $cost > $max_cost || is_null( $max_key ) ) {
				$max_cost = $cost;
				$max_key  = $shipping_method_key;
			}
		}

		if ( is_null( $max_key ) || $max_cost <= 0.0 ) {
			return $shipping_methods;
		}

		$response = $calculate_tax->get_response();

		if ( $response->shipping_cost->amount > 0.0 ) {
			$shipping_methods[ $max_key ]->set_taxes( $calculate_tax->get_shipping_order_item_raw_tax_data( $currency ) );
		}

		return $shipping_methods;
	}

	/**
	 * Uses the shipping class to calculate shipping then gets the totals when its finished.
	 *
	 * @param array $shipping_methods WooCommerce Cart shipping methods.
	 * @param mixed $wc_cart WooCommerce Cart.
	 *
	 * @return array
	 */
	public static function calculate_cart_shipping( array $shipping_methods, $wc_cart ): array {
		$shipping_taxes = wp_list_pluck( $shipping_methods, 'taxes' );
		$merged_taxes   = array();
		foreach ( $shipping_taxes as $taxes ) {
			foreach ( $taxes as $tax_id => $tax_amount ) {
				if ( ! isset( $merged_taxes[ $tax_id ] ) ) {
					$merged_taxes[ $tax_id ] = 0;
				}
				$merged_taxes[ $tax_id ] += $tax_amount;
			}
		}

		$wc_cart->set_shipping_tax( array_sum( $merged_taxes ) );
		$wc_cart->set_shipping_taxes( $merged_taxes );
		$wc_cart->set_shipping_total( array_sum( wp_list_pluck( $shipping_methods, 'cost' ) ) );

		if ( wc_prices_include_tax() ) {
			$wc_cart->set_shipping_total( (float) $wc_cart->get_shipping_total( 'edit' ) - (float) $wc_cart->get_shipping_tax( 'edit' ) );
		}

		return $shipping_methods;
	}

	/**
	 * Sets WooCommerce Cart totals.
	 *
	 * @param mixed    $response Stripe Calculate Tax API response object.
	 * @param \WC_Cart $wc_cart WooCommerce Cart.
	 * @param string   $currency Currency.
	 *
	 * @return void
	 * @throws Exception In case of any Exception.
	 */
	public static function calculate_cart_totals( $response, $wc_cart, string $currency ) {
		$wc_cart->set_total( self::get_denormalized_amount( $response->amount_total, $currency ) + $wc_cart->get_fee_total() + self::get_cart_or_order_not_taxable_shipping_total( $wc_cart ) );
		$wc_cart->set_total_tax( self::get_denormalized_amount( $response->tax_amount_exclusive + $response->tax_amount_inclusive, $currency ) );
	}

	/**
	 * Get Stripe Tax rates array from Stripe Tax Calculation API response.
	 *
	 * @param mixed                  $response Response.
	 * @param \WC_Order_Item_Product $wc_order_item WC Order items.
	 *
	 * @return array prepared for Woocommerce tax rates from Stripe Tax Calculation API response rates
	 */
	public static function get_wc_rates_array_from_response_for_item( $response, $wc_order_item = false ): array {
		$new_item_tax_rates = array();
		foreach ( $response->line_items->data as $datum ) {
			if ( $wc_order_item ) {
				$product      = $wc_order_item->get_product();
				$item_id      = $wc_order_item->get_id();
				$product_name = $product->get_name() . ( (int) $item_id ? ( '#' . $item_id ) : '' );
			}
			if ( ! $wc_order_item || $datum->reference === $product_name ) {
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
							'rate'      => (float) $rate_percentage,
							'label'     => $rate_name,
							'shipping'  => 'no',
							'compound'  => 'no',
							'inclusive' => 'inclusive' === $datum->tax_behavior,
							'amount'    => $tax_breakdown->amount,
						);
					} else {
						$new_item_tax_rates[ $rate_key ]['rate']   += (float) $rate_percentage;
						$new_item_tax_rates[ $rate_key ]['amount'] += $tax_breakdown->amount;
					}
				}
			}
		}

		return $new_item_tax_rates;
	}

	/**
	 * Normalize amount according Stripe's currency rules
	 *
	 * @param float  $amount Amount.
	 * @param string $currency Currency.
	 *
	 * @return int
	 * @throws Exception If data is not correct.
	 * @see https://stripe.com/docs/currencies
	 * @see CalculateTax::get_denormalized_amount()
	 */
	public static function get_normalized_amount( float $amount, string $currency ): int {
		Validate::validate_currency( $currency );
		if ( StripeTaxPluginHelper::is_currency_zero_decimal( $currency ) ) {
			return (int) $amount;
		}
		if ( StripeTaxPluginHelper::is_currency_three_decimal( $currency ) ) {
			return (int) ( round( $amount * 100 ) * 10 );
		}
		if ( StripeTaxPluginHelper::is_currency_special_case_round_and_multiply_100( $currency ) ) {
			return (int) ( round( $amount ) * 100 );
		}

		return (int) round( $amount * 100 );
	}

	/**
	 * Makes reversal conversion from normalized amount
	 *
	 * @param float  $amount Amount.
	 * @param string $currency Currency.
	 *
	 * @return float
	 * @throws Exception If data is not correct.
	 * @see https://stripe.com/docs/currencies
	 * @see CalculateTax::get_normalized_amount()
	 */
	public static function get_denormalized_amount( float $amount, string $currency ): float {
		Validate::validate_currency( $currency );
		if ( StripeTaxPluginHelper::is_currency_zero_decimal( $currency ) ) {
			return $amount;
		}
		if ( StripeTaxPluginHelper::is_currency_three_decimal( $currency ) ) {
			return $amount / 1000.0;
		}

		return $amount / 100.0;
	}

	/**
	 * Create Stripe Tax Calculation API call
	 *
	 * @return \Stripe\StripeTaxForWooCommerce\SDK\lib\Tax\Calculation|object
	 * @throws Exception If Response data is not correct.
	 * @see https://stripe.com/docs/api/tax/calculations/create
	 */
	protected function get_from_api_call(): object {
		$stripe_client = $this->get_stripe_client( $this->api_key );

		$request = $this->request;

		$response = $stripe_client->tax->calculations->create( $request );

		if ( ! is_object( $response ) || ! isset( $response->object ) || 'tax.calculation' !== $response->object ) {
			throw new Exception( esc_html__( 'Unknown error', 'stripe-tax-for-woocommerce' ) );
		}

		return $response;
	}

	/**
	 * Check if Stripe Tax Calculation expired.
	 * We use 30 seconds magic number to be sure it will not expire during script execution.
	 * We assume entire site request will be handled not more than in 30 seconds
	 *
	 * @param mixed $response Response.
	 *
	 * @return bool
	 * @see https://stripe.com/docs/api/tax/calculations/object#tax_calculation_object-expires_at
	 */
	protected function is_expired( $response ): bool {
		if ( time() > ( $response->expires_at - 30 ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Deletes Stripe Tax Calculation from database.
	 * It is used if Stripe Tax Transaction created from Tax Calculation.
	 *
	 * @return void
	 * @see https://stripe.com/docs/api/tax/transactions/create_from_calculation#tax_transaction_create-calculation
	 */
	public function delete() {
		$items = wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );
		if ( is_array( $items ) || array_key_exists( $this->md5_concat, $items ) ) {
			unset( $items[ $this->md5_concat ] );
			wp_cache_set( self::CACHE_KEY, $items, self::CACHE_GROUP );
		}
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE %i = %s AND %i = %s AND %i = %s AND %i = %s',
				array(
					self::TABLE_NAME,
					'calculate_tax_md5',
					$this->calculate_tax_md5,
					'tax_registrations_md5',
					$this->tax_registrations_md5,
					'tax_settings_md5',
					$this->tax_settings_md5,
					'api_key_md5',
					$this->api_key_md5,
				)
			)
		);
	}

	/**
	 * Gets Stripe Tax Calculation from WordPress object cache (if exists) to reduce database requests
	 *
	 * @return mixed|null
	 */
	protected function get_from_object_cache() {
		$items = wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );

		if ( ! is_array( $items ) || ! array_key_exists( $this->md5_concat, $items ) ) {
			return null;
		}

		if ( $this->is_expired( $items[ $this->md5_concat ] ) ) {
			unset( $items[ $this->md5_concat ] );
			wp_cache_set( self::CACHE_KEY, $items, self::CACHE_GROUP );

			return null;
		}

		return $items[ $this->md5_concat ];
	}

	/**
	 * Sets Tax Calculation to WordPress object cache
	 *
	 * @param mixed $response Tax Calculation response.
	 *
	 * @return void
	 */
	protected function set_to_object_cache( $response ) {
		$cache = wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		$cache[ $this->md5_concat ] = $response;

		wp_cache_set( self::CACHE_KEY, $cache, self::CACHE_GROUP );
	}

	/**
	 * Gets Stripe Tax Calculation from database
	 *
	 * @return object|null
	 */
	protected function get_from_db() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT %i, %i FROM %i WHERE %i = %s AND %i = %s AND %i = %s AND %i = %s',
				array(
					'time',
					'response',
					self::TABLE_NAME,
					'calculate_tax_md5',
					$this->calculate_tax_md5,
					'tax_registrations_md5',
					$this->tax_registrations_md5,
					'tax_settings_md5',
					$this->tax_settings_md5,
					'api_key_md5',
					$this->api_key_md5,
				)
			),
			ARRAY_A
		);

		if ( empty( $results ) ) {
			return null;
		}

		$response = json_decode( $results['response'] );
		if ( ! $response || $this->is_expired( $response ) ) {
			return null;
		}

		return $response;
	}

	/**
	 * Save or update Stripe Tax Calculation into database
	 *
	 * @param object $response Response.
	 *
	 * @return void
	 */
	protected function set_to_db( $response ) {
		global $wpdb;
		$time = time();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (%i, %i, %i, %i, %i, %i, %i) VALUES (%d, %s, %s, %s, %s, %s, %s) ON DUPLICATE KEY UPDATE %i = %d, %i = %s',
				array(
					self::TABLE_NAME,
					'time',
					'calculate_tax_md5',
					'tax_registrations_md5',
					'tax_settings_md5',
					'api_key_md5',
					'request',
					'response',
					$time,
					$this->calculate_tax_md5,
					$this->tax_registrations_md5,
					$this->tax_settings_md5,
					$this->api_key_md5,
					wp_json_encode( $this->request ),
					wp_json_encode( $response ),
					'time',
					$time,
					'response',
					wp_json_encode( $response ),
				)
			)
		);
	}

	/**
	 * Gets response from Create Tax Calculation API
	 * Function will check in object cache and return response from it
	 * If nothing found in WordPress object cache - it will return from database
	 * If nothing found in cache it will call Create Tax calculation api method
	 * Also any cache can be skipped using appropriate method parameter
	 *
	 * @param bool $skip_object_cache Skip object cache.
	 * @param bool $skip_database_cache Skip database cache.
	 *
	 * @return mixed|object|Calculation
	 * @throws Exception If data is not correct.
	 */
	public function get_response( $skip_object_cache = false, $skip_database_cache = false ) {
		if ( ! $skip_object_cache ) {
			$response = $this->get_from_object_cache();
			if ( $response ) {
				return $response;
			}
		}
		if ( ! $skip_database_cache ) {
			$response = $this->get_from_db();
			if ( $response ) {
				$this->set_to_object_cache( $response );

				return $response;
			}
		}
		$response = $this->get_from_api_call();

		if ( $response ) {
			$this->set_to_db( $response );
			$this->set_to_object_cache( $response );
		}

		return $response;
	}

	/**
	 * Returns shipping tax in Woocommerce Cart format.
	 *
	 * @param string     $currency Currency for proper Stripe to WooCommerce format.
	 * @param float|null $denormalized_shipping_tax Currency for proper Stripe to WooCommerce format.
	 *
	 * @throws Exception In case of any exception occurs.
	 */
	public function get_shipping_cart_item_tax( string $currency, ?float $denormalized_shipping_tax = null ): array {
		$response              = $this->get_response();
		$shipping_amount       = $response->shipping_cost->amount;
		$shipping_tax          = $response->shipping_cost->amount_tax;
		$tax_included_in_price = ( 'inclusive' === $response->shipping_cost->tax_behavior );
		if ( is_null( $denormalized_shipping_tax ) ) {
			$denormalized_shipping_tax = self::get_denormalized_amount( $shipping_tax, $currency );
		}

		// Rate percentage not used for any calculations, but added for compatibility with other tax rates.
		if ( $tax_included_in_price ) {
			$percentage_decimal = (float) $shipping_tax / (float) ( $shipping_amount - $shipping_tax ) * 100.0;
		} else {
			$percentage_decimal = (float) $shipping_tax / (float) $shipping_amount * 100.0;
		}

		$rate_id = 'stripe_tax_for_woocommerce__shipping_tax__' . $percentage_decimal . '__Shipping Tax';

		$shipping_cart_item_tax = array( $rate_id => $denormalized_shipping_tax );

		return $shipping_cart_item_tax;
	}

	/**
	 * Returns shipping tax in Woocommerce Order Item Tax format.
	 *
	 * @param string     $currency Currency for proper Stripe to WooCommerce format.
	 * @param float|null $denormalized_shipping_tax Currency for proper Stripe to WooCommerce format.
	 *
	 * @return StripeOrderItemTax
	 * @throws Exception In case of any exception occurs.
	 */
	public function get_shipping_order_item_tax( string $currency, ?float $denormalized_shipping_tax = null ): StripeOrderItemTax {
		$response              = $this->get_response();
		$shipping_amount       = $response->shipping_cost->amount;
		$shipping_tax          = $response->shipping_cost->amount_tax;
		$tax_included_in_price = ( 'inclusive' === $response->shipping_cost->tax_behavior );
		if ( is_null( $denormalized_shipping_tax ) ) {
			$denormalized_shipping_tax = self::get_denormalized_amount( $shipping_tax, $currency );
		}

		// Rate percentage not used for any calculations, but added for compatibility with other tax rates.
		if ( $tax_included_in_price ) {
			$percentage_decimal = (float) $shipping_tax / (float) ( $shipping_amount - $shipping_tax ) * 100.0;
		} else {
			$percentage_decimal = (float) $shipping_tax / (float) $shipping_amount * 100.0;
		}

		$rate_id = 'stripe_tax_for_woocommerce__shipping_tax__' . $percentage_decimal . '__Shipping Tax';

		$shipping_order_item_tax = new StripeOrderItemTax();
		$shipping_order_item_tax->set_rate( $rate_id );
		$shipping_order_item_tax->set_tax_total( $denormalized_shipping_tax );

		return $shipping_order_item_tax;
	}

	/**
	 * Returns shipping tax in Woocommerce raw tax data format.
	 *
	 * @param string     $currency Currency for proper Stripe to WooCommerce format.
	 * @param float|null $denormalized_shipping_tax Currency for proper Stripe to WooCommerce format.
	 *
	 * @return array
	 * @throws Exception In case of any exception occurs.
	 */
	public function get_shipping_order_item_raw_tax_data( string $currency, ?float $denormalized_shipping_tax = null ): array {
		$response              = $this->get_response();
		$shipping_amount       = $response->shipping_cost->amount;
		$shipping_tax          = $response->shipping_cost->amount_tax;
		$tax_included_in_price = ( 'inclusive' === $response->shipping_cost->tax_behavior );
		if ( is_null( $denormalized_shipping_tax ) ) {
			$denormalized_shipping_tax = self::get_denormalized_amount( $shipping_tax, $currency );
		}

		// Rate percentage not used for any calculations, but added for compatibility with other tax rates.
		if ( $tax_included_in_price ) {
			$percentage_decimal = (float) $shipping_tax / (float) ( $shipping_amount - $shipping_tax ) * 100.0;
		} else {
			$percentage_decimal = (float) $shipping_tax / (float) $shipping_amount * 100.0;
		}

		$rate_id = 'stripe_tax_for_woocommerce__shipping_tax__' . $percentage_decimal . '__Shipping Tax';

		return array( 'total' => array( $rate_id => $denormalized_shipping_tax ) );
	}

	/**
	 * Generate html to render tax id's fields in user profile page
	 *
	 * @param int    $counter Counter, if needed to set array key to be sent from front-end.
	 * @param string $selected Selected <option> of the <select> html element (Tax ID type).
	 * @param string $value Tax ID value.
	 *
	 * @return false|string
	 * @see https://stripe.com/docs/api/tax/calculations/create#calculate_tax-customer_details-tax_ids
	 */
	public static function get_tax_ids_admin_html( int $counter = - 1, string $selected = '', string $value = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		ob_start();

		return ob_get_clean();
	}

	/**
	 * Retrieves Tax IDs from POST request and reduces them to not more than 5
	 *
	 * @see https://stripe.com/docs/api/tax/calculations/create#calculate_tax-customer_details-tax_ids
	 * @return array
	 */
	public static function get_prepared_posted_tax_ids(): array {
		$user_id = get_current_user_id();

		check_admin_referer( 'update-user_' . $user_id );
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'update-user_' . $user_id ) ) {
			exit;
		}

		if ( ! isset( $_POST['stripe_tax_for_woocommerce_tax_id'] ) || ! is_array( $_POST['stripe_tax_for_woocommerce_tax_id'] ) ) {
			return array();
		}
		$prepared = array();
		foreach ( array_map( 'sanitize_text_field', wp_unslash( $_POST['stripe_tax_for_woocommerce_tax_id'] ) ) as $key => $value ) {
			$new_value_value = trim( sanitize_text_field( wp_unslash( $_POST['stripe_tax_for_woocommerce_tax_id_value'][ $key ] ?? '' ) ) );
			if ( $new_value_value ) {
				$prepared[ $value ] = $new_value_value;
			}
		}

		return array_slice( $prepared, 0, 5, true );
	}

	/**
	 * Prepares Tax IDs for API call. It is needed to remove country code before send to API.
	 *
	 * @param array<string, string> $tax_ids Tax_Ids.
	 *
	 * @return array
	 * @see StripeTaxPluginHelper::TAX_IDS
	 * @see https://stripe.com/docs/api/tax/calculations/create#calculate_tax-customer_details-tax_ids
	 */
	public static function convert_tax_ids_for_api( $tax_ids ): array {
		if ( ! is_array( $tax_ids ) ) {
			return array();
		}
		$for_api = array();
		$counter = 0;
		foreach ( $tax_ids as $key => $value ) {
			$for_api[ $counter ]          = array();
			$for_api[ $counter ]['type']  = substr( $key, 3 );
			$for_api[ $counter ]['value'] = $value;
			++$counter;
		}

		return $for_api;
	}
}
