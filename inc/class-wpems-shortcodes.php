<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

/**
 * WPEMS_Shortcodes class
 */
class WPEMS_Shortcodes {

	/**
	 * Init shortcodes
	 */
	public static function init() {
		add_action( 'tp_event_shortcode_wrapper_start', array( __CLASS__, 'shortcode_wrapper_start' ) );
		add_action( 'tp_event_shortcode_wrapper_end', array( __CLASS__, 'shortcode_wrapper_end' ) );

		$shortcodes = array(
			'list_event'      => __CLASS__ . '::list_event',
			'register'        => __CLASS__ . '::register',
			'login'           => __CLASS__ . '::login',
			'forgot_password' => __CLASS__ . '::forgot_password',
			'reset_password'  => __CLASS__ . '::reset_password',
			'account'         => __CLASS__ . '::account',
			'countdown'       => __CLASS__ . '::countdown',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "wp_event_{$shortcode}_shortcode_tag", 'wp_event_' . $shortcode ), $function );
		}

		add_action( 'template_redirect', array( __CLASS__, 'auto_shortcode' ) );
	}

	/**
	 * Redirect page
	 */
	public static function auto_shortcode() {
		if ( !is_page() ) {
			return;
		}

		global $post;
		if ( is_user_logged_in() && in_array( $post->ID, array( wpems_get_page_id( 'register' ), wpems_get_page_id( 'login' ) ) ) ) {
			wp_safe_redirect( home_url( '/' ) );
		}
	}

	/**
	 * Shortcode wrapper start
	 *
	 * @param $shortcode
	 */
	public static function shortcode_wrapper_start( $shortcode ) {
		echo '<div class="event-wrapper-shortcode ' . esc_attr( $shortcode ) . '">';
	}

	/**
	 * Shortcode wrapper end
	 */
	public static function shortcode_wrapper_end() {
		echo '</div>';
	}

	/**
	 * Render shortcode
	 *
	 * @param string $shortcode
	 * @param string $template
	 * @param array  $atts
	 *
	 * @return string
	 */
	public static function render( $shortcode = '', $template = '', $atts = array() ) {
		ob_start();
		do_action( 'tp_event_shortcode_wrapper_start', $shortcode );
		wpems_get_template( 'shortcodes/' . $template, $atts );
		do_action( 'tp_event_shortcode_wrapper_end', $shortcode );
		return ob_get_clean();
	}

	/**
	 * Shortcode show list event
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public static function list_event( $atts ) {
		$args = array( 'post_type' => 'tp_event' );
		return WPEMS_Shortcodes::render( 'list-event', 'event-list.php', array( 'args' => $args ) );
	}


	/**
	 * Shortcode user register
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public static function register( $atts ) {

		if ( !wpems_get_page_id( 'register' ) ) {
			return '';
		}
		if ( !get_option( 'users_can_register' ) ) {
			return WPEMS_Shortcodes::render( 'user-register', 'user-cannot-register.php' );
		} elseif ( !empty( $_REQUEST['registered'] ) ) {
			$email = sanitize_email( $_REQUEST['registered'] );
			$user  = get_user_by( 'email', $email );
			if ( $user && $user->ID ) {
				wp_new_user_notification( $user->ID );
				// register completed
				return WPEMS_Shortcodes::render( 'user-register', 'register-completed.php' );
			} else {
				// error
				return WPEMS_Shortcodes::render( 'user-register', 'register-error.php' );
			}
		} elseif ( !is_user_logged_in() ) {
			// show register form
			return WPEMS_Shortcodes::render( 'user-register', 'form-register.php' );
		}

		return '';
	}

	/**
	 * Shortcode user login
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public static function login( $atts ) {
		if ( !wpems_get_page_id( 'login' ) || is_user_logged_in() ) {
			return '';
		}

		return WPEMS_Shortcodes::render( 'user-login', 'form-login.php' );
	}

	/**
	 * Shortcode forgot password
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public static function forgot_password( $atts ) {
		if ( !wpems_get_page_id( 'forgot_password' ) ) {
			return '';
		}

		$checkemail = isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] === 'confirm' ? true : false;
		if ( $checkemail ) {
			wpems_add_notice( 'success', __( 'Check your email for a link to reset your password.', 'wp-events-manager' ) );
		} else {
			return WPEMS_Shortcodes::render( 'forgot-password', 'forgot-password.php' );
		}
		return '';
	}

	/**
	 * Shortcode reset password
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public static function reset_password( $atts ) {
		if ( !wpems_get_page_id( 'reset_password' ) ) {
			return '';
		}

		$atts = wp_parse_args( $atts, array(
			'key'   => isset( $_REQUEST['key'] ) ? sanitize_text_field( $_REQUEST['key'] ) : '',
			'login' => isset( $_REQUEST['login'] ) ? sanitize_text_field( $_REQUEST['login'] ) : ''
		) );

		$atts = wp_parse_args( $atts, array(
			'user_login'  => '',
			'redirect_to' => '',
			'checkemail'  => isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] === 'confirm' ? true : false
		) );

		if ( $atts['checkemail'] ) {
			wpems_add_notice( 'success', __( 'Check your email for a link to reset your password.', 'wp-events-manager' ) );
		}
		return WPEMS_Shortcodes::render( 'reset-password', 'reset-password.php', array( 'atts' => $atts ) );

	}

	/**
	 * Shortcode user account
	 *
	 * @return string
	 */
	public static function account( $atts ) {
		$user = wp_get_current_user();
		$args = array(
			'post_type'     => 'event_auth_book',
			'post_per_page' => - 1,
			'order'         => 'DESC',
			'meta_query'    => array(
				array(
					'key'   => 'ea_booking_user_id',
					'value' => $user->ID
				),
			),
		);
		return WPEMS_Shortcodes::render( 'user-account', 'user-account.php', array( 'args' => $args ) );
	}

	/**
	 * Countdown time for event
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public static function countdown( $atts ) {
		$atts = shortcode_atts(
			array(
				'event_id' => ''
			), $atts
		);

		return WPEMS_Shortcodes::render( 'event-countdown', 'event-countdown.php', array( 'args' => $atts ) );
	}

}

WPEMS_Shortcodes::init();