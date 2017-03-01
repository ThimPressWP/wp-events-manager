<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajax Process
 */
class TP_Event_Ajax {

	public function __construct() {
		// actions with
		// key is action ajax: wp_ajax_{action}
		// value is allow ajax nopriv: wp_ajax_nopriv_{action}
		$actions = array(
			'event_remove_notice' => true,
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
	 * Remove admin notice
	 */
	public function event_remove_notice() {

		update_option( 'thimpress_events_show_remove_event_auth_notice', 1 );
		wp_send_json( array(
			'status'  => true,
			'message' => __( 'Remove admin notice successful', 'wp-event-manager' )
		) );
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
			tp_event_add_notice( 'error', __( 'Event not found.', 'wp-event-manager' ) );
			ob_start();
			echo tp_event_print_notices();
			echo ob_get_clean();
			die();
		} else if ( !is_user_logged_in() ) {
			tp_event_print_notice( 'error', __( 'You must login before register ', 'wp-event-manager' ) . sprintf( ' <strong>%s</strong>', get_the_title( $event_id ) ) );
			die();
		} else {
			$event           = new TP_Event_Event( $event_id );
			$registered_time = $event->booked_quantity( get_current_user_id() );
			ob_start();
			if ( get_post_status( $event_id ) === 'tp-event-expired' ) {
				tp_event_print_notice( 'error', sprintf( '%s %s', get_the_title( $event_id ), __( 'has been expired', 'wp-event-manager' ) ) );
			} else if ( $registered_time && tp_event_get_option( 'email_register_times' ) === 'once' && $event->is_free() ) {
				tp_event_print_notice( 'error', __( 'You have registered this event before', 'wp-event-manager' ) );
			} else if ( !$event->get_slot_available() ) {
				tp_event_print_notice( 'error', __( 'The event is full, the registration is closed', 'wp-event-manager' ) );
			} else {
				tp_event_get_template( 'loop/booking-form.php', array( 'event_id' => $event_id ) );
			}
			echo ob_get_clean();
			die();
		}
	}

	/**
	 * Login Ajax
	 */
	public function event_login_action() {
		TP_Event_User_Process::process_login();
		die();
	}

	// register event
	public function event_auth_register() {
		try {
			// sanitize, validate data
			if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
				throw new Exception( __( 'Invalid request', 'wp-event-manager' ) );
			}

			if ( !isset( $_POST['action'] ) || !check_ajax_referer( 'event_auth_register_nonce', 'event_auth_register_nonce' ) ) {
				throw new Exception( __( 'Invalid request', 'wp-event-manager' ) );
			}

			$event_id = false;
			if ( !isset( $_POST['event_id'] ) || !is_numeric( $_POST['event_id'] ) ) {
				throw new Exception( __( 'Invalid event request', 'wp-event-manager' ) );
			} else {
				$event_id = absint( sanitize_text_field( $_POST['event_id'] ) );
			}

			$qty = 0;
			if ( !isset( $_POST['qty'] ) || !is_numeric( $_POST['qty'] ) ) {
				throw new Exception( __( 'Quantity must integer', 'wp-event-manager' ) );
			} else {
				$qty = absint( sanitize_text_field( $_POST['qty'] ) );
			}
			// End sanitize, validate data
			// load booking module
			$booking = TP_Event_Booking::instance();
			$event   = TP_Event_Event::instance( $event_id );

			$user       = wp_get_current_user();
			$registered = $event->booked_quantity( $user->ID );

			if ( $event->is_free() && $registered != 0 && tp_event_get_option( 'email_register_times', 'once' ) === 'once' ) {
				throw new Exception( __( 'You are registered this event.', 'wp-event-manager' ) );
			}

			$payment_methods = tp_event_payment_gateways();

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

			$return = array();

			if ( $args['price'] > 0 && $payment && !$payment->is_available() ) {
				throw new Exception( sprintf( '%s %s', get_title(), __( 'is not ready. Please contact administrator to setup payment gateways', 'wp-event-manager' ) ) );
			}
			if ( $payment->id == 'woo_payment' ) {

				do_action( 'tp_event_register_event_action', $args );
				$return = $payment->process( $event_id );
				wp_send_json( $return );

			} else {

				$booking_id = $booking->create_booking( $args, $args['payment_id'] );
				// create booking result
				if ( is_wp_error( $booking_id ) ) {
					throw new Exception( $booking_id->get_error_message() );
				} else {
					if ( $args['price'] == 0 ) {
						// update booking status
						$book = TP_Event_Booking::instance( $booking_id );
						$book->update_status( 'pending' );

						// user booking
						$user = get_userdata( $book->user_id );
						tp_event_add_notice( 'success', sprintf( __( 'Book ID <strong>%s</strong> completed! We\'ll send mail to <strong>%s</strong> when it is approve.', 'wp-event-manager' ), tp_event_format_ID( $booking_id ), $user->user_email ) );
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
							'message' => __( 'Payment method is not available', 'wp-event-manager' )
						) );
					}
				}
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
		wp_send_json( array( 'status' => false, 'message' => sprintf( __( 'You Must <a href="%s">Login</a>', 'wp-event-manager' ), tp_event_login_url() ) ) );
		die();
	}

}

// initialize ajax class process
new TP_Event_Ajax();
