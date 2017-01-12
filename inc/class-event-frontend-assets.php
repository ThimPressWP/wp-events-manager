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
		TP_Event_Assets::register_script( 'tp-countdown-plugin-js', TP_EVENT_LIB_URI . '/countdown/js/jquery.plugin.min.js' );
		TP_Event_Assets::register_script( 'tp-event-countdown-js', TP_EVENT_LIB_URI . '/countdown/js/jquery.countdown.min.js' );
		TP_Event_Assets::register_style( 'tp-event-countdown-css', TP_EVENT_LIB_URI . '/countdown/css/jquery.countdown.css' );
		TP_Event_Assets::localize_script( 'tp-event-countdown-js', 'TP_Event', tp_event_l18n() );

		// google map
		TP_Event_Assets::register_script( 'tp-event-google-map', TP_EVENT_ASSETS_URI . '/js/frontend/google-map.js' );

		// owl-carousel
		TP_Event_Assets::register_script( 'tp-event-owl-carousel-js', TP_EVENT_LIB_URI . '/owl-carousel/js/owl.carousel.min.js' );
		TP_Event_Assets::register_style( 'tp-event-owl-carousel-css', TP_EVENT_LIB_URI . '/owl-carousel/css/owl.carousel.css' );

		// magnific-popup
		TP_Event_Assets::register_script( 'tp-event-magnific-popup-js', TP_EVENT_LIB_URI . '/magnific-popup/js/jquery.magnific-popup.min.js', array(), TP_EVENT_VER, true );
		TP_Event_Assets::register_style( 'tp-event-magnific-popup-css', TP_EVENT_LIB_URI . '/magnific-popup/css/magnific-popup.css', array() );

		// events
		TP_Event_Assets::register_script( 'tp-event-frontend-js', TP_EVENT_ASSETS_URI . '/js/frontend/events.js' );
		TP_Event_Assets::register_style( 'tp-event-fronted-css', TP_EVENT_ASSETS_URI . '/css/frontend/events.css' );

		TP_Event_Assets::register_script( 'tp-event-frontend-jsx', TP_EVENT_ASSETS_URI . '/js/frontend/events-jsx.js', array('react', 'react-dom', 'babel') );
	}

}

TP_Event_Frontend_Assets::init();
