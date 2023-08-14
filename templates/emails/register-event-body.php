<?php
/**
 * The Template for displaying email register event's body for user.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/emails/register-event-body.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>
<h2>Hello {user_displayname}</h2>
You have been registered successful <a href="{event_link}">our event</a>. Please go to the following link for more details.<a href="{user_link}">Your account.</a>

<table class="event_auth_admin_table_booking">
	<thead>
	<tr>
		<th><?php _e( 'ID', 'wp-events-manager' ); ?></th>
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
		<td>#{booking_id}</td>
		<td><a href="{event_link}">{event_title}</a></td>
		<td>{event_type}</td>
		<td>{booking_quantity}</td>
		<td>{booking_price}</td>
		<td>{booking_payment_method}</td>
		<td>{booking_status}</td>
	</tr>
	</tbody>
</table>
