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
			if ( !in_array( $name, array( 'tp_event_date_start', 'tp_event_time_start', 'tp_event_date_end', 'tp_event_time_end' ) ) ) {
				update_post_meta( $post_id, $name, $value );
			}
		}
		// Start
		$start = !empty( $_POST['tp_event_date_start'] ) ? sanitize_text_field( $_POST['tp_event_date_start'] ) : '';
		$start .= $start && !empty( $_POST['tp_event_time_start'] ) ? ' ' . sanitize_text_field( $_POST['tp_event_time_start'] ) : '';

		update_post_meta( $post_id, 'tp_event_start', $start );

		// End
		$end = !empty( $_POST['tp_event_date_end'] ) ? sanitize_text_field( $_POST['tp_event_date_end'] ) : '';
		$end .= $end && !empty( $_POST['tp_event_time_end'] ) ? ' ' . sanitize_text_field( $_POST['tp_event_time_end'] ) : '';
		update_post_meta( $post_id, 'tp_event_end', $end );
		if ( ( $start && !$end ) || ( strtotime( $start ) >= strtotime( $end ) ) ) {
			TP_Event_Admin_Metaboxes::add_error( __( 'Please make sure end time after start time', 'tp-event' ) );
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
			return;
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
		global $post;
		$post_id        = $post->ID;
		$prefix         = 'tp_event_';
		$start          = get_post_meta( $post->ID, $prefix . 'start', true );
		$date_start     = $start ? date( 'Y-m-d', strtotime( $start ) ) : '';
		$time_start     = $start ? date( 'H:i', strtotime( $start ) ) : '';
		$use_start_time = get_post_meta( $post_id, $prefix . 'use_start_time', true );

		$end          = get_post_meta( $post->ID, $prefix . 'end', true );
		$date_end     = $end ? date( 'Y-m-d', strtotime( $end ) ) : '';
		$time_end     = $end ? date( 'H:i', strtotime( $end ) ) : '';
		$use_end_time = get_post_meta( $post_id, $prefix . 'use_end_time', true );

		$qty         = get_post_meta( $post_id, $prefix . 'qty', true );
		$price       = get_post_meta( $post_id, $prefix . 'price', true );
		$is_not_free = get_post_meta( $post_id, $prefix . 'is_not_free', true );
		$data_text   = !$is_not_free ? __( 'Free', 'tp-event' ) : __( 'Set Price', 'tp-event' );
		$text        = $is_not_free ? __( 'Free', 'tp-event' ) : __( 'Set Price', 'tp-event' );
		?>
        <div class="event_meta_box_container">
            <div class="event_meta_panel">
				<?php do_action( 'event_admin_metabox_before_fields', $post, $prefix ); ?>

                <div class="option_group">
                    <p class="form-field">
                        <label for="_quantity"><?php _e( 'Quantity', 'tp-event' ) ?></label>
                        <input type="number" min="0" step="1" class="short" name="<?php echo esc_attr( $prefix ) ?>qty" id="_quantity" value="<?php echo esc_attr( absint( $qty ) ) ?>">
                        <span class="description"><a href="#" data-target="set_price" class="open-extra" data-text="<?php echo esc_attr( $data_text ) ?>"><?php echo esc_html( $text ) ?></a></span>
                    </p>
                </div>
                <div class="option_group<?php echo ( !$is_not_free ) ? ' hide-if-js' : ''; ?>">
                    <input id="set_price" type="hidden" value="<?php echo esc_attr( $is_not_free ) ?>" name="<?php echo esc_attr( $prefix ) ?>is_not_free" />
                    <p class="form-field">
                        <label for="_auth_cost"><?php printf( '%s(%s)', __( 'Price', 'tp-event' ), tp_event_get_currency_symbol() ) ?></label>
                        <input type="number" step="any" min="0" class="short" name="<?php echo esc_attr( $prefix ) ?>price" id="_quantity" value="<?php echo esc_attr( floatval( $price ) ) ?>" />
                    </p>
                </div>

                <div class="option_group">
					<?php
					$text      = $use_start_time ? __( 'Remove time', 'tp-event' ) : __( 'Use time', 'tp-event' );
					$data_text = !$use_start_time ? __( 'Remove time', 'tp-event' ) : __( 'Use time', 'tp-event' );
					?>
                    <p class="form-field">
                        <label for="_date_start"><?php _e( 'Date Start', 'tp-event' ) ?></label>
                        <input type="text" class="short" name="<?php echo esc_attr( $prefix ) ?>date_start" id="_date_start" value="<?php echo esc_attr( $date_start ) ?>">
                        <span class="description"><a href="#" data-target="use_start_time" class="open-extra" data-text="<?php echo esc_attr( $data_text ) ?>"><?php echo esc_html( $text ) ?></a></span>
                    </p>
                </div>
                <div class="option_group<?php echo !$use_start_time ? ' hide-if-js' : '' ?>">
                    <input id="use_start_time" type="hidden" name="<?php echo esc_attr( $prefix ) ?>use_start_time" value="<?php echo esc_attr( $use_start_time ) ?>" />
                    <p class="form-field">
                        <label for="_time_start"><?php _e( 'Time Start', 'tp-event' ) ?></label>
                        <input type="text" class="short" name="<?php echo esc_attr( $prefix ) ?>time_start" id="_time_start" value="<?php echo esc_attr( $time_start ) ?>">
                    </p>
                </div>
				<?php
				$text      = $use_end_time ? __( 'Remove time', 'tp-event' ) : __( 'Use time', 'tp-event' );
				$data_text = !$use_end_time ? __( 'Remove time', 'tp-event' ) : __( 'Use time', 'tp-event' );
				?>
                <div class="option_group">
                    <p class="form-field">
                        <label for="_date_end"><?php _e( 'Date End', 'tp-event' ) ?></label>
                        <input type="text" class="short" name="<?php echo esc_attr( $prefix ) ?>date_end" id="_date_end" value="<?php echo esc_attr( $date_end ) ?>">
                        <span class="description"><a href="#" data-target="use_end_time" class="open-extra" data-text="<?php echo esc_attr( $data_text ) ?>"><?php echo esc_html( $text ) ?></a></span>
                    </p>
                </div>
                <div class="option_group<?php echo !$use_end_time ? ' hide-if-js' : '' ?>">
                    <input id="use_end_time" type="hidden" name="<?php echo esc_attr( $prefix ) ?>use_end_time" value="<?php echo esc_attr( $use_end_time ) ?>" />
                    <p class="form-field">
                        <label for="_time_end"><?php _e( 'Time End', 'tp-event' ) ?></label>
                        <input type="text" class="short" name="<?php echo esc_attr( $prefix ) ?>time_end" id="_time_end" value="<?php echo esc_attr( $time_end ) ?>">
                    </p>
                </div>
                <div class="option_group">
                    <p class="form-field">
                        <label for="_shortcode"><?php _e( 'Shortcode', 'tp-event' ) ?></label>
                        <input type="text" class="short" id="_shortcode" value="<?php echo esc_attr( '[tp_event_countdown events="' . $post->ID . '"]' ); ?>" readonly>
                    </p>
                </div>
				<?php wp_nonce_field( 'event_nonce', 'event-nonce' ); ?>
				<?php do_action( 'event_admin_metabox_after_fields', $post, $prefix ); ?>
            </div>
        </div>
		<?php
	}

}

TP_Event_Admin_Metabox_Event::init();
