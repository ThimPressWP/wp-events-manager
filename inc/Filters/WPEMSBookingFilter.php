<?php
/**
 * Class WPEMSBookingFilter
 *
 * @author  VuxMinhThanh
 * @package WPEventsManager/Filters
 * @version 1.0.0
 */


/**
 * Prevent loading this file directly
 */

namespace WPEventsManager\Filters;

defined( 'ABSPATH' ) || exit();

use WPEventsManager\Filters\WPEMSFilter;

class WPEMSBookingFilter extends WPEMSFilter {

	/**
	 * Const post type
	 */
	const POST_TYPE_BOOKING = 'event_auth_book';

	/**
	 * Const meta key
	 */
	const META_KEY_BOOKING_USER  = 'ea_booking_user_id';
	const META_KEY_BOOKING_EVENT = 'ea_booking_event_id';
	const META_KEY_BOOKING_QTY   = 'ea_booking_qty';

	/**
	 * Const status
	 */
	const STATUS_COMPLETED = 'ea-completed';

	/**
	 * @var string
	 */
	public $post_type = self::POST_TYPE_BOOKING;
}
