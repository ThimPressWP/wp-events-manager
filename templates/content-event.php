<?php
/**
 * The Template for displaying content events.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/content-event.php
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
?>

<?php
	/**
	 * tp_event_before_loop_event hook
	 */
	do_action( 'tp_event_before_loop_event' );

if ( post_password_required() ) {
	echo get_the_password_form();
	return;
}

$event       = EventPostModel::find( absint( get_the_ID() ) );
$date_format = get_option( 'date_format' );
$time_format = get_option( 'time_format' );
$start_date  = $event && $event->get_date_start() ? date_i18n( $date_format, strtotime( $event->get_date_start() ) ) : '';
$start_time  = $event && $event->get_time_start() ? date_i18n( $time_format, strtotime( $event->get_time_start() ) ) : '';
$end_time    = $event && $event->get_time_end() ? date_i18n( $time_format, strtotime( $event->get_time_end() ) ) : '';
$location    = $event ? $event->get_location() : wpems_event_location();
$attendees   = $event ? $event->booked_quantity() : 0;
$price       = $event && ! $event->is_free() ? wpems_format_price( $event->get_price() ) : __( 'Free', 'wp-events-manager' );
$terms       = get_the_terms( get_the_ID(), 'tp_event_category' );
$category    = ! is_wp_error( $terms ) && ! empty( $terms ) ? reset( $terms ) : null;
$excerpt     = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_the_content( null, false ) ), 18 );
?>

<li id="event-<?php the_ID(); ?>" <?php post_class( 'wpems-event-card' ); ?>>

	<?php
		/**
		 * tp_event_before_loop_event_summary hook
		 *
		 * @hooked tp_event_show_event_sale_flash - 10
		 * @hooked tp_event_show_event_images - 20
		 */
		do_action( 'tp_event_before_loop_event_item' );
	?>

	<div class="summary entry-summary wpems-event-card__inner">
		<a class="entry-thumbnail wpems-event-card__thumbnail" href="<?php echo esc_url( get_permalink() ); ?>">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'large' ); ?>
			<?php else : ?>
				<span class="wpems-event-card__thumbnail-placeholder"><?php echo esc_html( get_the_title() ); ?></span>
			<?php endif; ?>
		</a>

		<div class="wpems-event-card__content">
			<?php if ( $category ) : ?>
				<a class="wpems-event-card__category" href="<?php echo esc_url( get_term_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
			<?php endif; ?>

			<div class="entry-title wpems-event-card__title">
				<h2><a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a></h2>
			</div>

			<?php if ( $excerpt ) : ?>
				<div class="entry-content wpems-event-card__excerpt">
					<?php echo esc_html( $excerpt ); ?>
				</div>
			<?php endif; ?>

			<ul class="wpems-event-card__meta">
				<?php if ( $location ) : ?>
					<li class="wpems-event-card__meta-item wpems-event-card__meta-item--location"><?php echo esc_html( $location ); ?></li>
				<?php endif; ?>
				<?php if ( $start_date ) : ?>
					<li class="wpems-event-card__meta-item wpems-event-card__meta-item--date"><?php echo esc_html( $start_date ); ?></li>
				<?php endif; ?>
				<?php if ( $start_time || $end_time ) : ?>
					<li class="wpems-event-card__meta-item wpems-event-card__meta-item--time">
						<?php echo esc_html( trim( $start_time . ( $start_time && $end_time ? ' - ' : '' ) . $end_time ) ); ?>
					</li>
				<?php endif; ?>
				<li class="wpems-event-card__meta-item wpems-event-card__meta-item--attending">
					<?php
					printf(
						esc_html(
							_n(
								'%s attending',
								'%s attending',
								(int) $attendees,
								'wp-events-manager'
							)
						),
						esc_html( number_format_i18n( (int) $attendees ) )
					);
					?>
				</li>
			</ul>

			<div class="wpems-event-card__footer">
				<div class="wpems-event-card__price">
					<?php echo wp_kses_post( $price ); ?>
					<?php if ( $event && ! $event->is_free() ) : ?>
						<span><?php esc_html_e( 'per ticket', 'wp-events-manager' ); ?></span>
					<?php endif; ?>
				</div>
				<a class="wpems-event-button wpems-event-card__button" href="<?php echo esc_url( get_permalink() ); ?>"><?php esc_html_e( 'Buy Ticket', 'wp-events-manager' ); ?></a>
			</div>
		</div>

	</div><!-- .summary -->

	<?php
		/**
		 * tp_event_after_loop_event_item hook
		 *
		 * @hooked tp_event_show_event_sale_flash - 10
		 * @hooked tp_event_show_event_images - 20
		 */
		do_action( 'tp_event_after_loop_event_item' );
	?>

</li>

<?php do_action( 'tp_event_after_loop_event' ); ?>
