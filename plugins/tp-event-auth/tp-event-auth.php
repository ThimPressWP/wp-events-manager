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
            $this->init_hooks();
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
         * @param type $name string
         * @param type $value mixed
         */
        public function set_define( $name, $value = '' ) {
            if ( $name && !defined( $name ) ) {
                define( $name, $value );
            }
        }

        /**
         * Init hook
         * @since 1.0.3
         */
        public function init_hooks() {
            // plugins_loaded hook
            add_action( 'plugins_loaded', array( $this, 'loaded' ) );

            // init this plugin hook
            add_action( 'init', array( $this, 'event_auth_init' ), 0 );
            add_action( 'event_auth_loaded', array( $this, 'event_auth_loaded' ), 1 );
            register_activation_hook( plugin_basename( __FILE__ ), array( $this, 'install' ) );
            register_deactivation_hook( plugin_basename( __FILE__ ), array( $this, 'uninstall' ) );
        }

        /**
         * load text domain
         * include file
         * @return null
         */
        public function loaded() {
            // load text domain
            $this->load_textdomain();

            // is tp-event plugin installed and actived
            $this->is_active();
        }

        /**
         * install plugin hook
         */
        public function install() {
            if ( function_exists( 'event_create_page' ) ) {
                $this->_include( 'class-auth-install.php' );
                Auth_Install::install();
            }
        }

        /**
         * uninstall plugin hook
         */
        public function uninstall() {
            if ( function_exists( 'event_create_page' ) ) {
                $this->_include( 'class-auth-install.php' );
                Auth_Install::uninstall();
            }
        }

        /**
         * load text domain
         * @return boolean
         */
        public function load_textdomain() {
            // current locale
            $locale = get_locale();

            $default = WP_LANG_DIR . '/plugins/tp-event-auth-' . $locale . '.mo';
            $mofile = $plugin = TP_EVENT_AUTH_PATH . '/languages/tp-event-auth-' . $locale . '.mo';

            if ( file_exists( $default ) ) {
                $mofile = $default;
            }

            load_textdomain( 'tp-event-auth', $mofile );
        }

        /**
         * is_active TP Event
         * @return boolean
         */
        public function is_active() {

            if ( !function_exists( 'get_plugin_data' ) ) {
                require_once ABSPATH . '/wp-admin/includes/plugin.php';
            }

            if ( class_exists( 'TP_Event' ) && ( is_plugin_active( 'tp-event/tp-event.php' ) || is_plugin_active( 'wp-event/wp-event.php' ) ) ) {
                $this->is_active = true;
            }

            if ( !$this->is_active ) {
                add_action( 'admin_notices', array( $this, 'admin_notice' ) );
            } else {
                $this->includes();

                do_action( 'event_auth_loaded', $this );
            }
        }

        /**
         * include files needed
         */
        private function includes() {
            $this->_include( 'class-auth-autoloader.php' );
            $this->_include( 'class-auth-ajax.php' );
            $this->_include( 'class-auth-post-types.php' );
            $this->_include( 'functions.php' );
            $this->_include( 'gateways/class-auth-abstract-payment-gateway.php' );
            $this->_include( 'emails/class-auth-event-register-event.php' );
            if ( is_admin() ) {
                $this->_include( 'admin/class-auth-admin.php' );
            } else {
                $this->_include( 'template-hook.php' );
                $this->_include( 'class-auth-authentication.php' );
                $this->_include( 'class-auth-shortcodes.php' );
            }

            #enqueue script
            if ( !is_admin() ) {
                add_action( 'event_before_enqueue_scripts', array( $this, 'register_scripts' ) );
            }
            $this->_include( 'class-auth-install.php' );
        }

        /**
         * event_auth_init hook
         * @return
         */
        public function event_auth_init() {
            if ( !$this->is_active ) {
                return;
            }

            do_action( 'event_auth_init', $this );
        }

        /**
         * event auth loaded hook
         * @return initialize object Auth, Loader, Settings
         */
        public function event_auth_loaded() {
            /**
             * session
             */
            $this->session = new Auth_Session();
        }

        /**
         * payment gateways
         * @return type Auth_Payment_Gateways
         */
        public function payment_gateways() {
            return Auth_Payment_Gateways::instance();
        }

        /**
         * admin notice
         * @return message string
         */
        public function admin_notice() {
            $this->_include( 'admin/views/notices.php' );
        }

        /**
         * enqueue asset files
         * @param type $hook
         */
        public function register_scripts( $hook ) {
            Event_Assets::register_style( 'tp-event-auth', TP_EVENT_AUTH_ASSETS_URI . '/css/site.css', array() );
            Event_Assets::register_script( 'tp-event-auth', TP_EVENT_AUTH_ASSETS_URI . '/js/site.js', array(), TP_EVENT_AUTH_VER, true );
            Event_Assets::localize_script( 'tp-event-auth', 'event_auth_object', apply_filters( 'event_auth_object', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'something_wrong' => __( 'Something went wrong.', 'tp-event-auth' ),
                'register_button'   => wp_create_nonce( 'event-auth-register-nonce' )
            ) ) );
            Event_Assets::register_style( 'tp-event-auth-magnific-popup', TP_EVENT_AUTH_ASSETS_URI . '/magnific-popup/magnific-popup.css', array() );
            Event_Assets::register_script( 'tp-event-auth-popup', TP_EVENT_AUTH_ASSETS_URI . '/magnific-popup/jquery.magnific-popup.js', array(), TP_EVENT_AUTH_VER, true );
        }

        /**
         * _include
         * @param  boolean $file
         * @param  boolean $require
         * @param  boolean $unique
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
