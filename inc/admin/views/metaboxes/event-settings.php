<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

global $post;
$post_id    = $post->ID;
$prefix     = 'tp_event_';
$date_start = get_post_meta( $post->ID, $prefix . 'date_start', true ) ? date( 'Y-m-d', strtotime( get_post_meta( $post->ID, $prefix . 'date_start', true ) ) ) : '';
$time_start = get_post_meta( $post->ID, $prefix . 'time_start', true ) ? date( 'H:i', strtotime( get_post_meta( $post->ID, $prefix . 'time_start', true ) ) ) : '';

$date_end = get_post_meta( $post->ID, $prefix . 'date_end', true ) ? date( 'Y-m-d', strtotime( get_post_meta( $post->ID, $prefix . 'date_end', true ) ) ) : '';
$time_end = get_post_meta( $post->ID, $prefix . 'time_end', true ) ? date( 'H:i', strtotime( get_post_meta( $post->ID, $prefix . 'time_end', true ) ) ) : '';

$qty      = get_post_meta( $post_id, $prefix . 'qty', true );
$price    = get_post_meta( $post_id, $prefix . 'price', true );
$location = get_post_meta( $post_id, $prefix . 'location', true );
$note     = get_post_meta( $post_id, $prefix . 'note', true );
$today    = date( "Y-m-d", strtotime( 'today' ) );
$tomorrow = date( "Y-m-d", strtotime( 'tomorrow' ) );
?>
<div class="event_meta_box_container">
    <div class="event_meta_panel">
		<?php do_action( 'tp_event_admin_event_metabox_before_fields', $post, $prefix ); ?>
        <div class="option_group">
            <p class="form-field">
                <label for="_qty"><?php _e( 'Quantity', 'tp-event' ) ?></label>
                <input type="number" min="0" step="1" class="short" name="<?php echo esc_attr( $prefix ) ?>qty" id="_qty" value="<?php echo esc_attr( absint( $qty ) ) ?>">
            </p>
        </div>
        <div class="option_group">
            <p class="form-field">
                <label for="_price"><?php printf( '%s(%s)', __( 'Price', 'tp-event' ), tp_event_get_currency_symbol() ) ?></label>
                <input type="number" step="any" min="0" class="short" name="<?php echo esc_attr( $prefix ) ?>price" id="_price" value="<?php echo esc_attr( floatval( $price ) ) ?>" />
            </p>
            <p class="event-meta-notice">
				<?php echo esc_html__( 'Set 0 to make it becomes free event', 'tp-event' ); ?>
            </p>
        </div>

        <div class="option_group">
            <div class="form-field" id="event-time-metabox">
                <label><?php echo esc_html__( 'Start/End', 'tp-event' ); ?></label>
                <label hidden for="_date_start"></label>
                <input type="text" class="short date-start" name="<?php echo esc_attr( $prefix ) ?>date_start" id="_date_start"
                       value="<?php echo $date_start ? esc_attr( $date_start ) : esc_attr( $today ); ?>">
                <label hidden for="_time_start"></label>
                <input type="text" class="short time-start" name="<?php echo esc_attr( $prefix ) ?>time_start" id="_time_start"
                       value="<?php echo $time_start ? esc_attr( $time_start ) : '' ?>">
                <span class="time-connect"> <?php echo esc_html__( 'to', 'tp-event' ); ?></span>
                <label hidden for="_date_end"></label>
                <input type="text" class="short date-end" name="<?php echo esc_attr( $prefix ) ?>date_end" id="_date_end"
                       value="<?php echo $date_end ? esc_attr( $date_end ) : esc_attr( $tomorrow ); ?>">
                <label hidden for="_time_end"></label>
                <input type="text" class="short time-end" name="<?php echo esc_attr( $prefix ) ?>time_end" id="_time_end"
                       value="<?php echo $time_end ? esc_attr( $time_end ) : '' ?>">
            </div>
        </div>
        <div class="option_group">
            <p class="form-field">
                <label for="_location"><?php _e( 'Location', 'tp-event' ) ?></label>
                <input type="text" class="short" name="<?php echo esc_attr( $prefix ) ?>location" id="_location" value="<?php echo esc_attr( $location ) ?>">
            </p>
			<?php if ( !tp_event_get_option( 'google_map_api_key' ) ): ?>
                <p class="event-meta-notice">
					<?php echo esc_html__( 'You need set up Google Map API Key to show map.', 'tp-event' ); ?>
                    <a href="<?php echo esc_url( get_admin_url() . '/admin.php?page=tp-event-setting&tab=general' ); ?>"><?php echo esc_html__( 'Set up here' ) ?></a>
                </p>
			<?php endif; ?>
        </div>
        <div class="option_group">
            <p class="form-field">
                <label for="_note"><?php _e( 'Note', 'tp-event' ) ?></label>
                <textarea rows="4" class="short" name="<?php echo esc_attr( $prefix ) ?>note" id="_note"><?php echo esc_html( $note ); ?></textarea>
            </p>
        </div>
        <div class="option_group">
            <p class="form-field">
                <label for="_shortcode"><?php _e( 'Shortcode', 'tp-event' ) ?></label>
                <input type="text" class="short" id="_shortcode" value="<?php echo esc_attr( '[tp_event_countdown event_id="' . $post->ID . '"]' ); ?>" readonly>
            </p>
        </div>
		<?php wp_nonce_field( 'event_nonce', 'event-nonce' ); ?>
		<?php do_action( 'tp_event_admin_event_metabox_after_fields', $post, $prefix ); ?>
    </div>
</div>