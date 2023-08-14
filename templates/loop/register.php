<?php
/**
 * The Template for displaying register button in single event page.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/loop/register.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( wpems_get_option( 'allow_register_event' ) == 'no' ) {
	return;
}

$event            = new WPEMS_Event( get_the_ID() );
$user_reg         = $event->booked_quantity( get_current_user_id() );
$date_start       = $event->__get( 'date_start' ) ? date( 'Ymd', strtotime( $event->__get( 'date_start' ) ) ) : '';
$time_start       = $event->__get( 'time_start' ) ? date( 'Hi', strtotime( $event->__get( 'time_start' ) ) ) : '';
$date_end         = $event->__get( 'date_end' ) ? date( 'Ymd', strtotime( $event->__get( 'date_end' ) ) ) : '';
$time_end         = $event->__get( 'time_end' ) ? date( 'Hi', strtotime( $event->__get( 'time_end' ) ) ) : '';
$g_calendar_link  = 'http://www.google.com/calendar/event?action=TEMPLATE&text=' . urlencode( $event->get_title() );
$g_calendar_link .= '&dates=' . $date_start . ( $time_start ? 'T' . $time_start : '' ) . '/' . $date_end . ( $time_end ? 'T' . $time_end : '' );
$g_calendar_link .= '&details=' . urlencode( $event->post->post_content );
$g_calendar_link .= '&location=' . urlencode( $event->__get( 'location' ) );
$g_calendar_link .= '&trp=false&sprop=' . urlencode( get_permalink( $event->ID ) );
$g_calendar_link .= '&sprop=name:' . urlencode( get_option( 'blogname' ) );
$time_zone        = get_option( 'timezone_string' ) ? get_option( 'timezone_string' ) : 'UTC';
$g_calendar_link .= '&ctz=' . urlencode( $time_zone );

if ( absint( $event->qty ) == 0 || get_post_meta( get_the_ID(), 'tp_event_status', true ) === 'expired' ) {
	return;
}
?>

<div class="entry-register">

	<ul class="event-info">
		<li class="total">
			<span class="label"><?php _e( 'Total Slot:', 'wp-events-manager' ); ?></span>
			<span class="detail"><?php echo esc_html( absint( $event->qty ) ); ?></span>
		</li>
		<li class="booking_slot">
			<span class="label"><?php _e( 'Booked Slot:', 'wp-events-manager' ); ?></span>
			<span class="detail"><?php echo esc_html( absint( $event->booked_quantity() ) ); ?></span>
		</li>
		<li class="price">
			<span class="label"><?php _e( 'Cost:', 'wp-events-manager' ); ?></span>
			<span class="detail"><?php printf( '%s', $event->is_free() ? __( 'Free', 'wp-events-manager' ) : wpems_format_price( $event->get_price() ) ); ?></span>
		</li>
	</ul>

	<?php if ( is_user_logged_in() ) { ?>
		<a class="wpems_g_calendar_url" href="<?php esc_attr( $g_calendar_link ); ?>" target="_blank"><img src="https://www.google.com/calendar/images/ext/gc_button2.gif" alt="0" border="0"></a>
		<?php
		$registered_time = $event->booked_quantity( get_current_user_id() );
		if ( $registered_time && wpems_get_option( 'email_register_times' ) === 'once' && $event->is_free() ) {
			?>
			<p><?php echo __( 'You have registered this event before.', 'wp-events-manager' ); ?></p>
		<?php } else { ?>
			<a class="event_register_submit event_auth_button event-load-booking-form"
			   data-event="<?php echo esc_attr( get_the_ID() ); ?>"><?php _e( 'Register Now', 'wp-events-manager' ); ?></a>
		<?php } ?>
	<?php } else { ?>
		<p><?php echo sprintf( __( 'You must <a href="%s">login</a> before register event.', 'wp-events-manager' ), wpems_login_url() ); ?></p>
	<?php } ?>

</div>
