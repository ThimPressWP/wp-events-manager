<?php

/**
 * Class Event
 *
 * @author VuxMinhThanh
 * @package WP-Events-Manager/Classes
 * @version 1.0.0
 */

namespace WPEventsManager\Models;

use WPEMSBookingDB;
use WPEventsManager\Filters\WPEMSEventFilter;

class EventModel extends PostModel {
	/**
	 * is free
	 * @return bool
	 */
	public function is_free(): bool {
		$price   = $this->get_meta_value_by_key( self::META_KEY_EVENT_PRICE );
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
		$booked    = BookingModel::get_booking_quantity_by_id( $this->ID ) ?? 0;
		$available = $quantity - $booked;

		return apply_filters( 'event_slot_available', $available );
	}

		/**
	 * register time
	 * @return int
	 */
	public function get_total_registered(): int {
		$registered       = WPEMSBookingDB::get_registered();
		$total_registered = count( $registered );
		return apply_filters( 'event_registered_time', $total_registered );
	}

	/**
	 * get slot available
	 * @param event_id
	 * @return int
	 */
	public static function get_booking_quantity_by_id( $event_id ) {
		if ( empty( $event_id ) ) {
			$event_id = $this->ID;
		}

		return WPEMSBookingDB::get_booked_quantity_by_event_id( $event_id ) ?? 0;
	}

	public static function find( int $event_id ) {
		$filter_event     = new WPEMSEventFilter();
		$filter_event->ID = $event_id;
		return self::get_item_model_from_db( $filter_event );
	}
}
