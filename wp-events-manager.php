<?php

/*
  Plugin Name: WP Events Manager
  Plugin URI: http://thimpress.com/
  Description: A complete plugin for Events management and online booking system
  Author: ThimPress
  Version: 2.0.8
  Author URI: http://thimpress.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * WPEMS class
 */
if ( ! class_exists( 'WPEMS' ) ) {

	final class WPEMS {

		private static $_instance = null;

		public $_session = null;

		/**
		 * WPEMS constructor.
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
			$this->set_define( 'WPEMS_PATH', plugin_dir_path( __FILE__ ) );
			$this->set_define( 'WPEMS_URI', plugin_dir_url( __FILE__ ) );
			$this->set_define( 'WPEMS_INC', WPEMS_PATH . 'inc/' );
			$this->set_define( 'WPEMS_INC_URI', WPEMS_URI . 'inc/' );
			$this->set_define( 'WPEMS_ASSETS_URI', WPEMS_URI . 'assets/' );
			$this->set_define( 'WPEMS_LIB_URI', WPEMS_INC_URI . 'libraries/' );
			$this->set_define( 'WPEMS_VER', '2.0.8' );
			$this->set_define( 'WPEMS_MAIN_FILE', __FILE__ );
		}

		public function set_define( $name = '', $value = '' ) {
			if ( $name && ! defined( $name ) ) {
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
			$this->_session = new WPEMS_Session();

			do_action( 'wpems_init', $this );
		}

		/**
		 * include file
		 *
		 * @param  array || string
		 *
		 * @return null
		 */
		public function includes() {

			$this->_include( 'inc/wpems-core-functions.php' );
			$this->_include( 'inc/class-wpems-autoloader.php' );
			$this->_include( 'inc/class-wpems-assets.php' );
			$this->_include( 'inc/class-wpems-ajax.php' );
			$this->_include( 'inc/class-wpems-post-types.php' );
			$this->_include( 'inc/emails/class-wpems-register-event.php' );
			$this->_include( 'inc/class-wpems-payment-gateways.php' );
			$this->_include( 'inc/class-wpems-install.php' );
			$this->_include( 'inc/class-wpems-settings.php' );
			$this->_include( 'inc/class-wpems-session.php' );
			$this->_include( 'inc/class-wpems-booking.php' );
			$this->_include( 'inc/class-wpems-event.php' );
			$this->settings = WPEMS_Settings::instance();

			if ( is_admin() ) {
				$this->_include( 'inc/admin/class-wpems-admin.php' );
			} else {
				$this->_include( 'inc/class-wpems-template.php' );
				$this->_include( 'inc/class-wpems-frontend-assets.php' );
				$this->_include( 'inc/class-wpems-user-process.php' );
				$this->_include( 'inc/class-wpems-shortcodes.php' );
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
					if ( file_exists( WPEMS_PATH . $f ) ) {
						require_once WPEMS_PATH . $f;
					}
				}
			} else {
				if ( file_exists( WPEMS_PATH . $file ) ) {
					require_once WPEMS_PATH . $file;
				} elseif ( file_exists( $file ) ) {
					require_once $file;
				}
			}
		}

		/**
		 * load text domain
		 * @return null
		 */
		public function text_domain() {
			// Get mo file
			$text_domain = 'wp-events-manager';
			$locale      = apply_filters( 'plugin_locale', get_locale(), $text_domain );
			$mo_file     = $text_domain . '-' . $locale . '.mo';
			// Check mo file global
			$mo_global = WP_LANG_DIR . '/plugins/' . $mo_file;
			// Load translate file
			if ( file_exists( $mo_global ) ) {
				load_textdomain( $text_domain, $mo_global );
			} else {
				load_textdomain( $text_domain, WPEMS_PATH . '/languages/' . $mo_file );
			}
		}

		/**
		 * get instance class
		 * @return WPEMS
		 */
		public static function instance() {
			if ( ! empty( self::$_instance ) ) {
				return self::$_instance;
			}

			return self::$_instance = new self();
		}

	}

	if ( ! function_exists( 'WPEMS' ) ) {

		function WPEMS() {
			return WPEMS::instance();
		}

	}
	WPEMS();
}


$GLOBALS['WPEMS'] = WPEMS();