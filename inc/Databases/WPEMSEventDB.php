<?php
/**
 * Class WPEMSEventDB
 * 
 * @package WPEventsManager/Databases
 *
 * @author vuxminhthanh
 */

namespace WPEventsManager\Databases;

defined( 'ABSPATH' ) || exit();

use WPEventsManager\Databases\WPEMSDatabase;
use WPEventsManager\Filters\WPEMSBookingFilter;

class WPEMSEventDB extends WPEMSDatabase {
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
}
