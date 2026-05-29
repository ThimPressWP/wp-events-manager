<?php
/**
 * The Template for displaying content single event.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/content-single-event.php
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

$event = EventPostModel::find( absint( get_the_ID() ) );
if ( ! $event ) {
	return;
}

$date_format      = get_option( 'date_format' );
$time_format      = get_option( 'time_format' );
$start_date       = $event->get_date_start() ? date_i18n( $date_format, strtotime( $event->get_date_start() ) ) : '';
$start_time       = $event->get_time_start() ? date_i18n( $time_format, strtotime( $event->get_time_start() ) ) : '';
$end_time         = $event->get_time_end() ? date_i18n( $time_format, strtotime( $event->get_time_end() ) ) : '';
$location         = $event->get_location();
$total_slot       = $event->get_quantity();
$booked_slot      = $event->booked_quantity();
$remaining_slot   = max( 0, $event->get_slot_available() );
$remaining_width  = $total_slot > 0 ? min( 100, round( ( $remaining_slot / $total_slot ) * 100 ) ) : 0;
$summary          = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_the_content( null, false ) ), 24 );
$location_iframe  = get_post_meta( get_the_ID(), 'tp_event_iframe', true );
$permalink        = get_permalink();
$encoded_url      = rawurlencode( $permalink );
$encoded_title    = rawurlencode( get_the_title() );
$previous_event   = get_previous_post();
$next_event       = get_next_post();
?>

<article id="tp_event-<?php the_ID(); ?>" <?php post_class( 'tp_single_event wpems-event-detail' ); ?>>

	<?php
	/**
	 * tp_event_before_single_event hook
	 */
	do_action( 'tp_event_before_single_event' );
	?>

	<div class="summary entry-summary wpems-event-detail__layout">
		<div class="wpems-event-detail__main">
			<header class="wpems-event-detail__header">
				<div class="entry-title wpems-event-detail__title">
					<h1><?php the_title(); ?></h1>
				</div>
				<?php if ( $summary ) : ?>
					<p class="wpems-event-detail__summary"><?php echo esc_html( $summary ); ?></p>
				<?php endif; ?>
			</header>

			<div class="entry-thumbnail wpems-event-detail__media">
				<?php if ( has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'large' ); ?>
				<?php else : ?>
					<div class="wpems-event-detail__media-placeholder"><?php echo esc_html( get_the_title() ); ?></div>
				<?php endif; ?>
				<?php do_action( 'tp_event_loop_event_countdown' ); ?>
			</div>

			<section class="wpems-event-panel wpems-event-description">
				<h2><?php esc_html_e( 'Event Description', 'wp-events-manager' ); ?></h2>
				<?php do_action( 'tp_event_single_event_content' ); ?>
			</section>

			<section class="wpems-event-panel wpems-event-location">
				<h2><?php esc_html_e( 'Location', 'wp-events-manager' ); ?></h2>
				<?php if ( $location_iframe ) : ?>
					<div class="wpems-event-location__map"><?php echo wp_kses_post( $location_iframe ); ?></div>
				<?php else : ?>
					<?php ob_start(); ?>
					<?php wpems_get_location_map(); ?>
					<?php $map_html = ob_get_clean(); ?>
					<?php if ( $map_html ) : ?>
						<div class="wpems-event-location__map"><?php echo $map_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<?php elseif ( $location ) : ?>
						<p class="wpems-event-location__fallback"><?php echo esc_html( $location ); ?></p>
					<?php else : ?>
						<p class="wpems-event-location__fallback"><?php esc_html_e( 'Location details will be announced soon.', 'wp-events-manager' ); ?></p>
					<?php endif; ?>
				<?php endif; ?>
			</section>

			<div class="wpems-event-share">
				<span><?php esc_html_e( 'Share:', 'wp-events-manager' ); ?></span>
				<a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Facebook', 'wp-events-manager' ); ?></a>
				<a href="<?php echo esc_url( 'https://twitter.com/intent/tweet?url=' . $encoded_url . '&text=' . $encoded_title ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'X', 'wp-events-manager' ); ?></a>
				<a href="<?php echo esc_url( 'https://www.linkedin.com/shareArticle?mini=true&url=' . $encoded_url . '&title=' . $encoded_title ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'LinkedIn', 'wp-events-manager' ); ?></a>
			</div>

			<?php if ( $previous_event || $next_event ) : ?>
				<nav class="wpems-event-navigation" aria-label="<?php esc_attr_e( 'Event navigation', 'wp-events-manager' ); ?>">
					<?php if ( $previous_event ) : ?>
						<a class="wpems-event-navigation__item wpems-event-navigation__item--prev" href="<?php echo esc_url( get_permalink( $previous_event ) ); ?>">
							<span><?php esc_html_e( 'Previous Event', 'wp-events-manager' ); ?></span>
							<strong><?php echo esc_html( get_the_title( $previous_event ) ); ?></strong>
						</a>
					<?php endif; ?>
					<?php if ( $next_event ) : ?>
						<a class="wpems-event-navigation__item wpems-event-navigation__item--next" href="<?php echo esc_url( get_permalink( $next_event ) ); ?>">
							<span><?php esc_html_e( 'Next Event', 'wp-events-manager' ); ?></span>
							<strong><?php echo esc_html( get_the_title( $next_event ) ); ?></strong>
						</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>

			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</div>

		<aside class="wpems-event-detail__sidebar">
			<section class="wpems-event-panel wpems-event-info-card">
				<h2><?php esc_html_e( 'Event Information', 'wp-events-manager' ); ?></h2>
				<ul class="event-info wpems-event-info-list">
					<?php if ( $start_date ) : ?>
						<li class="wpems-event-info-list__item wpems-event-info-list__item--date">
							<span class="label"><?php esc_html_e( 'Date', 'wp-events-manager' ); ?></span>
							<span class="detail"><?php echo esc_html( $start_date ); ?></span>
						</li>
					<?php endif; ?>
					<?php if ( $start_time ) : ?>
						<li class="wpems-event-info-list__item wpems-event-info-list__item--start">
							<span class="label"><?php esc_html_e( 'Start Time', 'wp-events-manager' ); ?></span>
							<span class="detail"><?php echo esc_html( $start_time ); ?></span>
						</li>
					<?php endif; ?>
					<?php if ( $end_time ) : ?>
						<li class="wpems-event-info-list__item wpems-event-info-list__item--finish">
							<span class="label"><?php esc_html_e( 'Finish Time', 'wp-events-manager' ); ?></span>
							<span class="detail"><?php echo esc_html( $end_time ); ?></span>
						</li>
					<?php endif; ?>
					<?php if ( $location ) : ?>
						<li class="wpems-event-info-list__item wpems-event-info-list__item--location">
							<span class="label"><?php esc_html_e( 'Location', 'wp-events-manager' ); ?></span>
							<span class="detail"><?php echo esc_html( $location ); ?></span>
						</li>
					<?php endif; ?>
					<li class="wpems-event-info-list__item wpems-event-info-list__item--seats">
						<span class="label"><?php esc_html_e( 'Remaining Seats', 'wp-events-manager' ); ?></span>
						<span class="detail"><?php echo esc_html( number_format_i18n( $remaining_slot ) ); ?></span>
						<span class="wpems-event-seat-progress" aria-hidden="true"><span style="width: <?php echo esc_attr( $remaining_width ); ?>%"></span></span>
					</li>
				</ul>
			</section>

			<?php do_action( 'tp_event_after_single_event' ); ?>
		</aside>
	</div><!-- .summary -->

</article><!-- #tp_event-<?php the_ID(); ?> -->
