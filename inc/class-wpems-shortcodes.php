<?php
/**
 * WP Events Manager Shortcodes class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

class WPEMS_Shortcodes {
	/**
	 * Init shortcodes
	 */
	public static function init() {
		add_action( 'tp_event_shortcode_wrapper_start', array( __CLASS__, 'shortcode_wrapper_start' ) );
		add_action( 'tp_event_shortcode_wrapper_end', array( __CLASS__, 'shortcode_wrapper_end' ) );

		$shortcodes = array(
			'list'            => __CLASS__ . '::event_list',
			'register'        => __CLASS__ . '::register',
			'login'           => __CLASS__ . '::login',
			'forgot_password' => __CLASS__ . '::forgot_password',
			'reset_password'  => __CLASS__ . '::reset_password',
			'account'         => __CLASS__ . '::account',
			'countdown'       => __CLASS__ . '::countdown',
			'calendars'       => __CLASS__ . '::event_calendars',
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
		if ( ! is_page() ) {
			return;
		}

		global $post;

		if ( ! isset( $post->ID ) ) {
			return;
		}

		$page_id = array();

		if ( $register_id = wpems_get_page_id( 'register' ) ) {
			$page_id[] = $register_id;
		}
		if ( $login_id = wpems_get_page_id( 'login' ) ) {
			$page_id[] = $login_id;
		}

		if ( is_user_logged_in() && in_array( $post->ID, $page_id ) ) {
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
	 * @param array $atts
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
	 * Shortcode user register
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public static function register( $atts ) {

		if ( ! wpems_get_page_id( 'register' ) ) {
			return '';
		}
		if ( ! get_option( 'users_can_register' ) ) {
			return WPEMS_Shortcodes::render( 'user-register', 'user-cannot-register.php' );
		} elseif ( ! empty( $_REQUEST['registered'] ) ) {
			$email = sanitize_email( $_REQUEST['registered'] );
			$user  = get_user_by( 'email', $email );
			if ( $user && $user->ID ) {
				wp_new_user_notification( $user->ID, null, 'user' );

				// register completed
				return WPEMS_Shortcodes::render( 'user-register', 'register-completed.php' );
			} else {
				// error
				return WPEMS_Shortcodes::render( 'user-register', 'register-error.php' );
			}
		} elseif ( ! is_user_logged_in() ) {
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
		if ( ! wpems_get_page_id( 'login' ) || is_user_logged_in() ) {
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
		if ( ! wpems_get_page_id( 'forgot_password' ) ) {
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
		if ( ! wpems_get_page_id( 'reset_password' ) ) {
			return '';
		}

		$atts = wp_parse_args(
			$atts,
			array(
				'key'   => isset( $_REQUEST['key'] ) ? sanitize_text_field( $_REQUEST['key'] ) : '',
				'login' => isset( $_REQUEST['login'] ) ? sanitize_text_field( $_REQUEST['login'] ) : '',
			)
		);

		$atts = wp_parse_args(
			$atts,
			array(
				'user_login'  => '',
				'redirect_to' => '',
				'checkemail'  => isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] === 'confirm' ? true : false,
			)
		);

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
					'value' => $user->ID,
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
				'event_id' => '',
			),
			$atts
		);

		return WPEMS_Shortcodes::render( 'event-countdown', 'event-countdown.php', array( 'args' => $atts ) );
	}

	/**
	 * Event list
	 * @param $atts
	 * @return string
	 */
	public static function event_list( $atts ) {
		try {
			$filter_by_input_search = '';
			$filter_by_status       = '';
			$filter_by_type         = '';
			$filter_by_category     = '';
			$filter_by_date         = array();
			$filter_by_price        = array();
			$order_by               = '';
			$getDateInput           = '';
			$getPriceMin            = '';
			$getPriceMax            = '';

			$events     = \WPEMS\Model\EventsModel::getInstance();
			$checkParam = new \WPEMS\Helper\CheckParam();

			// Get value from frontend
			if ( isset( $_GET['search_event_list'] ) ) {
				// Retrieve form input values
				$filter_by_input_search = $checkParam->get_param( 'wpems_keyword', 'GET' );
				$filter_by_status       = $checkParam->get_param( 'wpems_status', 'GET' );
				$filter_by_type         = $checkParam->get_param( 'wpems_type', 'GET' );
				$filter_by_category     = $checkParam->get_param( 'wpems_category', 'GET' );
				$getDateInput           = $checkParam->get_param( 'wpems_date', 'GET' );
				$filter_by_date         = explode( ' - ', $getDateInput );
				$getPriceMin            = $checkParam->get_param( 'wpems_price_min', 'GET' );
				$getPriceMax            = $checkParam->get_param( 'wpems_price_max', 'GET' );
				$filter_by_price        = [ $getPriceMin, $getPriceMax ];
			}
			$order_by = $checkParam->get_param( 'tp_event_order_by', 'GET' );

			// Give arguments to database
			$posts = $events->get_posts_filter(
				[
					'filter_by_input_search' => $filter_by_input_search,
					'filter_by_status'       => $filter_by_status,
					'filter_by_type'         => $filter_by_type,
					'filter_by_category'     => $filter_by_category,
					'filter_by_date'         => $filter_by_date,
					'filter_by_price'        => $filter_by_price,
					'order_by'               => $order_by,
				]
			);

			// Get data from database to send to frontend
			$get_types      = $events->get_types_categories( 'tp_event_type' );
			$get_categories = $events->get_types_categories( 'tp_event_category' );

			// Create an array of number for price input
			$number_array = array();
			for ( $i = 0; $i <= 500; $i += 10 ) {
				$number_array[] = $i;
			}

			// Give data to fronted to display on the screen
			$atts = shortcode_atts(
				array(
					'posts'                  => $posts,
					'filter_by_input_search' => $filter_by_input_search,
					'types'                  => $get_types,
					'filter_by_type'         => $filter_by_type,
					'categories'             => $get_categories,
					'filter_by_category'     => $filter_by_category,
					'filter_by_status'       => $filter_by_status,
					'dateInput'              => $getDateInput,
					'numbers'                => $number_array,
					'getPriceMin'            => $getPriceMin,
					'getPriceMax'            => $getPriceMax,
					'order_by'               => $order_by,
				),
				$atts
			);

			return WPEMS_Shortcodes::render( 'event-list', 'event-list.php', array( 'args' => $atts ) );
		} catch ( Exception $e ) {
			echo 'Something was wrong: ' . $e->getMessage();
		}
	}

	/**
	 * Event calendar
	 * @param $atts
	 * @return string
	 */
	public static function event_calendars() {
		return WPEMS_Shortcodes::render( 'event-calendar', 'event-calendar.php' );
	}
}

WPEMS_Shortcodes::init();
