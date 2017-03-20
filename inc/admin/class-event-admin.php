<?php

defined( 'ABSPATH' ) || exit();

class WPEMS_Admin {

	public function __construct() {

		$this->_includes();
	}

	private function _includes() {
		include( WP_EVENT_PATH . 'inc/admin/class-event-admin-menu.php' );
		include( WP_EVENT_PATH . 'inc/admin/class-event-admin-assets.php' );
		include( WP_EVENT_PATH . 'inc/admin/class-event-admin-metaboxes.php' );
		include( WP_EVENT_PATH . 'inc/admin/class-event-admin-settings.php' );
		include( WP_EVENT_PATH . 'inc/admin/class-event-admin-users.php' );
	}

}

new WPEMS_Admin();
