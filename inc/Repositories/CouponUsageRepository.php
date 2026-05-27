<?php
/**
 * Repository for wpems_coupon_usage audit ledger.
 *
 * Append-only ledger of coupon redemptions. Supports per-user
 * usage counting and soft-void on cancel.
 *
 * @package WPEMS\Repositories
 * @since   3.0.0
 */

namespace WPEMS\Repositories;

defined( 'ABSPATH' ) || exit;

use WPEMS\Tables\TableNames;

/**
 * Coupon usage repository.
 */
class CouponUsageRepository {

	/**
	 * Explicit column list for coupon usage reads.
	 *
	 * @var string[]
	 */
	private const SELECT_COLUMNS = array(
		'id',
		'coupon_id',
		'coupon_code',
		'user_id',
		'booking_id',
		'discount_applied',
		'used_at_gmt',
		'voided_at_gmt',
		'void_reason',
	);

	/**
	 * Record a coupon redemption.
	 *
	 * UNIQUE (coupon_id, booking_id) makes this idempotent.
	 *
	 * @param int      $coupon_id        Coupon ID.
	 * @param string   $coupon_code      Coupon code snapshot.
	 * @param int      $booking_id       Booking ID.
	 * @param int|null $user_id          User ID (null for guests).
	 * @param string   $discount_applied Discount amount (4-dp string).
	 *
	 * @return int Usage row ID.
	 */
	public function record_usage( int $coupon_id, string $coupon_code, int $booking_id, ?int $user_id, string $discount_applied ): int {
		global $wpdb;

		$table = TableNames::coupon_usage();
		$now   = gmdate( 'Y-m-d H:i:s' );

		$data = array(
			'coupon_id'        => $coupon_id,
			'coupon_code'      => $coupon_code,
			'user_id'          => $user_id,
			'booking_id'       => $booking_id,
			'discount_applied' => $discount_applied,
			'used_at_gmt'      => $now,
		);

		$wpdb->insert( $table, $data );

		if ( (int) $wpdb->insert_id > 0 ) {
			return (int) $wpdb->insert_id;
		}

		// Duplicate key — look up existing.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE coupon_id = %d AND booking_id = %d LIMIT 1",
				$coupon_id,
				$booking_id
			)
		);

		return (int) $existing;
	}

	/**
	 * Soft-void all usage rows for a booking.
	 *
	 * Only touches non-voided rows (idempotent).
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $reason     Void reason.
	 *
	 * @return bool True if at least one row was voided.
	 */
	public function void_usage_for_booking( int $booking_id, string $reason ): bool {
		if ( $booking_id <= 0 ) {
			return false;
		}

		global $wpdb;

		$table = TableNames::coupon_usage();
		$now   = gmdate( 'Y-m-d H:i:s' );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET voided_at_gmt = %s, void_reason = %s
				WHERE booking_id = %d AND voided_at_gmt IS NULL",
				$now,
				$reason,
				$booking_id
			)
		);

		return (int) $wpdb->rows_affected > 0;
	}

	/**
	 * Count non-voided usage for a coupon.
	 *
	 * @param int $coupon_id Coupon ID.
	 *
	 * @return int
	 */
	public function count_coupon_usage( int $coupon_id ): int {
		if ( $coupon_id <= 0 ) {
			return 0;
		}

		global $wpdb;

		$table = TableNames::coupon_usage();

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE coupon_id = %d AND voided_at_gmt IS NULL",
				$coupon_id
			)
		);
	}

	/**
	 * Count non-voided usage for a specific user and coupon.
	 *
	 * Guest checkouts (user_id <= 0) always return 0.
	 *
	 * @param int $coupon_id Coupon ID.
	 * @param int $user_id   User ID.
	 *
	 * @return int
	 */
	public function count_user_usage( int $coupon_id, int $user_id ): int {
		if ( $user_id <= 0 ) {
			return 0;
		}

		if ( $coupon_id <= 0 ) {
			return 0;
		}

		global $wpdb;

		$table = TableNames::coupon_usage();

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE coupon_id = %d AND user_id = %d AND voided_at_gmt IS NULL",
				$coupon_id,
				$user_id
			)
		);
	}

	/**
	 * Find usage row for a booking.
	 *
	 * @param int $booking_id Booking ID.
	 *
	 * @return array|null Associative array or null.
	 */
	public function find_for_booking( int $booking_id ): ?array {
		if ( $booking_id <= 0 ) {
			return null;
		}

		global $wpdb;

		$table   = TableNames::coupon_usage();
		$columns = implode( ', ', self::SELECT_COLUMNS );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT {$columns} FROM {$table} WHERE booking_id = %d LIMIT 1",
				$booking_id
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}
}
