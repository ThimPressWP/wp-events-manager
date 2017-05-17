<?php
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( wpems_get_option( 'allow_register_event' ) == 'no' ) {
	return;
}

$event    = new WPEMS_Event( get_the_ID() );
$user_reg = $event->booked_quantity( get_current_user_id() );

if ( absint( $event->qty ) == 0 || $event->post->post_status === 'tp-event-expired' ) {
	return;
}
?>

<div class="entry-register">

    <ul class="event-info">
        <li class="total">
            <span class="label"><?php _e( 'Total Slot:', 'wp-events-manager' ) ?></span>
            <span class="detail"><?php echo esc_html( absint( $event->qty ) ) ?></span>
        </li>
        <li class="booking_slot">
            <span class="label"><?php _e( 'Booked Slot:', 'wp-events-manager' ) ?></span>
            <span class="detail"><?php echo esc_html( absint( $event->booked_quantity() ) ) ?></span>
        </li>
        <li class="price">
            <span class="label"><?php _e( 'Cost:', 'wp-events-manager' ) ?></span>
            <span class="detail"><?php printf( '%s', $event->is_free() ? __( 'Free', 'wp-events-manager' ) : wpems_format_price( $event->get_price() ) ) ?></span>
        </li>
    </ul>

    <a class="event_register_submit event_auth_button event-load-booking-form" data-event="<?php echo esc_attr( get_the_ID() ) ?>"><?php _e( 'Register Now', 'wp-events-manager' ); ?></a>

</div>
