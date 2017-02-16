<?php

defined( 'ABSPATH' ) || exit();

class TP_Event_Admin_Assets {

	/**
	 * Register scripts
	 * @since 1.4.1.4
	 */
	public static function init() {
		add_action( 'tp_event_before_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
	}

	/**
	 * Register scripts
	 *
	 * @param type $hook
	 */
	public static function register_scripts( $hook ) {
		TP_Event_Assets::register_script( 'tp-event-admin-js', WP_EVENT_ASSETS_URI . '/js/admin/admin-events.js' );
		TP_Event_Assets::register_style( 'tp-event-admin-css', WP_EVENT_ASSETS_URI . '/css/admin/admin.css' );
		TP_Event_Assets::register_script( 'tp-event-admin-datetimepicker-full', WP_EVENT_ASSETS_URI . '/js/datetimepicker/jquery.datetimepicker.full.min.js' );
		TP_Event_Assets::register_style( 'tp-event-admin-datetimepicker-min', WP_EVENT_ASSETS_URI . '/css/datetimepicker/jquery.datetimepicker.min.css' );
	}

}

TP_Event_Admin_Assets::init();
