<?php
/**
 * WP Events Manager Admin Menu class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

class WPEMS_Admin_Menu {

	/**
	 * menus
	 * @var array
	 */
	public $_menus = array();

	/**
	 * instead new class
	 * @var null
	 */
	static $_instance = null;

	public function __construct() {
		// admin menu
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	// add admin menu callback
	public function admin_menu() {
		/**
		 * menus
		 * @var array
		 */
		$menus = apply_filters( 'tp_event_admin_menu', $this->_menus );
		add_menu_page( __( 'Events Manager', 'wp-events-manager' ), __( 'Events Manager', 'wp-events-manager' ), 'administrator', 'tp-event-setting', null, 'dashicons-calendar-alt', 4 );
		if ( $menus ) {
			foreach ( $menus as $menu ) {
				call_user_func_array( 'add_submenu_page', $menu );
			}
		}
		add_submenu_page( 'tp-event-setting', __( 'WP Event Users', 'wp-events-manager' ), __( 'Users', 'wp-events-manager' ), 'administrator', 'tp-event-users', array( 'WPEMS_Admin_Users', 'output' ) );
		add_submenu_page( 'tp-event-setting', __( 'WP Event Settings', 'wp-events-manager' ), __( 'Settings', 'wp-events-manager' ), 'administrator', 'tp-event-setting', array( 'WPEMS_Admin_Settings', 'output' ) );
	}

	/**
	 * add menu item
	 *
	 * @param $params
	 */
	public function add_menu( $params ) {
		$this->_menus[] = $params;
	}

	/**
	 * instance
	 * @return object class
	 */
	public static function instance() {
		if ( self::$_instance ) {
			return self::$_instance;
		}

		return new self();
	}

}

WPEMS_Admin_Menu::instance();
