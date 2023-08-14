<?php
/**
 * WP Events Manager Admin Metaboxes class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

class WPEMS_Admin_Metaboxes {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 0 );
		add_action( 'save_post', array( __CLASS__, 'save_post_meta' ) );
		add_action( 'admin_notices', array( __CLASS__, 'print_errors' ) );

		/**
		 * Save post meta
		 */
		add_action( 'tp_event_process_update_tp_event_meta', array( 'WPEMS_Admin_Metabox_Event', 'save' ), 10, 2 );
		add_action( 'tp_event_process_update_event_auth_book_meta', array( 'WPEMS_Admin_Metabox_Booking', 'save' ), 10, 2 );
	}

	/**
	 * Add meta boxes
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'event-settings-metabox',
			__( 'Event Settings', 'wp-events-manager' ),
			array( 'WPEMS_Admin_Metabox_Event', 'render' ),
			'tp_event',
			'normal',
			'high'
		);
		add_meta_box(
			'booking-information-metabox',
			__( 'Booking Information', 'wp-events-manager' ),
			array( 'WPEMS_Admin_Metabox_Booking', 'render' ),
			'event_auth_book',
			'normal',
			'default'
		);
		add_meta_box(
			'booking-status-side',
			__( 'Booking Actions', 'wp-events-manager' ),
			array( 'WPEMS_Admin_Metabox_Booking', 'side' ),
			'event_auth_book',
			'side',
			'high'
		);
	}

	/**
	 * Save post meta
	 *
	 * @param type $post_id
	 *
	 * @return boolean
	 */
	public static function save_post_meta( $post_id ) {
		if ( empty( $_POST ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, array( 'tp_event', 'event_auth_book' ) ) ) {
			return false;
		}

		if ( $post_type == 'tp_event' && ( empty( $_POST['event-nonce'] ) || ! wp_verify_nonce( $_POST['event-nonce'], 'event_nonce' ) ) ) {
			return false;
		} elseif ( $post_type == 'event_auth_book' && ( empty( $_POST['event-booking-nonce'] ) || ! wp_verify_nonce( $_POST['event-booking-nonce'], 'event_booking_nonce' ) ) ) {
			return false;
		}

		do_action( 'tp_event_process_update_' . $post_type . '_meta', $post_id, $_POST );
	}

	/**
	 * Add error message save post meta
	 *
	 * @param type $message
	 */
	public static function add_error( $message = '' ) {
		$error   = get_option( 'tp_event_meta_box_error_messages', array() );
		$error[] = $message;
		update_option( 'tp_event_meta_box_error_messages', $error );
	}

	/**
	 * Print notices error save post meta
	 * @return type
	 */
	public static function print_errors() {
		$errors = get_option( 'tp_event_meta_box_error_messages' );
		if ( ! $errors ) {
			return;
		}
		echo '<div id="event_error" class="error notice is-dismissible">';

		foreach ( $errors as $error ) {
			echo '<p>' . wp_kses_post( $error ) . '</p>';
		}

		echo '</div>';
		delete_option( 'tp_event_meta_box_error_messages' );
	}

}

WPEMS_Admin_Metaboxes::init();
