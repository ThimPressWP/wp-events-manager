<?php
/**
 * Plugin Name: WP Events Manager
 * Plugin URI: http://thimpress.com/
 * Description: A complete plugin for Events management and online booking system
 * Author: ThimPress
 * Version: 2.2.4
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author URI: http://thimpress.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Load Composer autoloader for PSR-4 namespaced classes.
 *
 * @since 2.3.0
 */
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

if ( class_exists( \WPEMS\Payments\PaymentGatewayRegistry::class ) ) {
	\WPEMS\Payments\PaymentGatewayRegistry::bootstrap();
}

if ( class_exists( \WPEMS\Payments\PaymentWebhookRouter::class ) ) {
	\WPEMS\Payments\PaymentWebhookRouter::bootstrap();
}

if ( class_exists( \WPEMS\Payments\PaymentReturnRouter::class ) ) {
	\WPEMS\Payments\PaymentReturnRouter::bootstrap();
}

if ( class_exists( \WPEMS\BookingSystemBootstrap::class ) ) {
	add_action(
		'plugins_loaded',
		array( \WPEMS\BookingSystemBootstrap::class, 'init' ),
		30
	);

	if ( class_exists( \WPEMS\Cron\CronBootstrap::class ) ) {
		register_activation_hook(
			__FILE__,
			array( \WPEMS\Cron\CronBootstrap::class, 'activate_static' )
		);
		register_deactivation_hook(
			__FILE__,
			array( \WPEMS\Cron\CronBootstrap::class, 'deactivate_static' )
		);
	}
}

/**
 * WPEMS class
 */
if ( ! class_exists( 'WPEMS' ) ) {

	final class WPEMS {

		private static $_instance = null;

		public $_session = null;

		public $settings = null;

		/**
		 * WPEMS constructor.
		 */
		public function __construct() {
			try {
				$this->define_constants();
				$this->includes();
				$this->init_hooks();
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}
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
			$this->set_define( 'WPEMS_VER', '2.2.4' );
			$this->set_define( 'WPEMS_MAIN_FILE', __FILE__ );
		}

		public function set_define( $name = '', $value = '' ) {
			if ( $name && ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Init hooks plugins
		 *
		 * @since 2.0
		 */
		public function init_hooks() {
			// plugin loaded
			add_action( 'init', array( $this, 'loaded' ) );
			add_action( 'init', array( $this, 'included_files_when_plugins_loaded' ), 20 );
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
		 * Include files.
		 */
		public function includes() {

			$this->_include( 'inc/wpems-core-functions.php' );
			$this->_include( 'inc/class-wpems-autoloader.php' );
			$this->_include( 'inc/class-wpems-assets.php' );
			$this->_include( 'inc/class-wpems-ajax.php' );
			$this->_include( 'inc/class-wpems-post-types.php' );
			$this->_include( 'inc/emails/class-wpems-register-event.php' );
			// $this->_include( 'inc/class-wpems-payment-gateways.php' );
			$this->_include( 'inc/class-wpems-install.php' );
			$this->_include( 'inc/class-wpems-settings.php' );
			$this->_include( 'inc/class-wpems-session.php' );
			$this->_include( 'inc/class-wpems-booking.php' );
			$this->_include( 'inc/class-wpems-event.php' );
			$this->settings = WPEMS_Settings::instance();

			if ( is_admin() ) {
				\WPEMS\Admin\Admin::init();
			} else {
				$this->_include( 'inc/class-wpems-template.php' );
				$this->_include( 'inc/class-wpems-frontend-assets.php' );
				$this->_include( 'inc/class-wpems-user-process.php' );
				$this->_include( 'inc/class-wpems-shortcodes.php' );
			}

			$this->_include( 'inc/class-wpems-gdpr.php' );

			// Load addons
			do_action( 'wpems-plugin-ready' );
		}
		/**
		 * Include files when plugins loaded. Prevent translation loading for the wp-events-manager domain was triggered too early.
		 *
		 * @return void
		 * @since 2.2.1
		 */
		public function included_files_when_plugins_loaded() {
			require_once WPEMS_PATH . 'inc/class-wpems-payment-gateways.php';
		}

		/**
		 * Include single file
		 *
		 * @param $file
		 */
		public function _include( $file = null ) {
			if ( is_array( $file ) ) {
				foreach ( $file as $key => $f ) {
					$this->include_plugin_file( $f );
				}
			} else {
				$this->include_plugin_file( $file );
			}
		}

		/**
		 * Include a plugin file only when it resolves inside this plugin.
		 *
		 * @param string|null $file Relative plugin file path.
		 *
		 * @return bool
		 */
		private function include_plugin_file( $file = null ) {
			if ( ! $file || ! is_string( $file ) ) {
				return false;
			}

			$base_path = realpath( WPEMS_PATH );
			$file_path = realpath( WPEMS_PATH . ltrim( $file, '/\\' ) );
			if ( ! $base_path || ! $file_path || 0 !== strpos( $file_path, $base_path . DIRECTORY_SEPARATOR ) ) {
				return false;
			}

			require_once $file_path;

			return true;
		}

		/**
		 * load text domain
		 *
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
		 *
		 * @return WPEMS
		 */
		public static function instance() {
			if ( ! empty( self::$_instance ) ) {
				return self::$_instance;
			}

			self::$_instance = new self();
			return self::$_instance;
		}
	}

	if ( ! function_exists( 'WPEMS' ) ) {

		// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
		function WPEMS() {
			return WPEMS::instance();
		}
	}
	WPEMS();
}


$GLOBALS['WPEMS'] = WPEMS();
