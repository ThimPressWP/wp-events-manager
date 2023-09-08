<?php
/**
 * WP Events Manager Admin Assets class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

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

		global $post;
		// Calendar
		WPEMS_Assets::register_script('wpems-fullcalendar-lb', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js');
		WPEMS_Assets::register_script( 'wpems-admin-calendar-js', WPEMS_ASSETS_URI . '/js/admin/admin-calendar.js', array('wpems-fullcalendar-lb'  ));

		WPEMS_Assets::register_script( 'wpems-admin-js', WPEMS_ASSETS_URI . '/js/admin/admin-events.js' );
		WPEMS_Assets::register_style( 'wpems-admin-css', WPEMS_ASSETS_URI . '/css/admin/admin.css' );
		WPEMS_Assets::register_script( 'wpems-admin-datetimepicker-full', WPEMS_ASSETS_URI . '/js/datetimepicker/jquery.datetimepicker.full.min.js' );
		WPEMS_Assets::register_style( 'wpems-admin-datetimepicker-min', WPEMS_ASSETS_URI . '/css/datetimepicker/jquery.datetimepicker.min.css' );

		if( $post && $post->post_type == 'tp_event' ){
			WPEMS_Assets::register_script( 'admin-events-settings', WPEMS_ASSETS_URI . '/js/admin/admin-events-schedules.js' );
			WPEMS_Assets::register_script( 'admin-events-map', WPEMS_ASSETS_URI . '/js/admin/admin-events-map.js' );
			WPEMS_Assets::register_script( 'sortable', WPEMS_ASSETS_URI . '/js/admin/libraries/cdnjs.cloudflare.com_ajax_libs_Sortable_1.14.0_Sortable.min.js' );
			// WPEMS_Assets::register_script( 'sortable', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyC1YtKgyk-HgkoB2rJXLfNZYezUvlyXWX0&callback=initMap&libraries=places&v=weekly' );
		}
	}
	
}

WPEMS_Admin_Assets::init();
