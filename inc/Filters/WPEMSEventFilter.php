<?php
/**
 * Class WPEMSEventFilter
 *
 * @author  VuxMinhThanh
 * @package WPEventsManager/Classes/Filters
 * @version 1.0.0
 */


/**
 * Prevent loading this file directly
 */

namespace WPEventsManager\Filters;

defined( 'ABSPATH' ) || exit();

class WPEMSEventFilter extends WPEMSPostTypeFilter {

	/**
	 * Const post type
	 */
	const POST_TYPE_EVENT = 'tp_event';

	/**
	 * Const meta key
	 */
	const META_KEY_EVENT_QTY                   = 'tp_event_qty';
	const META_KEY_EVENT_PRICE                 = 'tp_event_price';
	const META_KEY_EVENT_DATE_START            = 'tp_event_date_start';
	const META_KEY_EVENT_TIME_START            = 'tp_event_time_start';
	const META_KEY_EVENT_DATE_END              = 'tp_event_date_end';
	const META_KEY_EVENT_TIME_END              = 'tp_event_time_end';
	const META_KEY_EVENT_REGISTRATION_END_DATE = 'tp_event_registration_end_date';
	const META_KEY_EVENT_REGISTER_END_TIME     = 'tp_event_registration_end_time';
	const META_KEY_EVENT_LOCATION              = 'tp_event_location';
	const META_KEY_EVENT_IFRAME                = 'tp_event_iframe';
	const META_KEY_EVENT_STATUS                = 'tp_event_status';
	const META_KEY_EVENT_CATEGORY              = 'tp_event_category';

	/**
	 * Const status
	 */
	const STATUS_HAPPENING = 'happening';
	const STATUS_EXPIRED   = 'expired';

	/**
	 * @var string
	 */
	public $post_type = self::POST_TYPE_EVENT;
}
