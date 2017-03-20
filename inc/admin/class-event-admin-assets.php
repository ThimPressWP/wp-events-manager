<?php

defined( 'ABSPATH' ) || exit();

class WPEMS_Admin_Assets {

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
		WPEMS_Assets::register_script( 'tp-event-admin-js', WP_EVENT_ASSETS_URI . '/js/admin/admin-events.js' );
		WPEMS_Assets::register_style( 'tp-event-admin-css', WP_EVENT_ASSETS_URI . '/css/admin/admin.css' );
		WPEMS_Assets::register_script( 'tp-event-admin-datetimepicker-full', WP_EVENT_ASSETS_URI . '/js/datetimepicker/jquery.datetimepicker.full.min.js' );
		WPEMS_Assets::register_style( 'tp-event-admin-datetimepicker-min', WP_EVENT_ASSETS_URI . '/css/datetimepicker/jquery.datetimepicker.min.css' );
	}

}

WPEMS_Admin_Assets::init();
