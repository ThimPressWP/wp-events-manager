<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;
?>

<div id="event-booking-actions" class="booking-actions">
    <label for="booking-status"><?php echo esc_html__( 'Booking Status', 'tp-event' ); ?></label>
    <select name="booking-status" id="booking-status">
		<?php foreach ( tp_event_get_payment_status() as $key => $text ) : ?>
            <option value="<?php echo esc_attr( $key ) ?>"<?php echo get_post_status( $post->ID ) === $key ? ' selected' : '' ?>><?php printf( '%s', $text ) ?></option>
		<?php endforeach; ?>
    </select>
    <p class="booking-status-description"><?php echo esc_html__( 'Update booking event status', 'tp-event' ); ?></p>
</div>