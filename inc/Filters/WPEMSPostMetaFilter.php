<?php
/**
 * Class WPEMSPostMetaFilter
 *
 * Filter post type of WP Events Manager
 *
 * @author  tungnx
 * @package WPEventsManager/Filters
 * @version 1.0.0
 */

/**
 * Prevent loading this file directly
 */

namespace WPEventsManager\Filters;

defined( 'ABSPATH' ) || exit();


class WPEMSPostMetaFilter extends WPEMSFilter {
	const COL_META_ID    = 'meta_id';
	const COL_POST_ID    = 'post_id';
	const COL_META_VALUE = 'meta_value';
	const COL_META_KEY   = 'meta_key';
	/**
	 * @var string[]
	 */
	public $all_fields = [
		self::COL_META_ID,
		self::COL_POST_ID,
		self::COL_META_VALUE,
		self::COL_META_KEY,
	];

	/**
	 * @var int
	 */
	public $meta_id = 0;
	/**
	 * @var int
	 */
	public $post_id = 0;
	/**
	 * @var string
	 */
	public $meta_value = '';
	/**
	 * @var string
	 */
	public $meta_key = '';
}
