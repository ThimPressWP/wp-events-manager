<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class Auth_Install {

	public static function init() {

	}

	/**
	 * install hook
	 */
	public static function install() {
		if ( !defined( 'TP_EVENT_AUTH_INSTALLING' ) ) {
			define( 'TP_EVENT_AUTH_INSTALLING', true );
		}
		/**
		 * Create pages
		 */
		self::create_pages();

		/**
		 * create roles
		 */
		self::create_roles();

		/**
		 * create options
		 */
//		self::create_options();

		/**
		 * update event auth version
		 */
		update_option( 'event_auth_version', TP_EVENT_VER );
	}

	/**
	 * unstall hook
	 */
	public static function uninstall() {

	}

	/**
	 * Create default pages
	 */
	public static function create_pages() {
		$pages = array(
			'register' => array(
				'name'    => _x( 'auth-register', 'Page slug', 'tp-event' ),
				'title'   => _x( 'Auth Register', 'Page title', 'tp-event' ),
				'content' => '[' . apply_filters( 'event_auth_register_shortcode_tag', 'event_auth_register' ) . ']'
			),
			'login'    => array(
				'name'    => _x( 'auth-login', 'Page slug', 'tp-event' ),
				'title'   => _x( 'Auth Login', 'Page title', 'tp-event' ),
				'content' => '[' . apply_filters( 'event_auth_login_shortcode_tag', 'event_auth_login' ) . ']'
			),
			'account'  => array(
				'name'    => _x( 'auth-account', 'Page slug', 'tp-event' ),
				'title'   => _x( 'Auth Account', 'Page title', 'tp-event' ),
				'content' => '[' . apply_filters( 'event_auth_my_account_shortcode_tag', 'event_auth_my_account' ) . ']'
			),
//            'reset_password' => array(
//                'name' => _x( 'auth-resetpass', 'Page slug', 'tp-event' ),
//                'title' => _x( 'Auth Reset Password', 'Page title', 'tp-event' ),
//                'content' => '[' . apply_filters( 'event_auth_reset_password_shortcode_tag', 'event_auth_reset_password' ) . ']'
//            ),
//            'forgot_pass' => array(
//                'name' => _x( 'auth-forgot-password', 'Page slug', 'tp-event' ),
//                'title' => _x( 'Auth Forgot Password', 'Page title', 'tp-event' ),
//                'content' => '[' . apply_filters( 'event_auth_forgot_password_shortcode_tag', 'event_auth_forgot_password' ) . ']'
//            )
		);

		foreach ( $pages as $name => $page ) {
			tp_event_create_page( esc_sql( $page['name'] ), $name . '_page_id', $page['title'], $page['content'], !empty( $page['parent'] ) ? tp_event_get_page_id( $page['parent'] ) : '' );
		}
	}

	/**
	 * Create roles
	 */
	public static function create_roles() {

	}

	/**
	 * Remove roles
	 */
	public static function remove_roles() {

	}

	/**
	 * create options
	 * @since 1.0.3
	 */
	public static function create_options() {

		if ( !class_exists( 'Auth_Admin_Settings' ) ) {
			require_once TP_EVENT_PATH . 'inc/admin/class-auth-admin-settings.php';
		}
		$setting_pages = Auth_Admin_Settings::setting_pages();
		foreach ( $setting_pages as $setting ) {
			$options = $setting->get_settings();
			continue;
			foreach ( $options as $option ) {
				if ( !empty ( $option['id'] ) && !get_option( $option['id'] ) && !empty( $option['default'] ) ) {
					update_option( $option['id'], $option['default'] );
				}
			}
		}
	}

}

Auth_Install::init();
