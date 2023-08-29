<?php
/**
 * WP Events Manager Frontend Assets class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

class WPEMS_Frontend_Assets {

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
		// Sync to google calendar
		WPEMS_Assets::register_script( 'google-calendar-js', WPEMS_ASSETS_URI . '/js/frontend/google-calendar.js' );

		// Calendar
		WPEMS_Assets::register_script('wpems-fullcalendar-lb', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js');
		WPEMS_Assets::register_script( 'wpems-calendar-js', WPEMS_ASSETS_URI . '/js/admin/calendar-event.js' );

		// Event List
		WPEMS_Assets::register_script( 'wpems-litepicker-lb', 'https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js' );
		WPEMS_Assets::register_script( 'wpems-ranges-lb', 'https://cdn.jsdelivr.net/npm/litepicker/dist/plugins/ranges.js' );
		WPEMS_Assets::register_script( 'wpems-event-list-display-js', WPEMS_ASSETS_URI . '/js/frontend/event-list-display.js' );
		
		// Countdown
		WPEMS_Assets::register_script( 'wpems-countdown-plugin-js', WPEMS_LIB_URI . '/countdown/js/jquery.plugin.min.js' );
		WPEMS_Assets::register_script( 'wpems-countdown-js', WPEMS_LIB_URI . '/countdown/js/jquery.countdown.min.js' );
		WPEMS_Assets::register_style( 'wpems-countdown-css', WPEMS_LIB_URI . '/countdown/css/jquery.countdown.css' );
		WPEMS_Assets::localize_script( 'wpems-countdown-js', 'WPEMS', wpems_l18n() );

		// google map
		if ( is_singular( 'tp_event' ) ) {
			WPEMS_Assets::register_script( 'wpems-google-map', WPEMS_ASSETS_URI . '/js/frontend/google-map.js' );
		}

		// owl-carousel
		WPEMS_Assets::register_script( 'wpems-owl-carousel-js', WPEMS_LIB_URI . '/owl-carousel/js/owl.carousel.min.js' );
		WPEMS_Assets::register_style( 'wpems-owl-carousel-css', WPEMS_LIB_URI . '/owl-carousel/css/owl.carousel.css' );

		// magnific-popup
		WPEMS_Assets::register_script( 'wpems-magnific-popup-js', WPEMS_LIB_URI . '/magnific-popup/js/jquery.magnific-popup.min.js', array(), WPEMS_VER, true );
		WPEMS_Assets::register_style( 'wpems-magnific-popup-css', WPEMS_LIB_URI . '/magnific-popup/css/magnific-popup.css', array() );

		// events
		WPEMS_Assets::register_script( 'wpems-frontend-js', WPEMS_ASSETS_URI . '/js/frontend/events.js' );
		WPEMS_Assets::register_style( 'wpems-fronted-css', WPEMS_ASSETS_URI . '/css/frontend/events.css' );
	}

}

WPEMS_Frontend_Assets::init();
