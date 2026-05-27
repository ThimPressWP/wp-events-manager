<?php
/**
 * Creates and upgrades all 9 WPEMS custom tables via dbDelta().
 *
 * Idempotent — safe to run on every plugin activation and on schema-version bumps.
 *
 * @package WPEMS\Tables
 * @since   3.0.0
 */

namespace WPEMS\Tables;

defined( 'ABSPATH' ) || exit;

use InvalidArgumentException;

/**
 * Schema manager for all WPEMS custom tables.
 */
final class SchemaManager {

	/**
	 * Current schema version. Bumped on every structural migration.
	 */
	const SCHEMA_VERSION = '1.0.0';

	/**
	 * Option key for the persisted schema version.
	 */
	const OPTION_KEY = 'wpems_schema_version';

	/**
	 * Create all tables and set the schema version.
	 *
	 * @return void
	 */
	public static function install(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $GLOBALS['wpdb']->get_charset_collate();

		foreach ( TableNames::all() as $table ) {
			dbDelta( self::get_dbdelta_sql( $table ) . ' ' . $charset_collate . ';' );
		}

		self::set_schema_version( self::SCHEMA_VERSION );
	}

	/**
	 * Get CREATE TABLE SQL in the form expected by WordPress dbDelta().
	 *
	 * dbDelta() extracts the table name with `CREATE TABLE ([^ ]*)`, so the
	 * `IF NOT EXISTS` form generated for inspection would be parsed as table
	 * name `IF`.
	 *
	 * @param string $table Full table name (from TableNames).
	 *
	 * @return string
	 */
	private static function get_dbdelta_sql( string $table ): string {
		$sql    = self::get_create_table_sql( $table );
		$prefix = 'CREATE TABLE IF NOT EXISTS ';

		if ( 0 === strpos( $sql, $prefix ) ) {
			return 'CREATE TABLE ' . substr( $sql, strlen( $prefix ) );
		}

		return $sql;
	}

	/**
	 * Upgrade tables if the schema version has changed.
	 *
	 * @return void
	 */
	public static function upgrade(): void {
		if ( self::get_schema_version() === self::SCHEMA_VERSION ) {
			return;
		}

		self::install();
	}

	/**
	 * Get the persisted schema version.
	 *
	 * @return string
	 */
	public static function get_schema_version(): string {
		return (string) get_option( self::OPTION_KEY, '0.0.0' );
	}

	/**
	 * Set the persisted schema version.
	 *
	 * @param string $version Version string to persist.
	 *
	 * @return void
	 */
	public static function set_schema_version( string $version ): void {
		update_option( self::OPTION_KEY, $version, false );
	}

	/**
	 * Check whether a table exists in the database.
	 *
	 * @param string $table Full table name.
	 *
	 * @return bool
	 */
	public static function table_exists( string $table ): bool {
		global $wpdb;
		$sql = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table );

		return strtolower( (string) $wpdb->get_var( $sql ) ) === strtolower( $table );
	}

	/**
	 * Get the CREATE TABLE SQL for a given table.
	 *
	 * Returns the bare `CREATE TABLE IF NOT EXISTS …` statement without
	 * trailing charset/collate or semicolon — the caller appends those.
	 *
	 * @param string $table Full table name (from TableNames).
	 *
	 * @return string
	 *
	 * @throws InvalidArgumentException If the table is not recognized.
	 */
	public static function get_create_table_sql( string $table ): string {
		// Map full table name to its suffix for the switch.
		$known = array(
			TableNames::bookings()             => 'bookings',
			TableNames::booking_meta()         => 'booking_meta',
			TableNames::event_inventory()      => 'event_inventory',
			TableNames::coupons()              => 'coupons',
			TableNames::coupon_events()        => 'coupon_events',
			TableNames::coupon_usage()         => 'coupon_usage',
			TableNames::payment_events()       => 'payment_events',
			TableNames::payment_transactions() => 'payment_transactions',
			TableNames::payment_sync_queue()   => 'payment_sync_queue',
		);

		if ( ! isset( $known[ $table ] ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Unknown WPEMS table: %s', $table )
			);
		}

		switch ( $known[ $table ] ) {

			case 'bookings':
				return "CREATE TABLE IF NOT EXISTS {$table} (
				    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				    legacy_post_id BIGINT UNSIGNED NULL,
				    event_id BIGINT UNSIGNED NOT NULL,
				    user_id BIGINT UNSIGNED NOT NULL,
				    qty INT UNSIGNED NOT NULL DEFAULT 1,
				    subtotal DECIMAL(15,4) NOT NULL DEFAULT 0,
				    discount_total DECIMAL(15,4) NOT NULL DEFAULT 0,
				    tax_rate DECIMAL(7,4) NOT NULL DEFAULT 0,
				    tax_total DECIMAL(15,4) NOT NULL DEFAULT 0,
				    total DECIMAL(15,4) NOT NULL DEFAULT 0,
				    currency CHAR(3) NOT NULL DEFAULT '',
				    coupon_id BIGINT UNSIGNED NULL,
				    payment_method VARCHAR(20) NOT NULL DEFAULT '',
				    payment_mode VARCHAR(20) NULL,
				    gateway_order_id VARCHAR(64) NULL,
				    status VARCHAR(20) NOT NULL DEFAULT 'ea-pending',
				    payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
				    hold_expires_at_gmt DATETIME NULL,
				    idempotency_key VARCHAR(64) NULL,
				    created_at_gmt DATETIME NOT NULL,
				    updated_at_gmt DATETIME NOT NULL,
				    PRIMARY KEY  (id),
				    UNIQUE KEY legacy_post_id (legacy_post_id),
				    UNIQUE KEY idempotency_key (idempotency_key),
				    KEY event_status (event_id, status),
				    KEY status_hold_expiry (status, hold_expires_at_gmt),
				    KEY payment_order (payment_method, gateway_order_id),
				    KEY booking_list (created_at_gmt, status, event_id, user_id, payment_method, total)
				)";

			case 'booking_meta':
				return "CREATE TABLE IF NOT EXISTS {$table} (
				    meta_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				    booking_id BIGINT UNSIGNED NOT NULL,
				    meta_key VARCHAR(191) NOT NULL,
				    meta_value LONGTEXT NULL,
				    PRIMARY KEY  (meta_id),
				    KEY booking_meta_key (booking_id, meta_key),
				    KEY meta_key (meta_key)
				)";

			case 'event_inventory':
				return "CREATE TABLE IF NOT EXISTS {$table} (
				    event_id BIGINT UNSIGNED NOT NULL,
				    capacity INT UNSIGNED NOT NULL DEFAULT 0,
				    held_qty INT UNSIGNED NOT NULL DEFAULT 0,
				    confirmed_qty INT UNSIGNED NOT NULL DEFAULT 0,
				    updated_at_gmt DATETIME NOT NULL,
				    PRIMARY KEY  (event_id)
				)";

			case 'coupons':
				return "CREATE TABLE IF NOT EXISTS {$table} (
				    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				    code VARCHAR(50) NOT NULL,
				    description TEXT NULL,
				    discount_type VARCHAR(20) NOT NULL DEFAULT 'percent',
				    percent_value DECIMAL(7,4) NULL,
				    amount_value DECIMAL(15,4) NULL,
				    max_discount_amount DECIMAL(15,4) NULL,
				    applies_to VARCHAR(20) NOT NULL DEFAULT 'all',
				    usage_limit INT UNSIGNED NULL,
				    usage_count INT UNSIGNED NOT NULL DEFAULT 0,
				    usage_limit_per_user INT UNSIGNED NULL,
				    min_order_amount DECIMAL(15,4) NULL,
				    starts_at_gmt DATETIME NULL,
				    expires_at_gmt DATETIME NULL,
				    status VARCHAR(20) NOT NULL DEFAULT 'active',
				    created_at_gmt DATETIME NOT NULL,
				    updated_at_gmt DATETIME NOT NULL,
				    PRIMARY KEY  (id),
				    UNIQUE KEY code (code),
				    KEY status_dates (status, starts_at_gmt, expires_at_gmt),
				    KEY discount_type (discount_type)
				)";

			case 'coupon_events':
				return "CREATE TABLE IF NOT EXISTS {$table} (
				    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				    coupon_id BIGINT UNSIGNED NOT NULL,
				    event_id BIGINT UNSIGNED NOT NULL,
				    PRIMARY KEY  (id),
				    UNIQUE KEY coupon_event (coupon_id, event_id),
				    KEY event_id (event_id)
				)";

			case 'coupon_usage':
				return "CREATE TABLE IF NOT EXISTS {$table} (
				    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				    coupon_id BIGINT UNSIGNED NOT NULL,
				    coupon_code VARCHAR(50) NOT NULL,
				    user_id BIGINT UNSIGNED NULL,
				    booking_id BIGINT UNSIGNED NOT NULL,
				    discount_applied DECIMAL(15,4) NOT NULL,
				    used_at_gmt DATETIME NOT NULL,
				    voided_at_gmt DATETIME NULL,
				    void_reason VARCHAR(191) NULL,
				    PRIMARY KEY  (id),
				    UNIQUE KEY coupon_booking (coupon_id, booking_id),
				    KEY coupon_user_void (coupon_id, user_id, voided_at_gmt),
				    KEY booking_id (booking_id)
				)";

			case 'payment_events':
				return "CREATE TABLE IF NOT EXISTS {$table} (
				    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				    gateway_id VARCHAR(20) NOT NULL,
				    event_id VARCHAR(64) NOT NULL,
				    object_id VARCHAR(64) NULL,
				    event_type VARCHAR(64) NOT NULL,
				    booking_id BIGINT UNSIGNED NULL,
				    status VARCHAR(20) NOT NULL DEFAULT 'processed',
				    payload_hash CHAR(64) NULL,
				    created_at_gmt DATETIME NOT NULL,
				    PRIMARY KEY  (id),
				    UNIQUE KEY gateway_event (gateway_id, event_id),
				    KEY booking_id (booking_id),
				    KEY object_id (object_id)
				)";

			case 'payment_transactions':
				return "CREATE TABLE IF NOT EXISTS {$table} (
				    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				    booking_id BIGINT UNSIGNED NOT NULL,
				    type VARCHAR(32) NOT NULL,
				    gateway_transaction_id VARCHAR(64) NULL,
				    gateway_capture_id VARCHAR(64) NULL,
				    gateway_charge_id VARCHAR(64) NULL,
				    gateway_payment_intent_id VARCHAR(64) NULL,
				    parent_transaction_id BIGINT UNSIGNED NULL,
				    amount DECIMAL(15,4) NOT NULL DEFAULT 0,
				    currency CHAR(3) NOT NULL DEFAULT '',
				    status VARCHAR(20) NOT NULL DEFAULT 'pending',
				    raw_response LONGTEXT NULL,
				    created_at_gmt DATETIME NOT NULL,
				    PRIMARY KEY  (id),
				    KEY booking_id (booking_id),
				    UNIQUE KEY type_txn_id (type, gateway_transaction_id),
				    KEY parent_transaction_id (parent_transaction_id),
				    KEY booking_type_status (booking_id, type, status)
				)";

			case 'payment_sync_queue':
				return "CREATE TABLE IF NOT EXISTS {$table} (
				    booking_id BIGINT UNSIGNED NOT NULL,
				    payment_method VARCHAR(20) NOT NULL DEFAULT '',
				    gateway_order_id VARCHAR(64) NULL,
				    next_sync_at_gmt DATETIME NOT NULL,
				    last_sync_at_gmt DATETIME NULL,
				    sync_attempts INT UNSIGNED NOT NULL DEFAULT 0,
				    last_error TEXT NULL,
				    PRIMARY KEY  (booking_id),
				    KEY next_sync (next_sync_at_gmt),
				    KEY method_next_sync (payment_method, next_sync_at_gmt)
				)";
		}

		// Unreachable — defensive fallback.
		throw new InvalidArgumentException(
			sprintf( 'Unknown WPEMS table: %s', $table )
		);
	}
}
