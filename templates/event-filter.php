<?php
/**
 * Template for displaying event archive filters.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/event-filter.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

$selected_dates      = isset( $_GET['event_date'] ) ? array_filter( array_map( 'sanitize_key', (array) wp_unslash( $_GET['event_date'] ) ) ) : array();
$selected_categories = isset( $_GET['event_category'] ) ? array_filter( array_map( 'sanitize_title', (array) wp_unslash( $_GET['event_category'] ) ) ) : array();
$selected_prices     = isset( $_GET['event_price'] ) ? array_filter( array_map( 'sanitize_key', (array) wp_unslash( $_GET['event_price'] ) ) ) : array();
$reset_url           = get_post_type_archive_link( 'tp_event' );
$date_filters        = array(
	'today'        => __( 'Today', 'wp-events-manager' ),
	'tomorrow'     => __( 'Tomorrow', 'wp-events-manager' ),
	'this_week'    => __( 'This Week', 'wp-events-manager' ),
	'this_weekend' => __( 'This Weekend', 'wp-events-manager' ),
);
$price_filters       = array(
	'free' => __( 'Free', 'wp-events-manager' ),
	'paid' => __( 'Paid', 'wp-events-manager' ),
);
$event_categories    = get_terms(
	array(
		'taxonomy'   => 'tp_event_category',
		'hide_empty' => true,
		'number'     => 6,
	)
);
?>

<aside class="wpems-event-filter" aria-label="<?php esc_attr_e( 'Event filters', 'wp-events-manager' ); ?>">
	<form class="wpems-event-filter__form" method="get">
		<?php foreach ( array( 's', 'event_location', 'event_order', 'event_view', 'post_type', 'ref' ) as $query_key ) : ?>
			<?php if ( isset( $_GET[ $query_key ] ) && '' !== $_GET[ $query_key ] ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $query_key ); ?>" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET[ $query_key ] ) ) ); ?>" />
			<?php endif; ?>
		<?php endforeach; ?>

		<div class="wpems-event-filter__header">
			<h2><?php esc_html_e( 'Filters', 'wp-events-manager' ); ?></h2>
			<?php if ( $reset_url ) : ?>
				<a href="<?php echo esc_url( $reset_url ); ?>"><?php esc_html_e( 'Clear', 'wp-events-manager' ); ?></a>
			<?php endif; ?>
		</div>

		<section class="wpems-event-filter__group">
			<h3><?php esc_html_e( 'Date', 'wp-events-manager' ); ?></h3>
			<?php foreach ( $date_filters as $date_key => $date_label ) : ?>
				<label>
					<input type="checkbox" name="event_date[]" value="<?php echo esc_attr( $date_key ); ?>" <?php checked( in_array( $date_key, $selected_dates, true ) ); ?> />
					<span><?php echo esc_html( $date_label ); ?></span>
				</label>
			<?php endforeach; ?>
		</section>

		<?php if ( ! is_wp_error( $event_categories ) && $event_categories ) : ?>
			<section class="wpems-event-filter__group">
				<h3><?php esc_html_e( 'Category', 'wp-events-manager' ); ?></h3>
				<?php foreach ( $event_categories as $event_category ) : ?>
					<label>
						<input type="checkbox" name="event_category[]" value="<?php echo esc_attr( $event_category->slug ); ?>" <?php checked( in_array( $event_category->slug, $selected_categories, true ) ); ?> />
						<span><?php echo esc_html( $event_category->name ); ?></span>
					</label>
				<?php endforeach; ?>
			</section>
		<?php endif; ?>

		<section class="wpems-event-filter__group">
			<h3><?php esc_html_e( 'Price', 'wp-events-manager' ); ?></h3>
			<?php foreach ( $price_filters as $price_key => $price_label ) : ?>
				<label>
					<input type="checkbox" name="event_price[]" value="<?php echo esc_attr( $price_key ); ?>" <?php checked( in_array( $price_key, $selected_prices, true ) ); ?> />
					<span><?php echo esc_html( $price_label ); ?></span>
				</label>
			<?php endforeach; ?>
		</section>

		<div class="wpems-event-filter__actions">
			<?php if ( $reset_url ) : ?>
				<a class="wpems-event-button wpems-event-button--secondary" href="<?php echo esc_url( $reset_url ); ?>"><?php esc_html_e( 'Reset', 'wp-events-manager' ); ?></a>
			<?php endif; ?>
			<button class="wpems-event-button" type="submit"><?php esc_html_e( 'Apply', 'wp-events-manager' ); ?></button>
		</div>
	</form>
</aside>
