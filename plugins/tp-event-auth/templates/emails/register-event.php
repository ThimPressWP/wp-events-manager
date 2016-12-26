<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( !$booking || !$user ) {
    return;
}
?>

<h2><?php printf( __( 'Hello %s!', 'tp-event-auth' ), $user->data->display_name ); ?></h2>
<?php
printf(
        __( 'You have been registered successful our <a href="%s">event</a>. Please go to the following link for more details.<br /><a href="%s">Your account.</a>', 'tp-event-auth' ), get_permalink( $booking->event_id ), event_auth_account_url()
);
?>

<table class="event_auth_admin_table_booking">
    <thead>
        <tr>
            <th style="border: 1px solid #eee"><?php _e( 'ID', 'tp-event-auth' ) ?></th>
            <th style="border: 1px solid #eee"><?php _e( 'Event', 'tp-event-auth' ) ?></th>
            <th style="border: 1px solid #eee"><?php _e( 'Type', 'tp-event-auth' ) ?></th>
            <th style="border: 1px solid #eee"><?php _e( 'Slot', 'tp-event-auth' ) ?></th>
            <th style="border: 1px solid #eee"><?php _e( 'Cost', 'tp-event-auth' ) ?></th>
            <th style="border: 1px solid #eee"><?php _e( 'Payment Method', 'tp-event-auth' ) ?></th>
            <th style="border: 1px solid #eee"><?php _e( 'Status', 'tp-event-auth' ) ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="border: 1px solid #eee"><?php printf( '%s', event_auth_format_ID( $booking->ID ) ) ?></td>
            <td style="border: 1px solid #eee"><?php printf( '<a href="%s">%s</a>', get_permalink( $booking->event_id ), get_the_title( $booking->event_id ) ) ?></td>
            <td style="border: 1px solid #eee"><?php printf( '%s', floatval( $booking->price ) == 0 ? __( 'Free', 'tp-event-auth' ) : __( 'Cost', 'tp-event-auth' )  ) ?></td>
            <td style="border: 1px solid #eee"><?php printf( '%s', $booking->qty ) ?></td>
            <td style="border: 1px solid #eee"><?php printf( '%s', event_auth_format_price( floatval( $booking->price ), $booking->currency ) ) ?></td>
            <td style="border: 1px solid #eee"><?php printf( '%s', $booking->payment_id ? event_auth_get_payment_title( $booking->payment_id ) : __( 'No payment.', 'tp-event-auth' )  ) ?></td>
            <td style="border: 1px solid #eee">
                <?php
                $return = array();
                $return[] = sprintf( '%s', event_auth_booking_status( $booking->ID ) );
                $return[] = $booking->payment_id ? sprintf( '<br />(%s)', event_auth_get_payment_title( $booking->payment_id ) ) : '';
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