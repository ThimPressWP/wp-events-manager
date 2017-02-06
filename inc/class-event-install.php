<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class TP_Event_Install {

	/**
	 * upgrade store
	 * @var type null || array
	 */
	public static $db_upgrade = null;

	/**
	 * Init
	 */
	public static function init() {
		self::$db_upgrade = array(
			'2.0' => TP_EVENT_INC . 'admin/upgrades/upgrade-2.0.php'
		);
	}

	/**
	 * register_activation_hook callback
	 */
	public static function install() {
		if ( !defined( 'TP_EVENT_INSTALLING' ) ) {
			define( 'TP_EVENT_INSTALLING', true );
			if ( !function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}

			$active_plugins = get_option( 'active_plugins', true );
			if ( ( $key = array_search( 'tp-event-auth/tp-event-auth.php', $active_plugins ) ) !== false ) {
				unset( $active_plugins[$key] );
			}
			update_option( 'active_plugins', $active_plugins );
		}
		/**
		 * Upgrade options
		 */
		self::upgrade_database();
		/**
		 * Add new roles
		 */
		Event_Roles::add_roles();
		/**
		 * Add new caps
		 */
		Event_Roles::add_caps();
		/**
		 * Create Pages
		 */
		self::create_pages();
		/**
		 * Update current version
		 */
		update_option( 'thimpress-event-version', TP_EVENT_VER );
	}

	/**
	 * register_deactivation_hook callback
	 */
	public static function uninstall() {
		/**
		 * Remove Caps and Roles
		 */
		Event_Roles::remove_roles();
	}


	/**
	 * Create pages
	 */
	public static function create_pages() {
		$pages = array(
			'register'        => array(
				'name'    => _x( 'user-register', 'Page slug', 'tp-event' ),
				'title'   => _x( 'User Register', 'Page title', 'tp-event' ),
				'content' => '[' . apply_filters( 'tp_event_register_shortcode_tag', 'tp_event_register' ) . ']'
			),
			'login'           => array(
				'name'    => _x( 'user-login', 'Page slug', 'tp-event' ),
				'title'   => _x( 'User Login', 'Page title', 'tp-event' ),
				'content' => '[' . apply_filters( 'tp_event_login_shortcode_tag', 'tp_event_login' ) . ']'
			),
			'forgot_password' => array(
				'name'    => _x( 'forgot-password', 'Page slug', 'tp-event' ),
				'title'   => _x( 'Forgot Password', 'Page title', 'tp-event' ),
				'content' => '[' . apply_filters( 'tp_event_forgot_password_shortcode_tag', 'tp_event_forgot_password' ) . ']'
			),
			'reset_password'  => array(
				'name'    => _x( 'reset-password', 'Page slug', 'tp-event' ),
				'title'   => _x( 'Reset Password', 'Page title', 'tp-event' ),
				'content' => '[' . apply_filters( 'tp_event_reset_password_shortcode_tag', 'tp_event_reset_password' ) . ']'
			),
			'account'         => array(
				'name'    => _x( 'user-account', 'Page slug', 'tp-event' ),
				'title'   => _x( 'User Account', 'Page title', 'tp-event' ),
				'content' => '[' . apply_filters( 'tp_event_account_shortcode_tag', 'tp_event_account' ) . ']'
			),
		);
		foreach ( $pages as $name => $page ) {
			tp_event_create_page( esc_sql( $page['name'] ), $name . '_page_id', $page['title'], $page['content'], !empty( $page['parent'] ) ? tp_event_get_page_id( $page['parent'] ) : '' );
		}
	}

	/**
	 * Upgrade Database
	 */
	public static function upgrade_database() {
		$old_version = get_option( 'thimpress-event-version' );
		foreach ( self::$db_upgrade as $ver => $file ) {
			if ( !$old_version || version_compare( $old_version, $ver, '<' ) ) {
				require_once $file;
			}
		}
	}

}

TP_Event_Install::init();

// active plugin
register_activation_hook( TP_EVENT_MAIN_FILE, array( 'TP_Event_Install', 'install' ) );
register_deactivation_hook( TP_EVENT_MAIN_FILE, array( 'TP_Event_Install', 'uninstall' ) );
