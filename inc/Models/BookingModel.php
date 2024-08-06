<?php

/**
 * Class BookingModel
 *
 * @author VuxMinhThanh
 * @package WP-Events-Manager/Classes
 * @version 1.0.0
 */

namespace WPEventsManager\Models;

use WPEventsManager\Databases\WPEMSBookingDB;
use WPEventsManager\Filters\WPEMSBookingFilter;

class BookingModel extends PostModel {
	public $post_type = WPEMSBookingFilter::POST_TYPE_BOOKING;

	public function create() {
		$booking_db = WPEMSBookingDB::getInstance();
		$booking_db->create_booking();
	}

	public function update_status( $status ) {
		$filter = new WPEMSBookingFilter();
		if ( ! in_array( $status, $filter->all_status ) ) {
			return null;
		}

		$filter->set[] = "post_status = ({$status})";
		$this->update( $filter );
	}

	public function update( WPEMSBookingFilter $filter ) {
		$filter->where[] = "AND ID = ({$this->ID})";
		$booking_db      = WPEMSBookingDB::getInstance();
		$booking_db->update( $filter );
	}

	public function delete() {
		$filter             = new WPEMSBookingFilter();
		$booking_db         = WPEMSBookingDB::getInstance();
		$filter->collection = $booking_db->tb_posts;
		$filter->where[]    = "AND ID = ({$this->ID})";
		$booking_db->update( $filter );
	}

	public static function find( $booking_id ) {
		$filter              = new WPEMSBookingFilter();
		$filter->ID          = $booking_id;
		$filter->post_status = $filter->all_status;
		return self::get_item_model_from_db( $filter );
	}
}
