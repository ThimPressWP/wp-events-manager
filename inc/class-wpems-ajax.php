<?php
/**
 * WP Events Manager Ajax class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

/**
 * Ajax Process
 */
class WPEMS_Ajax {

	public function __construct() {
		// actions with
		// key is action ajax: wp_ajax_{action}
		// value is allow ajax nopriv: wp_ajax_nopriv_{action}
		$actions = array(
			'event_remove_notice' => true,
			'event_auth_register' => false,
			'event_login_action'  => true,
			'load_form_register'  => true,
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

		if ( is_multisite() ) {
			update_site_option( 'thimpress_events_show_remove_event_auth_notice', 1 );
		} else {
			update_option( 'thimpress_events_show_remove_event_auth_notice', 1 );
		}
		wp_send_json(
			array(
				'status'  => true,
				'message' => __( 'Remove admin notice successful', 'wp-events-manager' ),
			)
		);
	}


	/**
	 * load form register
	 * @return html login form if user not logged in || @return html register event form
	 */
	public function load_form_register() {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'event-auth-register-nonce' ) ) {
			return;
		}

		$event_id = ! empty( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;

		if ( ! $event_id ) {
			wpems_add_notice( 'error', __( 'Event not found.', 'wp-events-manager' ) );
			wpems_print_notices();
			die();
		} elseif ( ! is_user_logged_in() ) {
			wpems_print_notices( 'error', __( 'You must login before register ', 'wp-events-manager' ) . sprintf( ' <strong>%s</strong>', get_the_title( $event_id ) ) );
			die();
		} else {
			$event           = new WPEMS_Event( $event_id );
			$registered_time = $event->booked_quantity( get_current_user_id() );
			ob_start();
			if ( get_post_meta( $event_id, 'tp_event_status', true ) === 'expired' ) {
				wpems_print_notices( 'error', sprintf( '%s %s', get_the_title( $event_id ), __( 'has been expired', 'wp-events-manager' ) ) );
			} elseif ( $registered_time && wpems_get_option( 'email_register_times' ) === 'once' && $event->is_free() ) {
				wpems_print_notices( 'error', __( 'You have registered this event before', 'wp-events-manager' ) );
			} elseif ( ! $event->get_slot_available() ) {
				wpems_print_notices( 'error', __( 'The event is full, the registration is closed', 'wp-events-manager' ) );
			} else {
				wpems_get_template( 'loop/booking-form.php', array( 'event_id' => $event_id ) );
			}
			echo ob_get_clean();
			die();
		}
	}

	/**
	 * Login Ajax
	 */
	public function event_login_action() {
		WPEMS_User_Process::process_login();
		die();
	}

	// register event
	public function event_auth_register() {
		try {
			// sanitize, validate data
			if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
				throw new Exception( __( 'Invalid request', 'wp-events-manager' ) );
			}

			if ( ! isset( $_POST['action'] ) || ! check_ajax_referer( 'event_auth_register_nonce', 'event_auth_register_nonce' ) ) {
				throw new Exception( __( 'Invalid request', 'wp-events-manager' ) );
			}

			$event_id = false;
			if ( ! isset( $_POST['event_id'] ) || ! is_numeric( $_POST['event_id'] ) ) {
				throw new Exception( __( 'Invalid event request', 'wp-events-manager' ) );
			} else {
				$event_id = absint( sanitize_text_field( $_POST['event_id'] ) );
			}

			$qty = 0;
			if ( ! isset( $_POST['qty'] ) || ! is_numeric( $_POST['qty'] ) ) {
				throw new Exception( __( 'Quantity must integer', 'wp-events-manager' ) );
			} else {
				$qty = absint( sanitize_text_field( $_POST['qty'] ) );
			}

			// End sanitize, validate data
			// load booking module
			$booking = WPEMS_Booking::instance();
			$event   = WPEMS_Event::instance( $event_id );

			$user       = wp_get_current_user();
			$registered = $event->booked_quantity( $user->ID );

			if ( $event->is_free() && $registered != 0 && wpems_get_option( 'email_register_times', 'once' ) === 'once' ) {
				throw new Exception( __( 'You are registered this event.', 'wp-events-manager' ) );
			}

			if ( $event->booked_quantity() >= get_post_meta( $event_id, 'tp_event_qty', true ) ) {
				throw new Exception( __( 'There is not any slots now. Please try with next future events!', 'wp-events-manager' ) );
			}

			$payment_methods = wpems_payment_gateways();

			$payment = isset( $_POST['payment_method'] ) ? sanitize_text_field( $_POST['payment_method'] ) : false;

			// create new book return $booking_id if success and WP Error if fail
			$args = apply_filters(
				'tp_event_create_booking_args',
				array(
					'event_id'   => $event_id,
					'qty'        => $qty,
					'price'      => (float) $event->get_price() * $qty,
					'payment_id' => $payment,
					'currency'   => wpems_get_currency(),
				)
			);

			$payment = ! empty( $payment_methods[ $payment ] ) ? $payment_methods[ $payment ] : false;

			$return = array();

			if ( $args['price'] > 0 && $payment && ! $payment->is_available() ) {
				throw new Exception( sprintf( '%s %s', get_title(), __( 'is not ready. Please contact administrator to setup payment gateways', 'wp-events-manager' ) ) );
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
						$book = WPEMS_Booking::instance( $booking_id );
						$book->update_status();

						// user booking
						$user = get_userdata( $book->user_id );
						wpems_add_notice( 'success', sprintf( __( 'Book ID <strong>%1$s</strong> completed! We\'ll send mail to <strong>%2$s</strong> when it is approve.', 'wp-events-manager' ), wpems_format_ID( $booking_id ), $user->user_email ) );
						wp_send_json(
							apply_filters(
								'event_auth_register_ajax_result',
								array(
									'status' => true,
									'url'    => wpems_account_url(),
								)
							)
						);
					} elseif ( $payment ) {

						$return = $payment->process( $booking_id );
						if ( isset( $return['status'] ) && $return['status'] === false ) {
							wp_delete_post( $booking_id );
						}
						wp_send_json( $return );
					} else {
						wp_send_json(
							array(
								'status'  => false,
								'message' => __( 'Payment method is not available', 'wp-events-manager' ),
							)
						);
					}
				}
			}
		} catch ( Exception $e ) {
			if ( $e ) {
				wpems_add_notice( 'error', $e->getMessage() );
			}
		}
		wpems_print_notices();
		$message = ob_get_clean();
		// allow hook
		wp_send_json(
			array(
				'status'  => false,
				'message' => $message,
			)
		);
		die();
	}

	// ajax nopriv: user is not signin
	public function must_login() {
		wp_send_json(
			array(
				'status'  => false,
				'message' => sprintf( __( 'You Must <a href="%s">Login</a>', 'wp-events-manager' ), tp_event_login_url() ),
			)
		);
		die();
	}

}

// initialize ajax class process
new WPEMS_Ajax();
