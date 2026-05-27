<?php
/**
 * Repository for wpems_booking_meta table.
 *
 * Thin wrapper behaving like WP *_metadata() functions but scoped
 * to booking IDs. Values round-trip through maybe_serialize/maybe_unserialize.
 *
 * @package WPEMS\Repositories
 * @since   3.0.0
 */

namespace WPEMS\Repositories;

defined( 'ABSPATH' ) || exit;

use WPEMS\Tables\TableNames;

/**
 * Booking meta repository.
 */
class BookingMetaRepository {

	/**
	 * Get a single meta value for a booking.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $key        Meta key.
	 * @param mixed  $fallback   Value to return when key is missing.
	 *
	 * @return mixed
	 */
	public function get( int $booking_id, string $key, $fallback = null ) {
		if ( $booking_id <= 0 || '' === $key ) {
			return $fallback;
		}

		global $wpdb;

		$table = TableNames::booking_meta();

		$raw = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$table} WHERE booking_id = %d AND meta_key = %s LIMIT 1",
				$booking_id,
				$key
			)
		);

		if ( null === $raw ) {
			return $fallback;
		}

		return maybe_unserialize( $raw );
	}

	/**
	 * Insert or update a meta value.
	 *
	 * Checks for existing row first, then updates or inserts accordingly.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $key        Meta key.
	 * @param mixed  $value      Meta value (will be serialized if needed).
	 *
	 * @return bool True on success.
	 */
	public function update( int $booking_id, string $key, $value ): bool {
		if ( $booking_id <= 0 || '' === $key ) {
			return false;
		}

		global $wpdb;

		$table      = TableNames::booking_meta();
		$serialized = maybe_serialize( $value );

		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_id FROM {$table} WHERE booking_id = %d AND meta_key = %s LIMIT 1",
				$booking_id,
				$key
			)
		);

		if ( $existing_id ) {
			$wpdb->update(
				$table,
				array( 'meta_value' => $serialized ),
				array( 'meta_id' => (int) $existing_id )
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'booking_id' => $booking_id,
					'meta_key'   => $key,
					'meta_value' => $serialized,
				)
			);
		}

		return '' === $wpdb->last_error;
	}

	/**
	 * Delete a meta entry.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $key        Meta key.
	 *
	 * @return bool True if at least one row was deleted.
	 */
	public function delete( int $booking_id, string $key ): bool {
		if ( $booking_id <= 0 || '' === $key ) {
			return false;
		}

		global $wpdb;

		$table = TableNames::booking_meta();

		$rows = $wpdb->delete(
			$table,
			array(
				'booking_id' => $booking_id,
				'meta_key'   => $key,
			)
		);

		return (bool) $rows;
	}

	/**
	 * Get all meta for a booking as key => value array.
	 *
	 * @param int $booking_id Booking ID.
	 *
	 * @return array Associative array of meta_key => unserialized meta_value.
	 */
	public function get_all( int $booking_id ): array {
		if ( $booking_id <= 0 ) {
			return array();
		}

		global $wpdb;

		$table = TableNames::booking_meta();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM {$table} WHERE booking_id = %d",
				$booking_id
			),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[ $row['meta_key'] ] = maybe_unserialize( $row['meta_value'] );
		}

		return $out;
	}
}
