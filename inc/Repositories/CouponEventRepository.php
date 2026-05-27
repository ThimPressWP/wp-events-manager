<?php
/**
 * Repository for wpems_coupon_events junction table.
 *
 * Manages the many-to-many relationship between coupons and events.
 * Used when coupon.applies_to = 'specific'.
 *
 * @package WPEMS\Repositories
 * @since   3.0.0
 */

namespace WPEMS\Repositories;

defined( 'ABSPATH' ) || exit;

use RuntimeException;
use WPEMS\Tables\TableNames;

/**
 * Coupon-event mapping repository.
 */
class CouponEventRepository {

	/**
	 * Replace all event mappings for a coupon (transactional).
	 *
	 * @param int   $coupon_id Coupon ID.
	 * @param int[] $event_ids Event post IDs.
	 *
	 * @return void
	 *
	 * @throws RuntimeException On database error.
	 */
	public function set_events( int $coupon_id, array $event_ids ): void {
		if ( $coupon_id <= 0 ) {
			return;
		}

		// Coerce, deduplicate, filter zeroes.
		$event_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', $event_ids ),
					function ( int $id ): bool {
						return $id > 0;
					}
				)
			)
		);

		global $wpdb;

		$table = TableNames::coupon_events();

		$wpdb->query( 'START TRANSACTION' );

		try {
			$wpdb->delete( $table, array( 'coupon_id' => $coupon_id ) );

			foreach ( $event_ids as $event_id ) {
				$wpdb->insert(
					$table,
					array(
						'coupon_id' => $coupon_id,
						'event_id'  => $event_id,
					)
				);

				if ( '' !== $wpdb->last_error ) {
					throw new RuntimeException( $wpdb->last_error );
				}
			}

			$wpdb->query( 'COMMIT' );
		} catch ( RuntimeException $e ) {
			$wpdb->query( 'ROLLBACK' );
			throw $e;
		}
	}

	/**
	 * Get all event IDs mapped to a coupon.
	 *
	 * @param int $coupon_id Coupon ID.
	 *
	 * @return int[]
	 */
	public function get_event_ids( int $coupon_id ): array {
		if ( $coupon_id <= 0 ) {
			return array();
		}

		global $wpdb;

		$table = TableNames::coupon_events();

		$col = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT event_id FROM {$table} WHERE coupon_id = %d",
				$coupon_id
			)
		);

		return array_map( 'intval', (array) $col );
	}

	/**
	 * Check whether a coupon is mapped to a specific event.
	 *
	 * @param int $coupon_id Coupon ID.
	 * @param int $event_id  Event post ID.
	 *
	 * @return bool
	 */
	public function coupon_applies_to_event( int $coupon_id, int $event_id ): bool {
		if ( $coupon_id <= 0 || $event_id <= 0 ) {
			return false;
		}

		global $wpdb;

		$table = TableNames::coupon_events();

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$table} WHERE coupon_id = %d AND event_id = %d LIMIT 1",
				$coupon_id,
				$event_id
			)
		);

		return (bool) $result;
	}

	/**
	 * Delete all event mappings for a coupon.
	 *
	 * Called from CouponRepository::delete() cascade.
	 *
	 * @param int $coupon_id Coupon ID.
	 *
	 * @return void
	 */
	public function delete_all_for_coupon( int $coupon_id ): void {
		if ( $coupon_id <= 0 ) {
			return;
		}

		global $wpdb;

		$wpdb->delete( TableNames::coupon_events(), array( 'coupon_id' => $coupon_id ) );
	}
}
