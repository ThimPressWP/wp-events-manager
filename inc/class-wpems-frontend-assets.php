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
	 *
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

		WPEMS_Assets::register_script( 'wpems-modal-js', WPEMS_ASSETS_URI . '/dist/js/frontend/wpems-modal.js', array(), WPEMS_VER, true );
		WPEMS_Assets::register_script( 'wpems-countdown-js', WPEMS_ASSETS_URI . '/dist/js/frontend/wpems-countdown.js', array(), WPEMS_VER, true );
		WPEMS_Assets::register_script( 'wpems-carousel-js', WPEMS_ASSETS_URI . '/dist/js/frontend/wpems-carousel.js', array(), WPEMS_VER, true );
		WPEMS_Assets::localize_script( 'wpems-countdown-js', 'WPEMS', wpems_l18n() );
		// google map
		if ( is_singular( 'tp_event' ) ) {
			WPEMS_Assets::register_script( 'wpems-google-map', WPEMS_ASSETS_URI . '/dist/js/frontend/google-map.js' );
		}

		// events
		WPEMS_Assets::register_script( 'wpems-frontend-js', WPEMS_ASSETS_URI . '/dist/js/frontend/events.js', array( 'wpems-modal-js', 'wpems-countdown-js', 'wpems-carousel-js' ) );
		WPEMS_Assets::register_style( 'wpems-fronted-css', WPEMS_ASSETS_URI . '/css/frontend/events.css' );
		WPEMS_Assets::register_style( 'wpems-figma-events-css', WPEMS_ASSETS_URI . '/css/frontend/figma-events.css', array( 'wpems-fronted-css' ), WPEMS_VER );
	}
}

WPEMS_Frontend_Assets::init();
