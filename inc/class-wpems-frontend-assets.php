<?php

defined( 'ABSPATH' ) || exit();

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
