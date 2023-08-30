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
// use Wpems_Model_Event;

/**
 * WPEMS_Shortcodes class
 */


class WPEMS_Shortcodes {

	public static $pageSize = 9;
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
			'list'            => __CLASS__ . '::event_list',
			'calendars'       => __CLASS__ . '::event_calendars',
			'sync'            => __CLASS__ . '::google_sync',
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
	 *

	 */
	public static function event_list( $atts ) {
		$filter_by_input_search = '';
		$filter_by_status       = '';
		$filter_by_type         = '';
		$filter_by_category     = '';
		$filter_by_date         = '';
		$filter_by_price        = '';
		$order_by               = '';
		$getDateInput           = '';
		$getPriceMin            = '';
		$getPriceMax            = '';
		// Get value from frontend
		if ( isset( $_GET['search_event_list'] ) ) {
			// Retrieve form input values
			$filter_by_input_search = WPEMS_Request_Pattern::get_param( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_SEARCH_CHAR, 'GET' );
			$filter_by_status       = WPEMS_Request_Pattern::get_param( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_STATUS, 'GET' );
			$filter_by_type         = WPEMS_Request_Pattern::get_param( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_TYPE, 'GET' );
			$filter_by_category     = WPEMS_Request_Pattern::get_param( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_CATEGORY, 'GET' );
			$getDateInput           = WPEMS_Request_Pattern::get_param( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_SEARCH_DATE, 'GET' );
			$filter_by_date         = explode( ' - ', $getDateInput );
			$getPriceMin            = WPEMS_Request_Pattern::get_param( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_PRICE_MIN, 'GET' );
			$getPriceMax            = WPEMS_Request_Pattern::get_param( \Wpems_Model_Event\WPEMS_Model_Event_List::$_FILTER_PRICE_MAX, 'GET' );
			$filter_by_price        = [ $getPriceMin, $getPriceMax ];
		}
		$order_by = WPEMS_Request_Pattern::get_param( 'tp_event_order_by', 'GET' );

		// Give arguments to database
		$get_posts = WPEMS_Frontend_Event_List_Data::get_posts_data(
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

		$posts = $get_posts->posts;
		$posts = WPEMS_Data_Pattern::get_postMeta( $posts );

		// Get data from database to send to frontend
		$get_types      = WPEMS_Data_Pattern::get_filter( 'tp_event_type' );
		$get_categories = WPEMS_Data_Pattern::get_filter( 'tp_event_category' );

		// Create an array of number for price input
		$number_array = array();
		for ( $i = 0; $i <= 500; $i += 10 ) {
			$number_array[] = $i;
		}

		$pageIndex          = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
		$current_item_start = 0;
		$current_item_end   = 0;
		$totalPost          = $get_posts->found_posts;
		$current_item_start = ( $pageIndex - 1 ) * self::$pageSize + 1;
		$current_item_end   = min( $current_item_start + $get_posts->post_count - 1, $totalPost );

		// Give data to fronted to display on the screen
		$atts = shortcode_atts(
			array(
				'query_posts'            => $get_posts,
				'posts'                  => $posts,
				'types'                  => $get_types,
				'categories'             => $get_categories,
				'numbers'                => $number_array,
				'totalPost'              => $totalPost,
				'pageIndex'              => $pageIndex,
				'current_item_start'     => $current_item_start,
				'current_item_end'       => $current_item_end,
				'dateInput'              => $getDateInput,
				'filter_by_input_search' => $filter_by_input_search,
				'filter_by_status'       => $filter_by_status,
				'filter_by_type'         => $filter_by_type,
				'filter_by_category'     => $filter_by_category,
				'getPriceMin'            => $getPriceMin,
				'getPriceMax'            => $getPriceMax,
				'order_by'               => $order_by,
			),
			$atts,
		);

		return WPEMS_Shortcodes::render( 'event-list', 'event-list-display.php', array( 'args' => $atts ) );
	}

		/**
	 * Sync to google calendar
	 *

	 */
	public static function google_sync( $atts ) {
		$eventData   = WPEMS_Google_Calendar::event_data();
		$bookingData = array();

		if ( ! empty( $eventData ) ) {
			foreach ( $eventData as $key => $value ) {
				$bookingData[] = array(
					'summary' => $value->post_content,
					'start'   => array(
						'dateTime' => str_replace( ' ', 'T', $value->post_date ),
						'timeZone' => 'UTC',
					),
					'end'     => array(
						'dateTime' => str_replace( ' ', 'T', $value->post_date ),
						'timeZone' => 'UTC',
					),
				);
			}
		}

		$atts = shortcode_atts(
			array(
				'bookingData' => $bookingData,
			),
			$atts
		);
		return WPEMS_Shortcodes::render( 'google-sync', 'google-calendars.php', array( 'args' => $atts ) );
	}

		/**
	 * Event calendar
	 *

	 */
	public static function event_calendars() {
		return WPEMS_Shortcodes::render( 'event', 'event-calendar.php' );
	}

}

WPEMS_Shortcodes::init();
