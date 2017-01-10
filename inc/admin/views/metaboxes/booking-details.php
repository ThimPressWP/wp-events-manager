<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

global $post;
$booking = TP_Event_Booking::instance( $post->ID );
$user    = get_userdata( $booking->user_id );
$prefix  = 'tp_event_';
?>

<?php do_action( 'tp_event_admin_metabox_before_fields', $post, $prefix ); ?>

    <div id="event-booking-details" class="booking-details">
    <div class="booking-data">
        <h3 class="booking-data-number"><?php echo sprintf( esc_attr__( 'Order %s', 'tp-event' ), tp_event_format_ID( $post->ID ) ); ?></h3>
        <div class="booking-date">
			<?php echo sprintf( __( 'Date %s', 'tp-event' ), $post->post_date ); ?>
        </div>
        <div class="booking-payment-method">
			<?php echo sprintf( '%s', $booking->payment_id ? tp_event_get_payment_title( $booking->payment_id ) : __( 'No payment', 'tp-event' ) ); ?>
        </div>
    </div>
    <div class="booking-user-data clearfix">
        <div class="user-avatar">
			<?php echo get_avatar( $booking->user_id, 120 ); ?>
        </div>
        <div class="order-user-meta">
            <div class="user-display-name">
				<?php echo sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=tp-event-users&user_id=' . $booking->user_id ), $user->data->user_nicename ); ?>
            </div>
            <div class="user-email">
				<?php echo $user->user_email ? $user->user_email : ''; ?>
            </div>
        </div>
    </div>
    <br />

    <h3><?php _e( 'Order Items', 'tp-event' ); ?></h3>
    <div class="order-items">
        <table>
            <thead>
            <tr>
                <th><?php _e( 'Item', 'tp-event' ); ?></th>
                <th><?php _e( 'Cost', 'tp-event' ); ?></th>
                <th><?php _e( 'Quantity', 'tp-event' ); ?></th>
                <th><?php _e( 'Payment Method', 'tp-event' ) ?></th>
                <th><?php _e( 'Amount', 'tp-event' ); ?></th>
            </tr>
            </thead>
            <tbody>
            <tr data-item_id="<?php echo esc_attr( $booking->event_id ); ?>">
                <td><?php echo sprintf( '<a href="%s">%s</a>', get_edit_post_link( $booking->event_id ), get_the_title( $booking->event_id ) ) ?></td>
                <td><?php echo sprintf( '%s', tp_event_format_price( floatval( $booking->price ), $booking->currency ) ) ?></td>
                <td><?php echo sprintf( '%s', $booking->qty ) ?></td>
                <td><?php echo sprintf( '%s', $booking->payment_id ? tp_event_get_payment_title( $booking->payment_id ) : __( 'No payment.', 'tp-event' ) ) ?></td>
                <td><?php echo sprintf( '%s', tp_event_format_price( floatval( $booking->price ), $booking->currency ) ) ?></td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <td width="300" colspan="3"><?php _e( 'Sub Total', 'tp-event' ); ?></td>
                <td width="100" class="align-right">
                    <span class="order-subtotal"><?php ?></span>
                </td>
            </tr>
            <tr>
                <td class="align-right" colspan="3"><?php _e( 'Total', 'tp-event' ); ?></td>
                <td class="align-right total">
                    <span class="order-total"><?php echo sprintf( '%s', tp_event_format_price( floatval( $booking->price ), $booking->currency ) ) ?></span>
                </td>
            </tr>
            </tfoot>
        </table>
    </div>

<?php do_action( 'tp_event_admin_metabox_after_fields', $post, $prefix ); ?>

<?php