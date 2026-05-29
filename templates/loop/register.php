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
use WPEMS\Models\EventPostModel;
/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( wpems_get_option( 'allow_register_event' ) == 'no' ) {
	return;
}

$event = EventPostModel::find( absint( get_the_ID() ) );
if ( ! $event ) {
	return;
}

$user_reg         = $event->booked_quantity( get_current_user_id() );
$date_start       = $event->get_date_start() ? date( 'Ymd', strtotime( $event->get_date_start() ) ) : '';
$time_start       = $event->get_time_start() ? date( 'Hi', strtotime( $event->get_time_start() ) ) : '';
$date_end         = $event->get_date_end() ? date( 'Ymd', strtotime( $event->get_date_end() ) ) : '';
$time_end         = $event->get_time_end() ? date( 'Hi', strtotime( $event->get_time_end() ) ) : '';
$g_calendar_link  = 'http://www.google.com/calendar/event?action=TEMPLATE&text=' . urlencode( $event->get_title() );
$g_calendar_link .= '&dates=' . $date_start . ( $time_start ? 'T' . $time_start : '' ) . '/' . $date_end . ( $time_end ? 'T' . $time_end : '' );
$g_calendar_link .= '&details=' . urlencode( $event->post_content );
$g_calendar_link .= '&location=' . urlencode( $event->get_location() );
$g_calendar_link .= '&trp=false&sprop=' . urlencode( get_permalink( $event->get_id() ) );
$g_calendar_link .= '&sprop=name:' . urlencode( get_option( 'blogname' ) );
$time_zone        = get_option( 'timezone_string' ) ? get_option( 'timezone_string' ) : 'UTC';
$g_calendar_link .= '&ctz=' . urlencode( $time_zone );

if ( $event->get_quantity() == 0 || $event->get_status() === 'expired' ) {
	return;
}

$payments       = $event->is_free() ? array() : (array) wpems_gateways_enable();
$price          = $event->is_free() ? __( 'Free', 'wp-events-manager' ) : wpems_format_price( $event->get_price() );
$login_url      = add_query_arg( 'redirect_to', get_permalink( $event->get_id() ), wpems_login_url() );
$can_book_event = $event->is_free() || ! empty( $payments );
?>

<div class="entry-register wpems-event-panel wpems-event-register-card">
	<h2><?php esc_html_e( 'Buy Ticket', 'wp-events-manager' ); ?></h2>

	<ul class="event-info wpems-event-register-card__info">
		<li class="price">
			<span class="label"><?php esc_html_e( 'Cost', 'wp-events-manager' ); ?></span>
			<span class="detail"><?php echo wp_kses_post( $price ); ?></span>
		</li>
		<li class="quantity">
			<span class="label"><?php esc_html_e( 'Quantity', 'wp-events-manager' ); ?></span>
			<span class="detail">1</span>
		</li>
		<?php if ( $payments ) : ?>
			<li class="payment-methods">
				<span class="label"><?php esc_html_e( 'Pay with', 'wp-events-manager' ); ?></span>
				<span class="detail">
					<?php
					$payment_titles = array();
					foreach ( $payments as $payment ) {
						$payment_titles[] = $payment->get_title();
					}
					echo esc_html( implode( ', ', $payment_titles ) );
					?>
				</span>
			</li>
		<?php endif; ?>
	</ul>

	<?php if ( is_user_logged_in() ) : ?>
		<?php
		$registered_time = $event->booked_quantity( get_current_user_id() );
		if ( $registered_time && wpems_get_option( 'email_register_times' ) === 'once' && $event->is_free() ) :
			?>
			<p class="wpems-event-register-card__notice"><?php esc_html_e( 'You have registered this event before.', 'wp-events-manager' ); ?></p>
		<?php elseif ( $can_book_event ) : ?>
			<a class="event_register_submit event_auth_button event-load-booking-form wpems-event-button" href="#" data-event="<?php echo esc_attr( get_the_ID() ); ?>"><?php esc_html_e( 'Buy Ticket', 'wp-events-manager' ); ?></a>
			<a class="wpems_g_calendar_url wpems-event-register-card__calendar" href="<?php echo esc_url( $g_calendar_link ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Add to Google Calendar', 'wp-events-manager' ); ?></a>
		<?php else : ?>
			<p class="wpems-event-register-card__notice"><?php esc_html_e( 'There are no payment gateways available. Please contact the administrator.', 'wp-events-manager' ); ?></p>
		<?php endif; ?>
	<?php else : ?>
		<a class="event_auth_button wpems-event-button" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Login to Buy Ticket', 'wp-events-manager' ); ?></a>
		<p class="wpems-event-register-card__notice"><?php esc_html_e( 'You must login to our site to book this event!', 'wp-events-manager' ); ?></p>
	<?php endif; ?>

</div>
