<?php
/**
 * Repository for wpems_event_inventory table.
 *
 * Atomic capacity management. Every method that mutates inventory MUST
 * use a single conditional UPDATE and check $wpdb->rows_affected.
 * Never SELECT-then-UPDATE.
 *
 * @package WPEMS\Repositories
 * @since   3.0.0
 */

namespace WPEMS\Repositories;

defined( 'ABSPATH' ) || exit;

use WPEMS\Tables\TableNames;

/**
 * Event inventory repository.
 */
class EventInventoryRepository {

	/**
	 * Idempotently create the inventory row before any reservation.
	 *
	 * @param int $event_id Event post ID.
	 * @param int $capacity Total capacity (0 = unlimited).
	 *
	 * @return void
	 */
	public function ensure_row( int $event_id, int $capacity ): void {
		global $wpdb;

		$table = TableNames::event_inventory();
		$now   = gmdate( 'Y-m-d H:i:s' );

		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$table} (event_id, capacity, held_qty, confirmed_qty, updated_at_gmt)
				VALUES (%d, %d, 0, 0, %s)",
				$event_id,
				$capacity,
				$now
			)
		);
	}

	/**
	 * Reserve seats (add to held_qty).
	 *
	 * Single atomic UPDATE. Returns true only if a row was actually updated.
	 *
	 * @param int $event_id Event post ID.
	 * @param int $qty      Quantity to reserve.
	 *
	 * @return bool
	 */
	public function reserve( int $event_id, int $qty ): bool {
		if ( $qty <= 0 ) {
			return false;
		}

		global $wpdb;

		$table = TableNames::event_inventory();
		$now   = gmdate( 'Y-m-d H:i:s' );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET held_qty = held_qty + %d, updated_at_gmt = %s
				WHERE event_id = %d
				  AND ( capacity = 0 OR held_qty + confirmed_qty + %d <= capacity )",
				$qty,
				$now,
				$event_id,
				$qty
			)
		);

		return 1 === (int) $wpdb->rows_affected;
	}

	/**
	 * Release held seats (subtract from held_qty).
	 *
	 * Clamps to zero to avoid negative held_qty.
	 *
	 * @param int $event_id Event post ID.
	 * @param int $qty      Quantity to release.
	 *
	 * @return bool
	 */
	public function release_hold( int $event_id, int $qty ): bool {
		if ( $qty <= 0 ) {
			return false;
		}

		global $wpdb;

		$table = TableNames::event_inventory();
		$now   = gmdate( 'Y-m-d H:i:s' );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET held_qty = GREATEST( CAST(held_qty AS SIGNED) - %d, 0 ),
				    updated_at_gmt = %s
				WHERE event_id = %d AND held_qty >= %d",
				$qty,
				$now,
				$event_id,
				$qty
			)
		);

		return 1 === (int) $wpdb->rows_affected;
	}

	/**
	 * Move qty from held_qty to confirmed_qty atomically.
	 *
	 * @param int $event_id Event post ID.
	 * @param int $qty      Quantity to confirm.
	 *
	 * @return bool
	 */
	public function confirm_hold( int $event_id, int $qty ): bool {
		if ( $qty <= 0 ) {
			return false;
		}

		global $wpdb;

		$table = TableNames::event_inventory();
		$now   = gmdate( 'Y-m-d H:i:s' );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET held_qty = held_qty - %d,
				    confirmed_qty = confirmed_qty + %d,
				    updated_at_gmt = %s
				WHERE event_id = %d AND held_qty >= %d",
				$qty,
				$qty,
				$now,
				$event_id,
				$qty
			)
		);

		return 1 === (int) $wpdb->rows_affected;
	}

	/**
	 * Release confirmed seats (used on full refund).
	 *
	 * @param int $event_id Event post ID.
	 * @param int $qty      Quantity to release.
	 *
	 * @return bool
	 */
	public function release_confirmed( int $event_id, int $qty ): bool {
		if ( $qty <= 0 ) {
			return false;
		}

		global $wpdb;

		$table = TableNames::event_inventory();
		$now   = gmdate( 'Y-m-d H:i:s' );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET confirmed_qty = GREATEST( CAST(confirmed_qty AS SIGNED) - %d, 0 ),
				    updated_at_gmt = %s
				WHERE event_id = %d AND confirmed_qty >= %d",
				$qty,
				$now,
				$event_id,
				$qty
			)
		);

		return 1 === (int) $wpdb->rows_affected;
	}

	/**
	 * Get the available quantity for an event.
	 *
	 * @param int $event_id Event post ID.
	 *
	 * @return int|null Available seats, or null if unlimited.
	 */
	public function get_available_quantity( int $event_id ): ?int {
		global $wpdb;

		$table = TableNames::event_inventory();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT capacity, held_qty, confirmed_qty FROM {$table} WHERE event_id = %d LIMIT 1",
				$event_id
			),
			ARRAY_A
		);

		if ( null === $row ) {
			return null;
		}

		$capacity = (int) $row['capacity'];

		if ( 0 === $capacity ) {
			return null; // Unlimited.
		}

		$available = $capacity - (int) $row['held_qty'] - (int) $row['confirmed_qty'];

		return max( $available, 0 );
	}

	/**
	 * Rebuild inventory counters from bookings table.
	 *
	 * Used after migration and by admin "Rebuild inventory".
	 *
	 * @param int $event_id Event post ID.
	 *
	 * @return void
	 */
	public function rebuild_for_event( int $event_id ): void {
		global $wpdb;

		$bookings_table  = TableNames::bookings();
		$inventory_table = TableNames::event_inventory();

		// Compute totals from bookings.
		$totals = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COALESCE( SUM( CASE WHEN status IN ('ea-pending','ea-processing') THEN qty ELSE 0 END ), 0 ) AS held,
					COALESCE( SUM( CASE WHEN status = 'ea-completed' THEN qty ELSE 0 END ), 0 ) AS confirmed
				FROM {$bookings_table}
				WHERE event_id = %d",
				$event_id
			),
			ARRAY_A
		);

		$held      = (int) ( $totals['held'] ?? 0 );
		$confirmed = (int) ( $totals['confirmed'] ?? 0 );

		// Read capacity from postmeta (existing key). Default 0 = unlimited.
		$capacity = (int) get_post_meta( $event_id, 'tp_event_qty', true );

		$now = gmdate( 'Y-m-d H:i:s' );

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$inventory_table} (event_id, capacity, held_qty, confirmed_qty, updated_at_gmt)
				VALUES (%d, %d, %d, %d, %s)
				ON DUPLICATE KEY UPDATE
					capacity = VALUES(capacity),
					held_qty = VALUES(held_qty),
					confirmed_qty = VALUES(confirmed_qty),
					updated_at_gmt = VALUES(updated_at_gmt)",
				$event_id,
				$capacity,
				$held,
				$confirmed,
				$now
			)
		);
	}
}
