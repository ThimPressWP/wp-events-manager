<?php
defined( 'ABSPATH' ) || exit();

class WPEMS_Admin_Metabox_Booking {

	public static function save( $post_id, $post, $update ) {
		if ( !empty( $_POST['booking-status'] ) ) {
			remove_action( 'tp_event_process_update_event_auth_book_meta', array( __CLASS__, 'save' ), 10, 3 );
			$booking = WPEMS_Booking::instance( $post_id );

			$status = sanitize_text_field( $_POST['booking-status'] );
			$booking->update_status( $status );
			add_action( 'tp_event_process_update_event_auth_book_meta', array( __CLASS__, 'save' ), 10, 3 );
		}
		if ( !empty( $_POST['booking-notes'] ) ) {
			update_post_meta( $post_id, 'ea_booking_note', sanitize_textarea_field( $_POST['booking-notes'] ) );
		}
	}

	public static function render() {
		require_once( WPEMS_INC . 'admin/views/metaboxes/booking-details.php' );
	}

	public static function side() {
		require_once( WPEMS_INC . 'admin/views/metaboxes/booking-actions.php' );
	}

}
