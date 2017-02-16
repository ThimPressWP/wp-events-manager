<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( !$booking || !$user ) {
    return;
}
?>

<h2><?php printf( __( 'Hello %s!', 'wp-event-manager' ), $user->data->display_name ); ?></h2>
<?php
printf(
        __( 'You have been registered successful our <a href="%s">event</a>. Please go to the following link for more details.<a href="%s">Your account.</a>', 'wp-event-manager' ), get_permalink( $booking->event_id ), tp_event_account_url()
);
?>

<table class="event_auth_admin_table_booking">
    <thead>
        <tr>
            <th style="border: 1px solid #eee"><?php _e( 'ID', 'wp-event-manager' ) ?></th>
            <th style="border: 1px solid #eee"><?php _e( 'Event', 'wp-event-manager' ) ?></th>
            <th style="border: 1px solid #eee"><?php _e( 'Type', 'wp-event-manager' ) ?></th>
            <th style="border: 1px solid #eee"><?php _e( 'Slot', 'wp-event-manager' ) ?></th>
            <th style="border: 1px solid #eee"><?php _e( 'Cost', 'wp-event-manager' ) ?></th>
            <th style="border: 1px solid #eee"><?php _e( 'Payment Method', 'wp-event-manager' ) ?></th>
            <th style="border: 1px solid #eee"><?php _e( 'Status', 'wp-event-manager' ) ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="border: 1px solid #eee"><?php printf( '%s', tp_event_format_ID( $booking->ID ) ) ?></td>
            <td style="border: 1px solid #eee"><?php printf( '<a href="%s">%s</a>', get_permalink( $booking->event_id ), get_the_title( $booking->event_id ) ) ?></td>
            <td style="border: 1px solid #eee"><?php printf( '%s', floatval( $booking->price ) == 0 ? __( 'Free', 'wp-event-manager' ) : __( 'Cost', 'wp-event-manager' )  ) ?></td>
            <td style="border: 1px solid #eee"><?php printf( '%s', $booking->qty ) ?></td>
            <td style="border: 1px solid #eee"><?php printf( '%s', tp_event_format_price( floatval( $booking->price ), $booking->currency ) ) ?></td>
            <td style="border: 1px solid #eee"><?php printf( '%s', $booking->payment_id ? tp_event_get_payment_title( $booking->payment_id ) : __( 'No payment', 'wp-event-manager' )  ) ?></td>
            <td style="border: 1px solid #eee">
                <?php
                $return = array();
                $return[] = sprintf( '%s', tp_event_booking_status( $booking->ID ) );
                $return[] = $booking->payment_id ? sprintf( '(%s)', tp_event_get_payment_title( $booking->payment_id ) ) : '';
                $return = implode( '', $return );
                printf( '%s', $return );
                ?>
            </td>
        </tr>
    </tbody>
</table>
<style type="text/css">
    table td,
    table th{
        padding: 10px;
        font-size: 13px;
        border: 1px solid;
    }
</style>