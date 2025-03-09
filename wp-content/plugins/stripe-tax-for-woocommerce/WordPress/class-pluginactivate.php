<?php
/**
 * Provides plugin activate functionality.
 *
 * @package Stripe\StripeTaxForWooCommerce\WordPress
 */

namespace Stripe\StripeTaxForWooCommerce\WordPress;

// Exit if script started not from WordPress.
defined( 'ABSPATH' ) || exit;

/**
 * Class for each function needs to be called on plugin activate event
 */
class PluginActivate {

	/**
	 * Activate triggered.
	 *
	 * @var bool $activate_triggered Property to keep functions called on plugin activate event only once
	 */
	protected static $activate_triggered;

	/**
	 * Checks if table exists in database
	 *
	 * @param string $table_name Table name.
	 *
	 * @return bool
	 */
	protected static function table_exists( string $table_name ): bool {
		global $wpdb;

		return (bool) (
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->get_var(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					array(
						$table_name,
					)
				)
			)
		);
	}

	/**
	 * Creates table to store plugin options
	 *
	 * @return void
	 */
	protected static function maybe_create_options_table() {
		global $wpdb;
		$table_name = STRIPE_TAX_FOR_WOOCOMMERCE_DB_PREFIX . 'options';
		if ( static::table_exists( $table_name ) ) {
			return;
		}
		// This part is responsible to create the most recent plugin's version of database table schema.
		// When database table schema be changed in any future versions, this part needs to be changed too.
		// This means, only most recent table creation queries must persist here.
		// Migrator logic for updating from one version to another one to be implemented later.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
				'CREATE TABLE %i (%i VARCHAR(191) NOT NULL PRIMARY KEY, %i LONGTEXT NOT NULL)',
				array(
					$table_name,
					'option_name',
					'option_value',
				)
			)
		);
	}

	/**
	 * Creates table to store tax calculations cache
	 *
	 * @return void
	 */
	protected static function maybe_create_tax_calculate_table() {
		global $wpdb;
		$table_name = STRIPE_TAX_FOR_WOOCOMMERCE_DB_PREFIX . 'calculate_tax';
		if ( static::table_exists( $table_name ) ) {
			return;
		}
		// This part is responsible to create the most recent plugin's version of database table schema.
		// When database table schema be changed in any future versions, this part needs to be changed too.
		// This means, only most recent table creation queries must persist here.
		// Migrator logic for updating from one version to another one to be implemented later.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
				'CREATE TABLE %i ( %i BIGINT AUTO_INCREMENT PRIMARY KEY, %i BIGINT NOT NULL, %i CHAR(32) NOT NULL, %i CHAR(32) NOT NULL, %i CHAR(32) NOT NULL, %i CHAR(32) NOT NULL, %i LONGTEXT NOT NULL, %i LONGTEXT NOT NULL, CONSTRAINT %i UNIQUE ( %i, %i, %i ) )',
				array(
					$table_name,
					'id',
					'time',
					'calculate_tax_md5',
					'tax_registrations_md5',
					'tax_settings_md5',
					'api_key_md5',
					'request',
					'response',
					'calculate_tax_uindex',
					'calculate_tax_md5',
					'tax_registrations_md5',
					'tax_settings_md5',
				)
			)
		);
	}

	/**
	 * Creates table to store tax codes cache
	 *
	 * @return void
	 */
	protected static function maybe_create_tax_codes_table() {
		global $wpdb;
		$table_name = STRIPE_TAX_FOR_WOOCOMMERCE_DB_PREFIX . 'tax_codes';
		if ( static::table_exists( $table_name ) ) {
			return;
		}
		// This part is responsible to create the most recent plugin's version of database table schema.
		// When database table schema be changed in any future versions, this part needs to be changed too.
		// This means, only most recent table creation queries must persist here.
		// Migrator logic for updating from one version to another one to be implemented later.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
				'CREATE TABLE %i (%i CHAR(13) NOT NULL PRIMARY KEY, %i TEXT NOT NULL, %i LONGTEXT NOT NULL)',
				array(
					$table_name,
					'id',
					'name',
					'description',
				)
			)
		);
	}

	/**
	 * Creates table to store additional product properties
	 *
	 * @return void
	 */
	protected static function maybe_create_products_table() {
		global $wpdb;
		$table_name = STRIPE_TAX_FOR_WOOCOMMERCE_DB_PREFIX . 'products';
		if ( static::table_exists( $table_name ) ) {
			return;
		}
		// This part is responsible to create the most recent plugin's version of database table schema.
		// When database table schema be changed in any future versions, this part needs to be changed too.
		// This means, only most recent table creation queries must persist here.
		// Migrator logic for updating from one version to another one to be implemented later.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
				'CREATE TABLE %i (%i BIGINT UNSIGNED NOT NULL PRIMARY KEY, %i CHAR(13) DEFAULT \'\' NOT NULL, %i ENUM (\'\', \'exclusive\', \'inclusive\') default \'\' NOT NULL)',
				array(
					$table_name,
					'product_id',
					'tax_code',
					'tax_behavior',
				)
			)
		);
	}

	/**
	 * Creates tax transactions table if not exist
	 *
	 * @return void
	 */
	protected static function maybe_create_tax_transactions_table() {
		global $wpdb;
		$table_name = STRIPE_TAX_FOR_WOOCOMMERCE_DB_PREFIX . 'tax_transactions';
		if ( static::table_exists( $table_name ) ) {
			return;
		}
		// This part is responsible to create the most recent plugin's version of database table schema.
		// When database table schema be changed in any future versions, this part needs to be changed too.
		// This means, only most recent table creation queries must persist here.
		// Migrator logic for updating from one version to another one to be implemented later.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
				'CREATE TABLE %i (%i BIGINT UNSIGNED NOT NULL PRIMARY KEY, %i LONGTEXT NOT NULL, %i LONGTEXT NOT NULL)',
				array(
					$table_name,
					'order_id',
					'tax_calculation',
					'tax_transaction',
				)
			)
		);
	}

	/**
	 * Calls each function to create each needed database table
	 *
	 * @return void
	 */
	protected static function create_necessary_tables() {
		static::maybe_create_options_table();
		static::maybe_create_tax_calculate_table();
		static::maybe_create_tax_codes_table();
		static::maybe_create_products_table();
		static::maybe_create_tax_transactions_table();
	}

	/**
	 * Add plugin activate notices.
	 *
	 * @return void
	 */
	protected static function add_plugin_activate_notices() {
		set_transient( 'stripe_tax_for_woocommerce_activate', true );
	}

	/**
	 * Function called on plugin activate event.
	 * Does nothing if function already called once and $force equals false (default).
	 *
	 * @param bool $force If true - skips check this function already called once, and does everything that it must do during the first call.
	 *
	 * @return void
	 */
	public static function trigger_activate( bool $force = false ) {
		if ( ! empty( static::$activate_triggered ) && ! $force ) {
			return;
		}
		static::$activate_triggered = true;
		static::create_necessary_tables();
		static::add_plugin_activate_notices();
	}
}
