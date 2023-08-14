<?php
/**
 * The Template for displaying email register event for admin.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/emails/register-admin-event.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! $booking || ! $user ) {
	return;
} ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width">
	<style type="text/css">
		table td,
		table th {
			font-size: 13px;
			padding: 5px 30px;
			border: 1px solid #eee;
		}
	</style>
</head>
<body>

<?php
printf(
	__( 'User have been registered successful <a href="%s">your event</a>.', 'wp-events-manager' ),
	get_permalink( $booking->event_id )
);
?>

<table class="event_auth_admin_table_booking">
	<thead>
	<tr>
		<th><?php _e( 'ID', 'wp-events-manager' ); ?></th>
		<th><?php _e( 'Name', 'wp-events-manager' ); ?></th>
		<th><?php _e( 'Event', 'wp-events-manager' ); ?></th>
		<th><?php _e( 'Type', 'wp-events-manager' ); ?></th>
		<th><?php _e( 'Quantity', 'wp-events-manager' ); ?></th>
		<th><?php _e( 'Cost', 'wp-events-manager' ); ?></th>
		<th><?php _e( 'Payment Method', 'wp-events-manager' ); ?></th>
		<th><?php _e( 'Status', 'wp-events-manager' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td><?php printf( '%s', wpems_format_ID( $booking->ID ) ); ?></td>
		<td><?php printf( '%s', $user->data->display_name ); ?></td>
		<td><?php printf( '<a href="%s">%s</a>', get_permalink( $booking->event_id ), get_the_title( $booking->event_id ) ); ?></td>
		<td><?php printf( '%s', floatval( $booking->price ) == 0 ? __( 'Free', 'wp-events-manager' ) : __( 'Cost', 'wp-events-manager' ) ); ?></td>
		<td><?php printf( '%s', $booking->qty ); ?></td>
		<td><?php printf( '%s', wpems_format_price( floatval( $booking->price ), $booking->currency ) ); ?></td>
		<td><?php printf( '%s', $booking->payment_id ? wpems_get_payment_title( $booking->payment_id ) : __( 'No payment', 'wp-events-manager' ) ); ?></td>
		<td>
			<?php
			$return   = array();
			$return[] = sprintf( '%s', wpems_booking_status( $booking->ID ) );
			$return[] = $booking->payment_id ? sprintf( '(%s)', wpems_get_payment_title( $booking->payment_id ) ) : '';
			$return   = implode( '', $return );
			printf( '%s', $return );
			?>
		</td>
	</tr>
	</tbody>
</table>
</body>
</html>
