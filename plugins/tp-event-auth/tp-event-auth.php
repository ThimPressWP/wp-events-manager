<?php

/*
  Plugin Name: Thim Event Authentication
  Plugin URI: http://thimpress.com/thim-event-auth
  Description: Authentication
  Author: ThimPress
  Version: 1.0.4
  Author URI: http://thimpress.com
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'TP_Event_Authentication' ) ) {

	final class TP_Event_Authentication {

		/**
		 * $is_active
		 * @var boolean
		 */
		public $is_active = false;

		/**
		 * session class
		 * @var type object
		 */
		public $session = null;

		/**
		 * $loader
		 * @var null
		 */
		public $loader = null;

		/**
		 * $instance
		 * @var null
		 */
		static $instance = null;

		/**
		 * __construct
		 * @plugins_loaded hoook
		 */
		public function __construct() {
			$this->define_constants();
		}

		/**
		 * Define Plugins Constants
		 */
		public function define_constants() {
			$this->set_define( 'TP_EVENT_AUTH_FILE', __FILE__ );
			$this->set_define( 'TP_EVENT_AUTH_PATH', plugin_dir_path( __FILE__ ) );
			$this->set_define( 'TP_EVENT_AUTH_URI', plugin_dir_url( __FILE__ ) );
			$this->set_define( 'TP_EVENT_AUTH_INC', TP_EVENT_AUTH_PATH . 'inc' );
			$this->set_define( 'TP_EVENT_AUTH_INC_URI', TP_EVENT_AUTH_URI . 'inc' );
			$this->set_define( 'TP_EVENT_AUTH_ASSETS_URI', TP_EVENT_AUTH_URI . 'assets' );
			$this->set_define( 'TP_EVENT_AUTH_LIB_URI', TP_EVENT_AUTH_INC_URI . '/libraries' );
			$this->set_define( 'TP_EVENT_AUTH_PLUGIN_FILE', plugin_basename( __FILE__ ) );
			$this->set_define( 'TP_EVENT_AUTH_VER', '1.0.4' );
		}

		/**
		 * set single constant
		 *
		 * @param type $name  string
		 * @param type $value mixed
		 */
		public function set_define( $name, $value = '' ) {
			if ( $name && !defined( $name ) ) {
				define( $name, $value );
			}
		}


		/**
		 * _include
		 *
		 * @param  boolean $file
		 * @param  boolean $require
		 * @param  boolean $unique
		 *
		 * @return null
		 */
		public function _include( $file = false, $require = true, $unique = true ) {
			$file = TP_EVENT_AUTH_INC . '/' . $file;
			if ( $file && file_exists( $file ) ) {
				if ( $unique ) {
					if ( $require ) {
						require_once $file;
					} else {
						include_once $file;
					}
				} else {
					if ( $require ) {
						require $file;
					} else {
						include $file;
					}
				}
			}
		}

		/**
		 * getInstance instead of new class
		 * @return object class
		 */
		public static function getInstance() {

			if ( !empty( self::$instance ) ) {
				return self::$instance;
			}

			return self::$instance = new self();
		}

	}

	if ( !function_exists( 'TP_Event_Authentication' ) ) {

		function TP_Event_Authentication() {
			return TP_Event_Authentication::getInstance();
		}

	}

	//initialize plugins
	TP_Event_Authentication();
}
