<?php
/**
 * Provide error renderer functionality.
 *
 * @package Stripe\StripeTaxForWooCommerce\WooCommerce
 */

namespace Stripe\StripeTaxForWooCommerce\WooCommerce;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

/**
 * Class for storing and rendering errors
 */
class ErrorRenderer {

	/**
	 * Error message id.
	 *
	 * @var string Id of error message.
	 */
	public $message_id = '';

	/**
	 * Message.
	 *
	 * @var string Untranslated error message.
	 */
	public $message = '';

	/**
	 * Type of error (means "success", "warning", "error" etc.).
	 *
	 * @var string Type of error.
	 */
	public $type = '';

	/**
	 * Array of error objects.
	 *
	 * @var array
	 */
	protected static $error_objects = array();

	/**
	 * Constructor for ErrorRenderer.
	 *
	 * @param string $message_id Id of error.
	 * @param string $message Untranslated error message.
	 * @param string $type Type of error.
	 */
	public function __construct( string $message_id, string $message, string $type ) {
		$this->message_id                     = $message_id;
		$this->message                        = $message;
		$this->type                           = $type;
		static::$error_objects[ $message_id ] = $this;
	}

	/**
	 * Sets error object.
	 *
	 * @param string $message_id Message id.
	 * @param string $message Message.
	 * @param string $type Type.
	 *
	 * @return void
	 */
	public static function set_error_object( string $message_id, string $message, string $type ) {
		if ( empty( static::$error_objects ) ) {
			\WC_Admin_Settings::add_error( __( 'An error occurred while saving settings. Specific details can be found further down the page', 'stripe-tax-for-woocommerce' ) );
		}
		static::$error_objects[ $message_id ] = new static( $message_id, $message, $type );
	}

	/**
	 * Gets error object.
	 *
	 * @param string $message_id Id of error.
	 *
	 * @return self Error object
	 */
	public static function get_error_object( string $message_id ): self {
		if ( ! array_key_exists( $message_id, static::$error_objects ) ) {
			return new static( $message_id, '', '' );
		}

		return static::$error_objects[ $message_id ];
	}

	/**
	 * Gets rendered error.
	 *
	 * @param string $message_id Id of error.
	 * @param bool   $allow_safe_html Should HTML be allowed in error message.
	 *
	 * @return string
	 */
	public static function get_rendered_error( string $message_id, bool $allow_safe_html = false ): string {
		$error_object = static::get_error_object( $message_id );
		if ( '' === $error_object->message ) {
			return '';
		}

		return '<span class="stripe_tax_for_woocommerce_message_span_id_' . esc_attr( $error_object->message_id ) . '" id="stripe_tax_for_woocommerce_message_id_"> </span>'
				. '<div class="stripe_tax_for_woocommerce_message stripe_tax_for_woocommerce_message_' . esc_attr( $error_object->type ) . '" id="stripe_tax_for_woocommerce_message_id_' . esc_attr( $error_object->message_id ) . '">'
				. '<p><strong>' . ( $allow_safe_html ? wp_kses( $error_object->message, wp_kses_allowed_html() ) : esc_html( $error_object->message ) ) . '</strong></p>'
				. '</div>';
	}

	/**
	 * Adds a stripe WooCommerce notice.
	 *
	 * @param string $message The message.
	 * @param string $notice_type The type of notice.
	 * @param array  $data Additional data.
	 *
	 * @return void
	 */
	public static function add_stripe_wc_notice( $message, $notice_type = 'success', $data = array() ) {
		$notices        = WC()->session->get( 'wc_notices', array() );
		$notice_message = sprintf(
			/* translators: %s: The notice message */
			__( 'Stripe Tax: %s', 'stripe-tax-for-woocommerce' ),
			$message
		);

		if ( is_array( $notices ) && array_key_exists( $notice_type, $notices ) && is_array( $notices[ $notice_type ] ) ) {
			$messages = array_column( $notices[ $notice_type ], 'notice' );
			if ( in_array( $notice_message, $messages, true ) ) {
				return;
			}
		}

		wc_add_notice(
			$notice_message,
			$notice_type,
			$data
		);
	}
}
