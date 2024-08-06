<?php

/**
 * Class EventModel
 *
 * @author VuxMinhThanh
 * @package WP-Events-Manager/Classes
 * @version 1.0.0
 */

namespace WPEventsManager\Models;

use WPEventsManager\Databases\WPEMSBookingDB;
use WPEventsManager\Filters\WPEMSEventFilter;

class EventModel extends PostModel {
	public $post_type = WPEMSEventFilter::POST_TYPE_EVENT;

	/**
	 * is free
	 * @return bool
	 */
	public function is_free(): bool {
		$price   = $this->get_meta_value_by_key( WPEMSEventFilter::META_KEY_EVENT_PRICE );
		$is_free = false;

		if ( empty( $price ) ) {
			$is_free = true;
		}

		return $is_free;
	}

	/**
	 * get slot available
	 *
	 * @return int
	 */
	public function get_slot_available(): int {
		$quantity  = $this->get_meta_value_by_key( WPEMSEventFilter::META_KEY_EVENT_QTY ) ?? 0;
		$booked    = $this->get_booking_quantity_by_id( $this->ID ) ?? 0;
		$available = $quantity - $booked;

		return apply_filters( 'event_slot_available', $available );
	}

	/**
	 * register time
	 * @return int
	 */
	public function get_total_registered(): int {
		$booking_db       = WPEMSBookingDB::getInstance();
		$registered       = $booking_db->get_registered( $this->ID );
		$total_registered = count( $registered );
		return apply_filters( 'event_registered_time', $total_registered );
	}

	/**
	 * get slot available
	 * @param event_id
	 * @return int
	 */
	public function get_booking_quantity_by_id() {
		$booking_db = WPEMSBookingDB::getInstance();
		return $booking_db->get_booked_quantity_by_event_id( $this->ID ) ?? 0;
	}

	public static function find( $event_id ) {
		$post_type = WPEMSEventFilter::POST_TYPE_EVENT;
		return self::find_item( $event_id, $post_type );
	}
}
