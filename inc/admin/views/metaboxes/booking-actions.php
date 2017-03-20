<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;
?>

<?php global $post; ?>

<?php $notes = get_post_meta( $post->ID, 'ea_booking_note', true ); ?>

<div id="event-booking-actions" class="booking-actions">
    <label for="booking-status"><?php echo esc_html__( 'Booking Status', 'wp-events-manager' ); ?></label>
    <select name="booking-status" id="booking-status">
		<?php foreach ( wpems_get_payment_status() as $key => $text ) : ?>
            <option value="<?php echo esc_attr( $key ) ?>"<?php echo get_post_status( $post->ID ) === $key ? ' selected' : '' ?>><?php printf( '%s', $text ) ?></option>
		<?php endforeach; ?>
    </select>
    <p class="booking-status-description"><?php echo esc_html__( 'Update booking event status', 'wp-events-manager' ); ?></p>
</div>
<div id="event-booking-notes" class="booking-notes">
    <label for="booking-notes"><?php echo esc_html__( 'Booking Notes', 'wp-events-manager' ); ?></label>
    <textarea id="booking-notes" name="booking-notes" rows="3"><?php echo esc_html( $notes ); ?></textarea>
</div>