<?php
/**
 * WP Events Manager Admin class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

class WPEMS_Admin {

	public function __construct() {

		$this->_includes();
	}

	private function _includes() {
		include( WPEMS_PATH . 'inc/admin/class-wpems-admin-menu.php' );
		include( WPEMS_PATH . 'inc/admin/class-wpems-admin-assets.php' );
		include( WPEMS_PATH . 'inc/admin/class-wpems-admin-metaboxes.php' );
		include( WPEMS_PATH . 'inc/admin/class-wpems-admin-settings.php' );
		include( WPEMS_PATH . 'inc/admin/class-wpems-admin-users.php' );
	}

}

new WPEMS_Admin();
