<?php
/**
 * Class WPEMSUserFilter
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

class WPEMSUserFilter extends WPEMSFilter {
	/**
	 * @var string[] List of fields can be filtered.
	 */
	public $all_fields = [
		'ID',
		'user_login',
		'user_nicename',
		'user_email',
		'display_name',
	];
	/**
	 * @var int user id.
	 */
	public $ID = 0;
	/**
	 * @var int[] List of user ids.
	 */
	public $ids = [];
	/**
	 * @var string User nice name.
	 */
	public $user_nicename = '';
	/**
	 * @var string Email.
	 */
	public $user_email = '';
}
