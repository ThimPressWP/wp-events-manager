<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
global $post;
$booking = Event_Booking::instance( $post->ID );
$user = get_userdata( $booking->user_id );
?>
<table class="event_auth_admin_table_booking">
    <thead>
        <tr>
            <th><?php _e( 'ID', 'tp-event' ) ?></th>
            <th><?php _e( 'User', 'tp-event' ) ?></th>
            <th><?php _e( 'Event', 'tp-event' ) ?></th>
            <th><?php _e( 'Cost', 'tp-event' ) ?></th>
            <th><?php _e( 'Type', 'tp-event' ) ?></th>
            <th><?php _e( 'Quantity', 'tp-event' ) ?></th>
            <th><?php _e( 'Payment Method', 'tp-event' ) ?></th>
            <th><?php _e( 'Status', 'tp-event' ) ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?php printf( '%s', tp_event_format_ID( $post->ID ) ) ?></td>
            <td><?php printf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=tp-event-users&user_id=' . $booking->user_id ), $user->data->user_nicename ) ?></td>
            <td><?php printf( '<a href="%s">%s</a>', get_edit_post_link( $booking->event_id ), get_the_title( $booking->event_id ) ) ?></td>
            <td><?php printf( '%s', tp_event_format_price( floatval( $booking->price ), $booking->currency ) ) ?></td>
            <td><?php printf( '%s', floatval( $booking->price ) == 0 ? __( 'Free', 'tp-event' ) : __( 'Cost', 'tp-event' )  ) ?></td>
            <td><?php printf( '%s', $booking->qty ) ?></td>
            <td><?php printf( '%s', $booking->payment_id ? tp_event_get_payment_title( $booking->payment_id ) : __( 'No payment.', 'tp-event' )  ) ?></td>
            <td>
                <select name="_auth_status">
                    <?php foreach ( tp_event_get_payment_status() as $key => $text ) : ?>
                        <option value="<?php echo esc_attr( $key ) ?>"<?php echo get_post_status( $post->ID ) === $key ? ' selected' : '' ?>><?php printf( '%s', $text ) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </tbody>
</table>
