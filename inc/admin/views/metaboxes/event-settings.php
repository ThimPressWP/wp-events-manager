<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

global $post;
$post_id        = $post->ID;
$prefix         = 'tp_event_';
$start          = get_post_meta( $post->ID, $prefix . 'start', true );
$date_start     = $start ? date( 'Y-m-d', strtotime( $start ) ) : '';
$time_start     = $start ? date( 'H:i', strtotime( $start ) ) : '';

$end          = get_post_meta( $post->ID, $prefix . 'end', true );
$date_end     = $end ? date( 'Y-m-d', strtotime( $end ) ) : '';
$time_end     = $end ? date( 'H:i', strtotime( $end ) ) : '';

$qty         = get_post_meta( $post_id, $prefix . 'qty', true );
$price       = get_post_meta( $post_id, $prefix . 'price', true );
$location    = get_post_meta( $post_id, $prefix . 'location', true );
?>
<div class="event_meta_box_container">
    <div class="event_meta_panel">
		<?php do_action( 'tp_event_admin_event_metabox_before_fields', $post, $prefix ); ?>
        <div class="option_group">
            <p class="form-field">
                <label for="_quantity"><?php _e( 'Quantity', 'tp-event' ) ?></label>
                <input type="number" min="0" step="1" class="short" name="<?php echo esc_attr( $prefix ) ?>qty" id="_quantity" value="<?php echo esc_attr( absint( $qty ) ) ?>">
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
                <label><?php echo esc_html__( 'Start/End', 'tp-evnt' ); ?></label>
                <label hidden for="_date_start"></label>
                <input type="text" class="short date-start" name="<?php echo esc_attr( $prefix ) ?>date_start" id="_date_start" value="<?php echo esc_attr( $date_start ) ?>">
                <label hidden for="_time_start"></label>
                <input type="text" class="short time-start" name="<?php echo esc_attr( $prefix ) ?>time_start" id="_time_start" value="<?php echo esc_attr( $time_start ) ?>">
                <span class="time-connect"> <?php echo esc_html__( 'to', 'tp-event' ); ?></span>
                <label hidden for="_date_end"></label>
                <input type="text" class="short date-end" name="<?php echo esc_attr( $prefix ) ?>date_end" id="_date_end" value="<?php echo esc_attr( $date_end ) ?>">
                <label hidden for="_time_end"></label>
                <input type="text" class="short time-end" name="<?php echo esc_attr( $prefix ) ?>time_end" id="_time_end" value="<?php echo esc_attr( $time_end ) ?>">
            </div>
        </div>
        <div class="option_group">
            <p class="form-field">
                <label for="_location"><?php _e( 'Location', 'tp-event' ) ?></label>
                <input type="text" class="short" name="<?php echo esc_attr( $prefix ) ?>location" id="location" value="<?php echo esc_attr( $location ) ?>">
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
                <label for="_shortcode"><?php _e( 'Shortcode', 'tp-event' ) ?></label>
                <input type="text" class="short" id="_shortcode" value="<?php echo esc_attr( '[tp_event_countdown event_id="' . $post->ID . '"]' ); ?>" readonly>
            </p>
        </div>
		<?php wp_nonce_field( 'event_nonce', 'event-nonce' ); ?>
		<?php do_action( 'tp_event_admin_event_metabox_after_fields', $post, $prefix ); ?>
    </div>
</div>