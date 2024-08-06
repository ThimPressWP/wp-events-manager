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

class WPEMSBookingFilter extends WPEMSPostTypeFilter {

	/**
	 * Const post type
	 */
	const POST_TYPE_BOOKING = 'event_auth_book';

	/**
	 * Const meta key
	 */
	const META_KEY_BOOKING_USER       = 'ea_booking_user_id';
	const META_KEY_BOOKING_EVENT      = 'ea_booking_event_id';
	const META_KEY_BOOKING_QTY        = 'ea_booking_qty';
	const META_KEY_BOOKING_COST       = 'ea_booking_cost';
	const META_KEY_BOOKING_PAYMENT_ID = 'ea_booking_payment_id';
	const META_KEY_BOOKING_PRICE      = 'ea_booking_price';
	const META_KEY_BOOKING_CURRENCY   = 'ea_booking_currency';

	/**
	 * Const status
	 */
	const STATUS_CANCEL     = 'ea-cancelled';
	const STATUS_PENDING    = 'ea-pending';
	const STATUS_PROCESSING = 'ea-processing';
	const STATUS_COMPLETED  = 'ea-completed';

	/**
	 * @var string
	 */
	public $all_status = array( self::STATUS_CANCEL, self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_COMPLETED );

	public $post_type   = self::POST_TYPE_BOOKING;
	public $post_status = self::STATUS_PENDING;
}
