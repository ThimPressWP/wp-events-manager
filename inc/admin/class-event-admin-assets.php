<?php

defined( 'ABSPATH' ) || exit();

class Event_Admin_Assets {

    /**
     * Register scripts
     * @since 1.4.1.4
     */
    public static function init() {
        add_action( 'event_before_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
    }
    
    /**
     * Register scripts
     * @param type $hook
     */
    public static function register_scripts( $hook ) {
//        Event_Assets::register_script( 'event-admin-backbone', TP_EVENT_ASSETS_URI . '/js/admin/backbone-modal.js' );
        Event_Assets::register_script( 'event-admin', TP_EVENT_ASSETS_URI . '/js/admin/admin-events.js' );
        Event_Assets::register_style( 'event-admin', TP_EVENT_ASSETS_URI . '/css/admin/admin.css' );
        Event_Assets::register_script( 'event-admin-datetimepicker', TP_EVENT_ASSETS_URI . '/js/datetimepicker/jquery.datetimepicker.full.min.js' );
        Event_Assets::register_style( 'event-admin-datetimepicker', TP_EVENT_ASSETS_URI . '/css/datetimepicker/jquery.datetimepicker.min.css' );
    }
    
}

Event_Admin_Assets::init();
