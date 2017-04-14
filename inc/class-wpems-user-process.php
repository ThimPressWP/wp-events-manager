<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class WPEMS_User_Process {

	private static $login_url = null;
	private static $register_url = null;
	private static $forgot_url = null;
	private static $account_url = null;
	private static $reset_url = null;
	private static $session = null;

	public static function init() {
		/**
		 * Process Register
		 * Login
		 * Lost Password
		 * Reset Password
		 */
		add_action( 'init', array( __CLASS__, 'user_process_init' ), 10 );
		add_action( 'init', array( __CLASS__, 'process_register' ), 50 );
		add_action( 'init', array( __CLASS__, 'process_login' ), 50 );
		add_action( 'init', array( __CLASS__, 'process_lost_password' ), 50 );
		add_action( 'init', array( __CLASS__, 'process_reset_password' ), 50 );

		// process
		add_action( 'wp_logout', array( __CLASS__, 'wp_logout' ) );
	}

	public static function user_process_init() {

		self::$login_url = wpems_login_url();

		self::$register_url = wpems_register_url();

		self::$forgot_url = wpems_forgot_password_url();

		self::$account_url = wpems_account_url();

		self::$reset_url = wpems_reset_password_url();
	}

	// redirect logout
	public static function wp_logout() {
		wpems_add_notice( 'success', sprintf( '%s', __( 'You have been sign out!', 'wp-events-manager' ) ) );
		wp_safe_redirect( self::$login_url );
		exit();
	}

	/**
	 * Process Register
	 */
	public static function process_register() {
		if ( empty( $_POST['auth-nonce'] ) || !wp_verify_nonce( $_POST['auth-nonce'], 'auth-reigter-nonce' ) ) {
			return;
		}

		$username  = !empty( $_POST['user_login'] ) ? $_POST['user_login'] : '';
		$email     = !empty( $_POST['user_email'] ) ? $_POST['user_email'] : '';
		$password  = !empty( $_POST['user_pass'] ) ? $_POST['user_pass'] : '';
		$password1 = !empty( $_POST['confirm_password'] ) ? $_POST['confirm_password'] : '';

		$user_id = wpems_create_new_user( apply_filters( 'event_auth_user_process_register_data', array(
			'username' => $username, 'email' => $email, 'password' => $password, 'confirm_password' => $password1
		) ) );

		if ( is_wp_error( $user_id ) ) {
			$fields = array();
			foreach ( $user_id->errors as $code => $message ) {
				if ( !$message[0] )
					continue;
				if ( wpems_is_ajax() ) {
					$fields[$code] = $message[0];
				} else {
					wpems_add_notice( 'error', $message[0] );
				}
			}
			if ( wpems_is_ajax() ) {
				wp_send_json( array( 'status' => false, 'fields' => $fields ) );
			}
		} else {

			$url = wp_get_referer();
			if ( !$url ) {
				$url = self::$register_url;
			}

			// not enable option 'register_notify' login user now
			$send_notify = wpems_get_option( 'register_notify', true );
			if ( !$send_notify ) {
				wp_set_auth_cookie( $user_id, true, is_ssl() );
			} else {
				$url = add_query_arg( 'registered', $email, self::$register_url );
			}

			if ( wpems_is_ajax() ) {
				wp_send_json( array( 'status' => true, 'redirect' => $url ) );
			} else {
				wp_safe_redirect( $url );
				exit();
			}
		}
	}

	/**
	 * Process Login
	 */
	public static function process_login() {

		$nonce_value = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( $_POST['_wpnonce'] ) : '';
		$nonce_value = isset( $_POST['auth-nonce'] ) ? sanitize_text_field( $_POST['auth-nonce'] ) : $nonce_value;

		if ( !wp_verify_nonce( $nonce_value, 'auth-login-nonce' ) ) {
			return;
		}
		$redirect = self::$account_url;
		if ( !empty( $_POST['redirect_to'] ) && $_POST['redirect_to'] !== '/wp-admin/admin-ajax.php' ) {
			$redirect = esc_url( $_POST['redirect_to'] );
		} elseif ( wp_get_referer() ) {
			$redirect = wp_get_referer();
		}

		$redirect = strpos( $redirect, '/wp-admin/admin-ajax.php' ) ? self::$account_url : $redirect;

		try {

			$creds    = array();
			$username = !empty( $_POST['user_login'] ) ? sanitize_text_field( trim( $_POST['user_login'] ) ) : '';
			$password = !empty( $_POST['user_pass'] ) ? sanitize_text_field( trim( $_POST['user_pass'] ) ) : '';

			$validation_error = new WP_Error();
			$validation_error = apply_filters( 'event_auth_process_login_errors', $validation_error, $username, $password );

			if ( $validation_error->get_error_code() ) {
				wpems_add_notice( 'error', '<strong>' . __( 'ERROR', 'wp-events-manager' ) . ':</strong> ' . $validation_error->get_error_message() );
			}

			if ( empty( $username ) ) {
				wpems_add_notice( 'error', '<strong>' . __( 'ERROR', 'wp-events-manager' ) . ':</strong> ' . __( 'Username is required.', 'wp-events-manager' ) );
			}

			if ( empty( $_POST['user_pass'] ) ) {
				wpems_add_notice( 'error', '<strong>' . __( 'ERROR', 'wp-events-manager' ) . ':</strong> ' . __( 'Password is required.', 'wp-events-manager' ) );
			}

			if ( is_email( $username ) && apply_filters( 'event_auth_get_username_from_email', true ) ) {
				$user = get_user_by( 'email', $username );

				if ( isset( $user->user_login ) ) {
					$creds['user_login'] = $user->user_login;
				} else {
					wpems_add_notice( 'error', '<strong>' . __( 'ERROR', 'wp-events-manager' ) . ':</strong> ' . __( 'A user could not be found with this email address.', 'wp-events-manager' ) );
				}
			} else {
				$creds['user_login'] = $username;
			}

			$creds['user_password'] = $password;
			$creds['remember']      = isset( $_POST['rememberme'] );
			$secure_cookie          = is_ssl() ? true : false;

			if ( !wpems_has_notice( 'error' ) ) {
				$user = wp_signon( apply_filters( 'event_auth_login_credentials', $creds ), $secure_cookie );

				if ( is_wp_error( $user ) ) {
					$message = $user->get_error_message();
					$message = str_replace( wp_lostpassword_url(), self::$forgot_url, $message );
					$message = str_replace( '<strong>' . esc_html( $creds['user_login'] ) . '</strong>', '<strong>' . esc_html( $username ) . '</strong>', $message );
					wpems_add_notice( 'error', $message );

					// break
					throw new Exception;
				} else {
					wpems_add_notice( 'success', __( 'You have logged in', 'wp-events-manager' ) );

					if ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) {
						wp_redirect( apply_filters( 'event_auth_login_redirect', $redirect, $user ) );
						exit;
					} else {
						$response             = array();
						$response['status']   = true;
						$response['redirect'] = apply_filters( 'event_auth_ajax_login_redirect', $redirect );
						ob_start();
						wpems_print_notices();
						$response['notices'] = ob_get_clean();
						wp_send_json( $response );
					}
				}
			}
		} catch ( Exception $ex ) {
			if ( $ex ) {
				wpems_add_notice( 'error', $ex->getMessage() );
			}
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$response             = array();
			$response['status']   = false;
			$response['redirect'] = apply_filters( 'event_auth_ajax_login_redirect', $redirect );
			ob_start();
			wpems_print_notices();
			$response['notices'] = ob_get_clean();
			wp_send_json( $response );
		}
	}

	/**
	 * Process Lost Password
	 */
	public static function process_lost_password() {

	}

	/**
	 * Process Reset Password
	 */
	public static function process_reset_password() {

	}

}

WPEMS_User_Process::init();
