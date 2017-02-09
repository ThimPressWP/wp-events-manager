<?php

/*
  Plugin Name: Thim Events
  Plugin URI: http://thimpress.com/
  Description: A complete plugin for Event management and online booking system
  Author: ThimPress
  Version: 2.0
  Author URI: http://thimpress.com
 */

if ( !defined( 'ABSPATH' ) )
	exit();

/**
 * Event class
 */
if ( !class_exists( 'TP_Event' ) ) {

	final class TP_Event {

		private static $_instance = null;

		public $_session = null;

		/**
		 * TP_Event constructor.
		 */
		public function __construct() {
			$this->define_constants();
			$this->includes();
			$this->init_hooks();
		}

		/**
		 * Define Plugins Constants
		 */
		public function define_constants() {
			$this->set_define( 'TP_EVENT_PATH', plugin_dir_path( __FILE__ ) );
			$this->set_define( 'TP_EVENT_URI', plugin_dir_url( __FILE__ ) );
			$this->set_define( 'TP_EVENT_INC', TP_EVENT_PATH . 'inc/' );
			$this->set_define( 'TP_EVENT_INC_URI', TP_EVENT_URI . 'inc/' );
			$this->set_define( 'TP_EVENT_ASSETS_URI', TP_EVENT_URI . 'assets' );
			$this->set_define( 'TP_EVENT_LIB_URI', TP_EVENT_INC_URI . 'libraries/' );
			$this->set_define( 'TP_EVENT_VER', '2.0' );
			$this->set_define( 'TP_EVENT_MAIN_FILE', __FILE__ );
		}

		public function set_define( $name = '', $value = '' ) {
			if ( $name && !defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Init hooks plugins
		 * @since 2.0
		 */
		public function init_hooks() {
			// plugin loaded
			add_action( 'plugins_loaded', array( $this, 'loaded' ) );
		}

		/**
		 * Load components when plugin loaded
		 */
		public function loaded() {
			// load text domain
			$this->text_domain();
			$this->_session = new TP_Event_Session();

			do_action( 'tp_event_init', $this );
		}

		/**
		 * include file
		 *
		 * @param  array || string
		 *
		 * @return null
		 */
		public function includes() {

			$this->_include( 'inc/tp-event-core-functions.php' );
			$this->_include( 'inc/class-event-autoloader.php' );
			$this->_include( 'inc/class-event-assets.php' );
			$this->_include( 'inc/class-event-ajax.php' );
			$this->_include( 'inc/class-event-post-types.php' );
			$this->_include( 'inc/emails/class-event-register-event.php' );
			$this->_include( 'inc/class-event-payment-gateways.php' );
			$this->_include( 'inc/class-event-install.php' );
			$this->settings = TP_Event_Settings::instance();

			if ( is_admin() ) {
				$this->_include( 'inc/admin/class-event-admin.php' );
			} else {
				$this->_include( 'inc/class-event-template.php' );
				$this->_include( 'inc/class-event-frontend-assets.php' );
				$this->_include( 'inc/class-event-user-process.php' );
				$this->_include( 'inc/class-event-shortcodes.php' );
			}

		}

		/**
		 * Include single file
		 *
		 * @param $file
		 */
		public function _include( $file = null ) {
			if ( is_array( $file ) ) {
				foreach ( $file as $key => $f ) {
					if ( file_exists( TP_EVENT_PATH . $f ) )
						require_once TP_EVENT_PATH . $f;
				}
			} else {
				if ( file_exists( TP_EVENT_PATH . $file ) )
					require_once TP_EVENT_PATH . $file;
				elseif ( file_exists( $file ) )
					require_once $file;
			}
		}

		/**
		 * load text domain
		 * @return null
		 */
		public function text_domain() {
			// Get mo file
			$text_domain = 'tp-event';
			$locale      = apply_filters( 'plugin_locale', get_locale(), $text_domain );
			$mo_file     = $text_domain . '-' . $locale . '.mo';
			// Check mo file global
			$mo_global = WP_LANG_DIR . '/plugins/' . $mo_file;
			// Load translate file
			if ( file_exists( $mo_global ) ) {
				load_textdomain( $text_domain, $mo_global );
			} else {
				load_textdomain( $text_domain, TP_EVENT_PATH . '/languages/' . $mo_file );
			}
		}

		/**
		 * get instance class
		 * @return TP_Event
		 */
		public static function instance() {
			if ( !empty( self::$_instance ) ) {
				return self::$_instance;
			}
			return self::$_instance = new self();
		}

	}

	if ( !function_exists( 'TP_EVENT' ) ) {

		function TP_EVENT() {
			return TP_Event::instance();
		}

	}
	TP_EVENT();
}


$GLOBALS['TP_Event'] = TP_EVENT();