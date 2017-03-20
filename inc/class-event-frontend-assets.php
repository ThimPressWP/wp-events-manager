<?php

defined( 'ABSPATH' ) || exit();

class TP_Event_Frontend_Assets {

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
		WPEMS_Assets::register_script( 'tp-countdown-plugin-js', WP_EVENT_LIB_URI . '/countdown/js/jquery.plugin.min.js' );
		WPEMS_Assets::register_script( 'tp-event-countdown-js', WP_EVENT_LIB_URI . '/countdown/js/jquery.countdown.min.js' );
		WPEMS_Assets::register_style( 'tp-event-countdown-css', WP_EVENT_LIB_URI . '/countdown/css/jquery.countdown.css' );
		WPEMS_Assets::localize_script( 'tp-event-countdown-js', 'TP_Event', wpems_l18n() );

		// google map
		if ( is_singular( 'tp_event' ) ) {
			WPEMS_Assets::register_script( 'tp-event-google-map', WP_EVENT_ASSETS_URI . '/js/frontend/google-map.js' );
		}

		// owl-carousel
		WPEMS_Assets::register_script( 'tp-event-owl-carousel-js', WP_EVENT_LIB_URI . '/owl-carousel/js/owl.carousel.min.js' );
		WPEMS_Assets::register_style( 'tp-event-owl-carousel-css', WP_EVENT_LIB_URI . '/owl-carousel/css/owl.carousel.css' );

		// magnific-popup
		WPEMS_Assets::register_script( 'tp-event-magnific-popup-js', WP_EVENT_LIB_URI . '/magnific-popup/js/jquery.magnific-popup.min.js', array(), WP_EVENT_VER, true );
		WPEMS_Assets::register_style( 'tp-event-magnific-popup-css', WP_EVENT_LIB_URI . '/magnific-popup/css/magnific-popup.css', array() );

		// events
		WPEMS_Assets::register_script( 'tp-event-frontend-js', WP_EVENT_ASSETS_URI . '/js/frontend/events.js' );
		WPEMS_Assets::register_style( 'tp-event-fronted-css', WP_EVENT_ASSETS_URI . '/css/frontend/events.css' );
	}

}

TP_Event_Frontend_Assets::init();
