<?php
/**
 * Repository for wpems_payment_sync_queue table.
 *
 * Cron polling reads from here; never scans wpems_bookings.
 * One row per booking actively under sync; row is deleted on terminal status.
 *
 * @package WPEMS\Repositories
 * @since   3.0.0
 */

namespace WPEMS\Repositories;

defined( 'ABSPATH' ) || exit;

use DateTimeImmutable;
use WPEMS\Tables\TableNames;

/**
 * Payment sync queue repository.
 */
class PaymentSyncQueueRepository {

	/**
	 * Explicit column list for reads from the sync queue.
	 *
	 * @var string[]
	 */
	private const SELECT_COLUMNS = array(
		'booking_id',
		'payment_method',
		'gateway_order_id',
		'next_sync_at_gmt',
		'last_sync_at_gmt',
		'sync_attempts',
		'last_error',
	);

	/**
	 * Enqueue a booking for sync (insert-or-replace).
	 *
	 * @param int               $booking_id       Booking ID.
	 * @param string            $payment_method   Payment method slug.
	 * @param string|null       $gateway_order_id Gateway order ID.
	 * @param DateTimeImmutable $next             Next sync time (UTC).
	 *
	 * @return void
	 */
	public function enqueue( int $booking_id, string $payment_method, ?string $gateway_order_id, DateTimeImmutable $next ): void {
		if ( $booking_id <= 0 ) {
			return;
		}

		global $wpdb;

		$table = TableNames::payment_sync_queue();

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (booking_id, payment_method, gateway_order_id, next_sync_at_gmt, sync_attempts)
				VALUES (%d, %s, %s, %s, 0)
				ON DUPLICATE KEY UPDATE
					payment_method   = VALUES(payment_method),
					gateway_order_id = VALUES(gateway_order_id),
					next_sync_at_gmt = VALUES(next_sync_at_gmt),
					last_error       = NULL",
				$booking_id,
				$payment_method,
				$gateway_order_id,
				$next->format( 'Y-m-d H:i:s' )
			)
		);
	}

	/**
	 * Return rows ready for polling (next_sync_at_gmt <= now).
	 *
	 * @param int $limit Maximum rows to return.
	 *
	 * @return array[] Associative arrays.
	 */
	public function dequeue_due( int $limit ): array {
		if ( $limit <= 0 ) {
			return array();
		}

		global $wpdb;

		$table = TableNames::payment_sync_queue();
		$now   = gmdate( 'Y-m-d H:i:s' );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT booking_id, payment_method, gateway_order_id, sync_attempts, last_sync_at_gmt
				FROM {$table}
				WHERE next_sync_at_gmt <= %s
				ORDER BY next_sync_at_gmt ASC
				LIMIT %d",
				$now,
				$limit
			),
			ARRAY_A
		);

		return (array) $rows;
	}

	/**
	 * Record a sync attempt, updating next_sync_at and error.
	 *
	 * @param int               $booking_id Booking ID.
	 * @param DateTimeImmutable $next       Next sync time (UTC).
	 * @param string|null       $error      Error message or null on success.
	 *
	 * @return void
	 */
	public function update_attempt( int $booking_id, DateTimeImmutable $next, ?string $error = null ): void {
		if ( $booking_id <= 0 ) {
			return;
		}

		global $wpdb;

		$table = TableNames::payment_sync_queue();
		$now   = gmdate( 'Y-m-d H:i:s' );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET last_sync_at_gmt = %s,
					next_sync_at_gmt = %s,
					sync_attempts    = sync_attempts + 1,
					last_error       = %s
				WHERE booking_id = %d",
				$now,
				$next->format( 'Y-m-d H:i:s' ),
				$error,
				$booking_id
			)
		);
	}

	/**
	 * Remove a booking from the sync queue (terminal status reached).
	 *
	 * @param int $booking_id Booking ID.
	 *
	 * @return void
	 */
	public function remove( int $booking_id ): void {
		if ( $booking_id <= 0 ) {
			return;
		}

		global $wpdb;

		$wpdb->delete( TableNames::payment_sync_queue(), array( 'booking_id' => $booking_id ) );
	}

	/**
	 * Get a single sync queue entry.
	 *
	 * @param int $booking_id Booking ID.
	 *
	 * @return array|null Associative array or null.
	 */
	public function get( int $booking_id ): ?array {
		if ( $booking_id <= 0 ) {
			return null;
		}

		global $wpdb;

		$table = TableNames::payment_sync_queue();

		$columns = implode( ', ', self::SELECT_COLUMNS );
		$row     = $wpdb->get_row(
			$wpdb->prepare( "SELECT {$columns} FROM {$table} WHERE booking_id = %d LIMIT 1", $booking_id ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Count entries due for sync (admin dashboard widget).
	 *
	 * @return int
	 */
	public function count_pending(): int {
		global $wpdb;

		$table = TableNames::payment_sync_queue();
		$now   = gmdate( 'Y-m-d H:i:s' );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE next_sync_at_gmt <= %s",
				$now
			)
		);
	}
}
