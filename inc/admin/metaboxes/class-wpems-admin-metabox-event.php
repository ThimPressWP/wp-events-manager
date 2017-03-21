<?php
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPEMS_Admin_Metabox_Event {

	public static function save( $post_id, $posted ) {
		if ( empty( $posted ) )
			return;

		foreach ( $posted as $name => $value ) {
			if ( strpos( $name, 'tp_event_' ) !== 0 ) {
				continue;
			}
			update_post_meta( $post_id, $name, $value );
		}
		// Start
		$start = !empty( $_POST['tp_event_date_start'] ) ? sanitize_text_field( $_POST['tp_event_date_start'] ) : '';
		$start .= $start && !empty( $_POST['tp_event_time_start'] ) ? ' ' . sanitize_text_field( $_POST['tp_event_time_start'] ) : '';

		// End
		$end = !empty( $_POST['tp_event_date_end'] ) ? sanitize_text_field( $_POST['tp_event_date_end'] ) : '';
		$end .= $end && !empty( $_POST['tp_event_time_end'] ) ? ' ' . sanitize_text_field( $_POST['tp_event_time_end'] ) : '';

		if ( ( $start && !$end ) || ( strtotime( $start ) >= strtotime( $end ) ) ) {
			WPEMS_Admin_Metaboxes::add_error( __( 'Please make sure event time is validate', 'wp-events-manager' ) );
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
		}

		$event_start = strtotime( $start );
		$event_end   = strtotime( $end );

		$time = strtotime( current_time( 'Y-m-d H:i' ) );

		$offset_time = get_option( 'gmt_offset' ) * 60 * 60;

		$status = 'publish';
		if ( $event_start && $event_end ) {
			if ( $event_start > $time ) {
				$status = 'tp-event-upcoming';
			} else if ( $event_start <= $time && $time < $event_end ) {
				$status = 'tp-event-happenning';
			} else if ( $time >= $event_end ) {
				$status = 'tp-event-expired';
			}

			wp_schedule_single_event( $event_start - $offset_time, 'tp_event_schedule_status', array( $post_id, 'tp-event-happenning' ) );
			wp_schedule_single_event( $event_end - $offset_time, 'tp_event_schedule_status', array( $post_id, 'tp-event-expired' ) );
		}

		if ( !in_array( get_post_status( $post_id ), array( 'tp-event-upcoming', 'tp-event-happenning', 'tp-event-expired' ) ) ) {
			wp_update_post( array( 'ID' => $post_id, 'post_status' => $status ) );
		}

	}

	public static function render() {
		require_once( WPEMS_INC . 'admin/views/metaboxes/event-settings.php' );
	}

}
