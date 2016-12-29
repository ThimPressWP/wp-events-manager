<?php

/*
  Plugin Name: Thim Events
  Plugin URI: http://thimpress.com/thim-event
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

		/**
		 * single define
		 */
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
			// plugin init
//			add_action( 'init', array( $this, 'tp_event_init' ), 0 );
			// plugin loaded
			add_action( 'plugins_loaded', array( $this, 'loaded' ) );
			// event auth loaded
			add_action( 'event_auth_loaded', array( $this, 'event_auth_loaded' ), 1 );

			// init this plugin hook
			register_activation_hook( plugin_basename( __FILE__ ), array( $this, 'install' ) );
			register_deactivation_hook( plugin_basename( __FILE__ ), array( $this, 'uninstall' ) );
		}

		/**
		 * Load components when plugin loaded
		 */
		public function loaded() {
			// load text domain
			$this->text_domain();

			// load event auth
			do_action( 'event_auth_loaded', $this );

			// add notice
			$this->admin_notice();
		}

		/**
		 * include file
		 *
		 * @param  array || string
		 *
		 * @return null
		 */
		public function includes() {

			$this->_include( 'inc/class-event-autoloader.php' );
			$this->_include( 'inc/class-auth-autoloader.php' );
			$this->_include( 'inc/class-event-assets.php' );
			$this->_include( 'inc/class-event-ajax.php' );
			$this->_include( 'inc/tp-event-core-functions.php' );
			$this->_include( 'inc/class-event-setting.php' );

			$this->_include( 'inc/class-event-custom-post-types.php' );

			$this->_include( 'inc/class-auth-post-types.php' );
			$this->_include( 'inc/gateways/class-event-abstract-payment-gateway.php' );

			$this->_include( 'inc/gateways/paypal/class-event-payment-gateway-paypal.php' );

			$this->_include( 'inc/admin/metaboxes/class-event-admin-metabox-booking-information.php' );

			$this->_include( 'inc/emails/class-auth-event-register-event.php' );


			if ( is_admin() ) {
				$this->_include( 'inc/admin/class-event-admin.php' );
				$this->_include( 'inc/admin/class-auth-admin.php' );
			} else {
				$this->_include( 'inc/class-event-template.php' );
				$this->_include( 'inc/class-event-frontend-scripts.php' );
				$this->_include( 'inc/shortcodes/class-event-shortcode-countdown.php' );

				$this->_include( 'inc/tp-event-template-hook.php' );
				$this->_include( 'inc/class-auth-authentication.php' );
				$this->_include( 'inc/class-auth-shortcodes.php' );
			}

			$this->_include( 'inc/class-event-install.php' );
		}

		/**
		 * payment gateways
		 * @return  TP_Event_Payment_Gateways
		 */
		public function payment_gateways() {
			return Event_Payment_Gateways::instance();
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
		 * Session
		 */
		public function event_auth_loaded() {
			$this->_session = new Event_Session();
		}

		/**
		 * admin notice
		 * @return string
		 */
		public function admin_notice() {
			$this->_include( 'admin/views/notices.php' );
		}


		public function tp_event_init() {
			do_action( 'tp_event_init', $this );
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

	if ( !function_exists( 'tp_event' ) ) {

		function tp_event() {
			return TP_Event::instance();
		}

	}
	tp_event();
}