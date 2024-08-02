<?php
/**
 * Class WPEMSBookingDB
 *
 *@package WPEventsManager/Databases
 *
 * @author vuxminhthanh
 */

namespace WPEventsManager\Databases;

defined( 'ABSPATH' ) || exit();

use WPEventsManager\Databases\WPEMSDatabase;
use WPEventsManager\Filters\WPEMSBookingFilter;
use WPEventsManager\Filters\WPEMSEventFilter;

class WPEMSBookingDB extends WPEMSDatabase {
	private static $_instance;

	protected function __construct() {
		parent::__construct();
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * get registered
	 * @return array
	 */
	public function get_registered( $event_id ) {
		$query = $this->wpdb->prepare(
			"
				SELECT booked.* FROM {$this->wpdb->posts} AS booked
					LEFT JOIN {$this->wpdb->postmeta} AS event ON event.post_id = booked.ID
					LEFT JOIN {$this->wpdb->postmeta} AS book_quanity ON book_quanity.post_id = booked.ID
					LEFT JOIN {$this->wpdb->postmeta} AS user_booked ON user_booked.post_id = booked.ID
					LEFT JOIN {$this->wpdb->users} AS user ON user.ID = user_booked.meta_value
				WHERE booked.post_type = %s
					AND event.meta_key = %s
					AND event.meta_value = %d
					AND user_booked.meta_key = %s
					AND book_quanity.meta_key = %s
			",
			WPEMSBookingFilter::POST_TYPE_BOOKING,
			WPEMSBookingFilter::META_KEY_BOOKING_EVENT,
			$event_id,
			WPEMSBookingFilter::META_KEY_BOOKING_USER,
			WPEMSBookingFilter::META_KEY_BOOKING_QTY,
		);

		return $this->wpdb->get_results( $query );
	}

		/**
	 * get booked quantity by event id
	 *
	 * @param event_id
	 * @param user_id
	 *
	 * @return int
	 */
	public static function get_booked_quantity_by_event_id( int $event_id, int $user_id = 0, string $status = '' ): int {
		$query = $this->wpdb->prepare(
			"
	         SELECT SUM( pm.meta_value ) AS qty FROM {$this->wpdb->postmeta} AS pm
	             INNER JOIN {$this->wpdb->posts} AS book ON book.ID = pm.post_id
	             INNER JOIN {$this->wpdb->postmeta} AS pm2 ON pm2.post_id = book.ID
	             INNER JOIN {$this->wpdb->postmeta} AS pm3 ON pm3.post_id = book.ID
	             INNER JOIN {$this->wpdb->posts} AS event ON event.ID = pm3.meta_value
	             INNER JOIN {$this->wpdb->users} AS user ON user.ID = pm2.meta_value
	         WHERE
	             pm.meta_key = %s
	             AND book.post_type = %s
	             AND pm2.meta_key = %s
	             AND pm3.meta_key = %s
	             AND event.ID = %d
	             AND event.post_type = %s
	     ",
			WPEMSBookingFilter::META_KEY_BOOKING_QTY,
			WPEMSBookingFilter::POST_TYPE_BOOKING,
			WPEMSBookingFilter::META_KEY_BOOKING_USER,
			WPEMSBookingFilter::META_KEY_BOOKING_EVENT,
			$event_id,
			WPEMSEventFilter::POST_TYPE_EVENT
		);

		if ( empty( $status ) && empty( $user_id ) ) {
			$query .= $this->wpdb->prepare( ' AND book.post_status = %s', WPEMSBookingFilter::STATUS_COMPLETED );
		}

		if ( ! empty( $status ) ) {
			$query .= $this->wpdb->prepare( ' AND book.post_status = %s', $status );
		}

		if ( ! empty( $user_id ) ) {
			$query .= $this->wpdb->prepare( ' AND user.ID = %d', $user_id );
		}

		return apply_filters( 'event_auth_booked_quanity', (int) $this->wpdb->get_var( $query ) );
	}
}
