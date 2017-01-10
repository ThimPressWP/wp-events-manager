<?php
defined( 'ABSPATH' ) || exit();

class TP_Event_Admin_Metabox_Booking {

	public static function save( $post_id, $post, $update ) {
		if ( !empty( $_POST['_auth_status'] ) ) {
			remove_action( 'tp_event_process_update_event_auth_book_meta', array( __CLASS__, 'save' ), 10, 3 );
			$booking = TP_Event_Booking::instance( $post_id );

			$status = sanitize_text_field( $_POST['_auth_status'] );
			$booking->update_status( $status );
			add_action( 'tp_event_process_update_event_auth_book_meta', array( __CLASS__, 'save' ), 10, 3 );
		}
	}

	public static function render() {
		require_once( TP_EVENT_INC . 'admin/views/metaboxes/booking-details.php' );
	}

}
