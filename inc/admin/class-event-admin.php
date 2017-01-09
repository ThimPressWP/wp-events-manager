<?php

defined( 'ABSPATH' ) || exit();

class TP_Event_Admin {

    public function __construct() {

        $this->_includes();
    }

    private function _includes() {
        TP_Event::instance()->_include( 'inc/admin/class-event-admin-menu.php' );
        TP_Event::instance()->_include( 'inc/admin/class-event-admin-assets.php' );
        TP_Event::instance()->_include( 'inc/admin/class-event-admin-metaboxes.php' );
		TP_Event::instance()->_include( 'inc/admin/class-event-admin-settings.php' );
		TP_Event::instance()->_include( 'inc/admin/class-event-admin-users.php' );
	}

}

new TP_Event_Admin();
