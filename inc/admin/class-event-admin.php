<?php

defined( 'ABSPATH' ) || exit();

class Event_Admin {

    public function __construct() {

        $this->_includes();
    }

    private function _includes() {
        TP_Event::instance()->_include( 'inc/admin/class-event-admin-menu.php' );
        TP_Event::instance()->_include( 'inc/admin/class-event-admin-assets.php' );
        TP_Event::instance()->_include( 'inc/admin/class-event-admin-metaboxes.php' );
    }

}

new Event_Admin();
