<?php
/**
 * Options storage.
 *
 * @package Stripe\StripeTaxForWooCommerce\WordPress
 */

namespace Stripe\StripeTaxForWooCommerce\WordPress;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

use stdClass;

/**
 * Class used to get, store and delete any options in database
 */
class Options {
	const OPTION_LIVE_MODE_SECRET_KEY           = 'live_mode_secret_key';
	const OPTION_LIVE_MODE_ENABLED              = 'live_mode_enabled';
	const OPTION_LIVE_MODE_ACCOUNT_ID           = 'live_mode_account_id';
	const OPTION_WOOCOMMERCE_CONNECT_LAST_ERROR = 'woocommerce_connect_last_error';
	const OPTION_WOOCOMMERCE_CONNECT_LAST_STATE = 'woocommerce_connect_last_state';
	const CACHE_GROUP                           = 'stripe-tax-for-woocommerce';
	const CACHE_KEY                             = 'options';
	const TABLE_NAME                            = STRIPE_TAX_FOR_WOOCOMMERCE_DB_PREFIX . 'options';

	/**
	 * Initializes the cache.
	 *
	 * @param bool $force_update_from_db Force update from database flag.
	 */
	protected static function init_cache( bool $force_update_from_db = false ) {
		$cache = wp_cache_get( static::CACHE_KEY, static::CACHE_GROUP );
		if ( is_array( $cache ) && ! $force_update_from_db ) {
			return;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$cached = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT %i, %i FROM %i',
				array(
					'option_name',
					'option_value',
					static::TABLE_NAME,
				)
			),
			OBJECT_K
		);
		wp_cache_set( static::CACHE_KEY, $cached, static::CACHE_GROUP );
	}

	/**
	 * Gets from cache.
	 *
	 * @param string $option_name Option name.
	 * @param bool   $force_update_from_db Force update from database flag.
	 */
	protected static function get_from_cache( string $option_name, bool $force_update_from_db = false ): string {
		static::init_cache( $force_update_from_db );
		$cached = wp_cache_get( static::CACHE_KEY, static::CACHE_GROUP );

		return (string) ( array_key_exists( $option_name, $cached ) && isset( $cached[ $option_name ]->option_value ) ? $cached[ $option_name ]->option_value : '' );
	}

	/**
	 * Updates into cache.
	 *
	 * @param string $option_name Option name.
	 * @param string $option_value Option value.
	 * @param bool   $force_update_from_db Force update from database flag.
	 */
	protected static function update_into_cache( string $option_name, string $option_value, bool $force_update_from_db = false ) {
		static::init_cache( $force_update_from_db );
		$cached                               = wp_cache_get( static::CACHE_KEY, static::CACHE_GROUP );
		$cached[ $option_name ]               = new stdClass();
		$cached[ $option_name ]->option_name  = $option_name;
		$cached[ $option_name ]->option_value = $option_value;
		wp_cache_set( static::CACHE_KEY, $cached, static::CACHE_GROUP );
	}

	/**
	 * Deletes an option value from the cache.
	 *
	 * @param string $option_name Option name.
	 * @param bool   $force_update_from_db Force update from database flag.
	 */
	protected static function delete_from_cache( string $option_name, bool $force_update_from_db = false ) {
		static::init_cache( $force_update_from_db );
		$cached = wp_cache_get( static::CACHE_KEY, static::CACHE_GROUP );
		unset( $cached[ $option_name ] );
		wp_cache_set( static::CACHE_KEY, $cached, static::CACHE_GROUP );
	}

	/**
	 * Return option value by option name
	 *
	 * @param string $option_name Option name.
	 * @param bool   $force_update_from_db Force update from database flag.
	 *
	 * @return string Option value
	 */
	public static function get_option( string $option_name, bool $force_update_from_db = false ): string {
		return static::get_from_cache( $option_name, $force_update_from_db );
	}

	/**
	 * Creates new or updates existing option.
	 *
	 * @param string $option_name Option name.
	 * @param string $option_value Option value.
	 * @param bool   $force_update_from_db Force update from database flag.
	 *
	 * @return void
	 */
	public static function update_option( string $option_name, string $option_value, bool $force_update_from_db = false ) {
		global $wpdb;
		$table_name = static::TABLE_NAME;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (%i, %i) VALUES (%s, %s) ON DUPLICATE KEY UPDATE %i = %s',
				array(
					$table_name,
					'option_name',
					'option_value',
					$option_name,
					$option_value,
					'option_value',
					$option_value,
				)
			)
		);
		static::update_into_cache( $option_name, $option_value, $force_update_from_db );
	}

	/**
	 * Deletes option with provided option name.
	 *
	 * @param string $option_name Option name.
	 * @param bool   $force_update_from_db Force update from database flag.
	 *
	 * @return void
	 */
	public static function delete_option( string $option_name, bool $force_update_from_db = false ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM %i WHERE %i = %s',
				array(
					static::TABLE_NAME,
					'option_name',
					$option_name,
				)
			)
		);
		static::delete_from_cache( $option_name, $force_update_from_db );
	}

	/**
	 * Gets live mode secret key from database
	 *
	 * @return string Live mode secret key
	 */
	public static function get_live_mode_key(): string {
		return static::get_option( static::OPTION_LIVE_MODE_SECRET_KEY );
	}

	/**
	 * Gets live mode masked secret key
	 *
	 * @return string Live mode masked secret key
	 */
	public static function get_live_mode_masked_key(): string {
		$key = static::get_live_mode_key();
		if ( '' === $key ) {
			return '';
		}
		if ( mb_strlen( $key ) <= 16 ) {
			return str_repeat( '*', 12 );
		}

		return mb_substr( $key, 0, 8 ) . '...' . mb_substr( $key, - 4 );
	}

	/**
	 * Checks if live mode enabled in Stripe Tax settings
	 *
	 * @return bool True, if live mode enabled, false if not
	 */
	public static function is_live_mode_enabled(): bool {
		return ( static::get_option( static::OPTION_LIVE_MODE_ENABLED ) === '1' );
	}

	/**
	 * Enables or disables live mode, depending on $enable function argument.
	 *
	 * @param bool $enable True, to enable, and false, to disable live mode.
	 *
	 * @return void
	 */
	public static function enable_live_mode( bool $enable ) {
		if ( $enable ) {
			static::update_option( static::OPTION_LIVE_MODE_ENABLED, '1' );

			return;
		}
		static::update_option( static::OPTION_LIVE_MODE_ENABLED, '0' );
	}

	/**
	 * Stripe tax for WooCommerce output rendered select.
	 *
	 * @param array  $select_array Selected array.
	 * @param string $name The name.
	 * @param string $selected Selected.
	 * @param string $id Id.
	 * @param string $class_list Class list.
	 */
	public static function stripe_tax_for_woocommerce_output_rendered_select( array $select_array, string $name, string $selected = '', string $id = '', string $class_list = '' ) {
		if ( count( $select_array ) < 1 ) {
			return;
		}
		echo '<select autocomplete="off" name="' . esc_attr( $name ) . '" class="wc-enhanced-select' . ( ( '' !== $class_list ) ? ' ' . esc_attr( $class_list ) : '' ) . '"'
			. ( ( '' !== $id ) ? ' id="' . esc_attr( $id ) . '"' : '' )

			. '>';
		foreach ( $select_array as $key => $value ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $selected, $key, false ) . '>' . esc_html( $value ) . '</option>';
		}
		echo '</select>';
	}
}
