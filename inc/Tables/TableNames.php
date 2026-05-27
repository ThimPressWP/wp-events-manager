<?php
/**
 * Single source of truth for every WPEMS custom table name.
 *
 * Every other class in the project MUST go through this helper —
 * no raw `$wpdb->prefix . 'wpems_…'` strings anywhere else.
 *
 * @package WPEMS\Tables
 * @since   3.0.0
 */

namespace WPEMS\Tables;

defined( 'ABSPATH' ) || exit;

/**
 * Table name registry.
 *
 * All methods are static and read `$GLOBALS['wpdb']->prefix` on each call
 * (do not cache — `switch_to_blog()` can change the prefix at runtime).
 */
final class TableNames {

	/**
	 * Shared table-name prefix appended after `$wpdb->prefix`.
	 */
	const PREFIX = 'wpems_';

	/**
	 * Get the bookings table name.
	 *
	 * @return string Full table name, e.g. `wp_wpems_bookings`.
	 */
	public static function bookings(): string {
		global $wpdb;

		return $wpdb->prefix . self::PREFIX . 'bookings';
	}

	/**
	 * Get the booking meta table name.
	 *
	 * @return string Full table name, e.g. `wp_wpems_booking_meta`.
	 */
	public static function booking_meta(): string {
		global $wpdb;

		return $wpdb->prefix . self::PREFIX . 'booking_meta';
	}

	/**
	 * Get the event inventory table name.
	 *
	 * @return string Full table name, e.g. `wp_wpems_event_inventory`.
	 */
	public static function event_inventory(): string {
		global $wpdb;

		return $wpdb->prefix . self::PREFIX . 'event_inventory';
	}

	/**
	 * Get the coupons table name.
	 *
	 * @return string Full table name, e.g. `wp_wpems_coupons`.
	 */
	public static function coupons(): string {
		global $wpdb;

		return $wpdb->prefix . self::PREFIX . 'coupons';
	}

	/**
	 * Get the coupon events table name.
	 *
	 * @return string Full table name, e.g. `wp_wpems_coupon_events`.
	 */
	public static function coupon_events(): string {
		global $wpdb;

		return $wpdb->prefix . self::PREFIX . 'coupon_events';
	}

	/**
	 * Get the coupon usage table name.
	 *
	 * @return string Full table name, e.g. `wp_wpems_coupon_usage`.
	 */
	public static function coupon_usage(): string {
		global $wpdb;

		return $wpdb->prefix . self::PREFIX . 'coupon_usage';
	}

	/**
	 * Get the payment events table name.
	 *
	 * @return string Full table name, e.g. `wp_wpems_payment_events`.
	 */
	public static function payment_events(): string {
		global $wpdb;

		return $wpdb->prefix . self::PREFIX . 'payment_events';
	}

	/**
	 * Get the payment transactions table name.
	 *
	 * @return string Full table name, e.g. `wp_wpems_payment_transactions`.
	 */
	public static function payment_transactions(): string {
		global $wpdb;

		return $wpdb->prefix . self::PREFIX . 'payment_transactions';
	}

	/**
	 * Get the payment sync queue table name.
	 *
	 * @return string Full table name, e.g. `wp_wpems_payment_sync_queue`.
	 */
	public static function payment_sync_queue(): string {
		global $wpdb;

		return $wpdb->prefix . self::PREFIX . 'payment_sync_queue';
	}

	/**
	 * Get all 9 custom table names.
	 *
	 * @return string[] All table names in canonical order.
	 */
	public static function all(): array {
		return array(
			self::bookings(),
			self::booking_meta(),
			self::event_inventory(),
			self::coupons(),
			self::coupon_events(),
			self::coupon_usage(),
			self::payment_events(),
			self::payment_transactions(),
			self::payment_sync_queue(),
		);
	}
}
