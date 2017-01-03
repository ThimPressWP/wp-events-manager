<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajax Process
 */
class Event_Ajax {

	public function __construct() {
		// actions with
		// key is action ajax: wp_ajax_{action}
		// value is allow ajax nopriv: wp_ajax_nopriv_{action}
		$actions = array(
			'event_auth_register' => false,
			'event_login_action'  => true,
			'load_form_register'  => true
		);

		foreach ( $actions as $action => $nopriv ) {
			add_action( 'wp_ajax_' . $action, array( $this, $action ) );
			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_' . $action, array( $this, $action ) );
			} else {
				add_action( 'wp_ajax_nopriv_' . $action, array( $this, 'must_login' ) );
			}
		}
	}

	/**
	 * load form register
	 * @return html login form if user not logged in || @return html register event form
	 */
	public function load_form_register() {
		if ( empty( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'event-auth-register-nonce' ) ) {
			return;
		}

		$event_id = !empty( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;

		if ( !$event_id ) {
			tp_event_add_notice( 'error', __( 'Event not found.', 'tp-event' ) );
			ob_start();
			echo tp_event_print_notices();
			echo ob_get_clean();
			die();
		} else if ( !is_user_logged_in() ) {
			/**
			 * return html login form if not user logged in
			 */
			tp_event_add_notice( 'error', __( 'You must login before register', 'tp-event' ) . sprintf( ' <strong>%s</strong>', get_the_title( $event_id ) ) );
			ob_start();
			echo Auth_Authentication::event_auth_login();
			echo ob_get_clean();
			die();
		} else {
			$event           = new Auth_Event( $event_id );
			$registered_time = $event->booked_quantity( get_current_user_id() );
			ob_start();
			if ( get_post_status( $event_id ) === 'tp-event-expired' ) {
				tp_event_print_notice( 'error', sprintf( '%s %s', get_the_title( $event_id ), __( 'has been expired', 'tp-event' ) ) );
			} else if ( $registered_time && tp_event_get_option( 'email_register_times' ) === 'once' ) {
				tp_event_print_notice( 'error', __( 'You have registered this event before.', 'tp-event' ) );
			} else {
				tp_event_get_template( 'form-book-event.php', array( 'event_id' => $event_id ) );
			}
			echo ob_get_clean();
			die();
		}
	}

	/**
	 * Login Ajax
	 */
	public function event_login_action() {
		Auth_Authentication::process_login();
		die();
	}

	// register event
	public function event_auth_register() {
		try {
			// sanitize, validate data
			if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
				throw new Exception( __( 'Invalid request.', 'tp-event' ) );
			}

			if ( !isset( $_POST['action'] ) || !check_ajax_referer( 'event_auth_register_nonce', 'event_auth_register_nonce' ) ) {
				throw new Exception( __( 'Invalid request.', 'tp-event' ) );
			}

			$event_id = false;
			if ( !isset( $_POST['event_id'] ) || !is_numeric( $_POST['event_id'] ) ) {
				throw new Exception( __( 'Invalid event request.', 'tp-event' ) );
			} else {
				$event_id = absint( sanitize_text_field( $_POST['event_id'] ) );
			}

			$qty = 0;
			if ( !isset( $_POST['qty'] ) || !is_numeric( $_POST['qty'] ) ) {
				throw new Exception( __( 'Quantity must integer.', 'tp-event' ) );
			} else {
				$qty = absint( sanitize_text_field( $_POST['qty'] ) );
			}
			// End sanitize, validate data
			// load booking module
			$booking = Event_Booking::instance();
			$event   = Auth_Event::instance( $event_id );

			$user       = wp_get_current_user();
			$registered = $event->booked_quantity( $user->ID );

			if ( $event->is_free() && $registered != 0 && tp_event_get_option( 'email_register_times', 'once' ) === 'once' ) {
				throw new Exception( __( 'You are registered this event.', 'tp-event' ) );
			}

			$payment_methods = tp_event_payments();

			$payment = isset( $_POST['payment_method'] ) ? sanitize_text_field( $_POST['payment_method'] ) : false;

			// create new book return $booking_id if success and WP Error if fail
			$args = apply_filters( 'tp_event_create_booking_args', array(
				'event_id'   => $event_id,
				'qty'        => $qty,
				'price'      => (float) $event->get_price() * $qty,
				'payment_id' => $payment,
				'currency'   => tp_event_get_currency()
			) );

			$payment = !empty( $payment_methods[$payment] ) ? $payment_methods[$payment] : false;
			$return  = array();

			if ( $args['price'] > 0 && $payment && !$payment->is_available() ) {
				throw new Exception( sprintf( '%s %s', get_title(), __( 'is not ready. Please contact administrator to setup payment gateways.', 'tp-event' ) ) );
			}

			if ( $args['payment_id'] == 'paypal' ) {
				$booking_id = $booking->create_booking( $args );
				// create booking result
				if ( is_wp_error( $booking_id ) ) {
					throw new Exception( $booking_id->get_error_message() );
				} else {
					if ( $args['price'] == 0 ) {
						// update booking status
						$book = Event_Booking::instance( $booking_id );
						$book->update_status( 'pending' );

						// user booking
						$user = get_userdata( $book->user_id );
						tp_event_add_notice( 'success', sprintf( __( 'Book ID <strong>%s</strong> completed! We\'ll send mail to <strong>%s</strong> when it is approve.', 'tp-event' ), tp_event_format_ID( $booking_id ), $user->user_email ) );
						wp_send_json( apply_filters( 'event_auth_register_ajax_result', array(
							'status' => true,
							'url'    => tp_event_account_url()
						) ) );
					} else if ( $payment ) {
						$return = $payment->process( $booking_id );

						if ( isset( $return['status'] ) && $return['status'] === false ) {
							wp_delete_post( $booking_id );
						}

						wp_send_json( $return );
					} else {
						wp_send_json( array(
							'status'  => false,
							'message' => __( 'Payment method is not available', 'tp-event' )
						) );
					}
				}
			} elseif ( $args['payment_id'] == 'woocommerce' ) {
				$booking->add_to_woo_item($args);

			}
		} catch ( Exception $e ) {
			if ( $e ) {
				tp_event_add_notice( 'error', $e->getMessage() );
			}
		}
		ob_start();
		tp_event_print_notices();
		$message = ob_get_clean();
		// allow hook
		wp_send_json( array(
			'status'  => false,
			'message' => $message
		) );
		die();
	}

	// ajax nopriv: user is not signin
	public function must_login() {
		wp_send_json( array( 'status' => false, 'message' => sprintf( __( 'You Must <a href="%s">Login</a>', 'tp-event' ), tp_event_login_url() ) ) );
		die();
	}

}

// initialize ajax class process
new Event_Ajax();
