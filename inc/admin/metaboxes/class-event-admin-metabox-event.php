<?php
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TP_Event_Admin_Metabox_Event {

	public static function init() {
//        add_action( 'tp_event_schedule_status', array( __CLASS__, 'schedule_status' ), 10, 2 );
	}

	public static function save( $post_id, $posted ) {
		if ( empty( $posted ) )
			return;

		remove_action( 'tp_event_process_update_tp_event_meta', array( __CLASS__, 'save' ), 10, 3 );
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
			TP_Event_Admin_Metaboxes::add_error( __( 'Please make sure event time is validate', 'wp-event-manager' ) );
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
		}

		$event_start = strtotime( $start );
		$event_end   = strtotime( $end );

		$time = current_time( 'timestamp' );

		$status = 'publish';
		if ( $event_start && $event_end ) {
			if ( $event_start > $time ) {
				$status = 'tp-event-upcoming';
			} else if ( $event_start <= $time && $time < $event_end ) {
				$status = 'tp-event-happenning';
			} else if ( $time >= $event_end ) {
				$status = 'tp-event-expired';
			}
			wp_clear_scheduled_hook( 'tp_event_schedule_status', array( $post_id, 'tp-event-happenning' ) );
			wp_clear_scheduled_hook( 'tp_event_schedule_status', array( $post_id, 'tp-event-expired' ) );
			wp_schedule_single_event( $event_start, 'tp_event_schedule_status', array( $post_id, 'tp-event-happenning' ) );
			wp_schedule_single_event( $event_end, 'tp_event_schedule_status', array( $post_id, 'tp-event-expired' ) );
		}

		if ( !in_array( get_post_status( $post_id ), array( 'tp-event-upcoming', 'tp-event-happenning', 'tp-event-expired' ) ) ) {
			wp_update_post( array( 'ID' => $post_id, 'post_status' => $status ) );
		}

		add_action( 'tp_event_process_update_tp_event_meta', array( __CLASS__, 'save' ), 10, 3 );
	}

	public static function schedule_status( $post_id, $status ) {
		wp_clear_scheduled_hook( 'tp_event_schedule_status', array( $post_id, $status ) );
		$old_status = get_post_status( $post_id );

		if ( $old_status !== $status && in_array( $status, array( 'tp-event-upcoming', 'tp-event-happenning', 'tp-event-expired' ) ) ) {
			$post = tp_event_add_property_countdown( get_post( $post_id ) );

			$current_time = current_time( 'timestamp' );
			$event_start  = strtotime( $post->event_start );
			$event_end    = strtotime( $post->event_end );
			if ( $status === 'tp-event-expired' && $current_time < $event_end ) {
				return;
			}

			if ( $status === 'tp-event-happenning' && $current_time < $event_start ) {
				return;
			}

			wp_update_post( array( 'ID' => $post_id, 'post_status' => $status ) );
		}
	}

	public static function render() {
		require_once( WP_EVENT_INC . 'admin/views/metaboxes/event-settings.php' );
	}

}

TP_Event_Admin_Metabox_Event::init();
